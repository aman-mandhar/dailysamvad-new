<?php

namespace App\Import\Importers;

use App\Import\Support\StatisticsCounter;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserImporter extends AbstractWordPressImporter
{
    public function key(): string
    {
        return 'users';
    }

    protected function sourceRecords(int $cursor, int $limit): Collection
    {
        return $this->source->connection()->table($this->source->table('users'))
            ->selectRaw('ID as source_id, user_login, user_nicename, user_email, display_name, user_registered')
            ->where('ID', '>', $cursor)->orderBy('ID')->limit($limit)->get();
    }

    protected function processRecord(object $record, StatisticsCounter $counter, bool $dryRun): void
    {
        $email = mb_strtolower(trim((string) $record->user_email));
        $username = trim((string) $record->user_login);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || $username === '') {
            $counter->skipped++;
            $this->logger->warning('Skipped unsupported WordPress user.', ['old_wp_id' => $record->source_id]);

            return;
        }

        $byWordPressId = User::query()->where('old_wp_id', $record->source_id)->first();
        if ($byWordPressId) {
            $counter->skipped++;

            return;
        }

        $byEmail = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($byEmail) {
            if ($byEmail->old_wp_id !== null && (int) $byEmail->old_wp_id !== (int) $record->source_id) {
                $counter->duplicates++;

                return;
            }

            if (! $dryRun) {
                $byEmail->forceFill([
                    'old_wp_id' => $record->source_id,
                    'slug' => $byEmail->slug ?: $this->authorSlug($record, $byEmail),
                ])->save();
            }
            $counter->updated++;

            return;
        }

        if (User::query()->where('username', $username)->exists()) {
            $counter->duplicates++;

            return;
        }

        if (! $dryRun) {
            $registered = $record->user_registered ? Carbon::parse($record->user_registered) : now();
            $user = new User;
            $user->forceFill([
                'old_wp_id' => $record->source_id,
                'name' => trim((string) $record->display_name) ?: $username,
                'username' => $username,
                'slug' => $this->authorSlug($record),
                'email' => $email,
                'password' => Str::password(40),
                'is_active' => true,
                'is_public' => true,
                'created_at' => $registered,
                'updated_at' => $registered,
            ])->save();
        }
        $counter->imported++;
    }

    private function authorSlug(object $record, ?User $existing = null): ?string
    {
        $slug = trim((string) $record->user_nicename);
        if ($slug === '') {
            return null;
        }

        $conflict = User::query()
            ->where('slug', $slug)
            ->when($existing, fn ($query) => $query->whereKeyNot($existing->getKey()))
            ->exists();

        return $conflict ? $slug.'-'.$record->source_id : $slug;
    }
}
