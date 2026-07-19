<?php

namespace App\Import\Importers;

use App\Import\Contracts\TaxonomyImporter;
use App\Import\Support\StatisticsCounter;
use App\Models\Tag;
use Illuminate\Support\Collection;

class TagImporter extends AbstractWordPressImporter implements TaxonomyImporter
{
    public function key(): string
    {
        return 'tags';
    }

    protected function sourceRecords(int $cursor, int $limit): Collection
    {
        return $this->source->connection()->table($this->source->table('terms').' as terms')
            ->join($this->source->table('term_taxonomy').' as taxonomy', 'terms.term_id', '=', 'taxonomy.term_id')
            ->selectRaw('terms.term_id as source_id, terms.name, terms.slug, taxonomy.description')
            ->where('taxonomy.taxonomy', 'post_tag')->where('terms.term_id', '>', $cursor)
            ->orderBy('terms.term_id')->limit($limit)->get();
    }

    protected function processRecord(object $record, StatisticsCounter $counter, bool $dryRun): void
    {
        $this->importTerm(Tag::class, $record, $counter, $dryRun);
    }

    protected function importTerm(string $model, object $record, StatisticsCounter $counter, bool $dryRun): void
    {
        if (trim((string) $record->name) === '' || trim((string) $record->slug) === '') {
            $counter->skipped++;

            return;
        }

        if ($model::query()->where('old_wp_id', $record->source_id)->exists()) {
            $counter->skipped++;

            return;
        }

        $existing = $model::query()->where('slug', $record->slug)->first();
        if ($existing) {
            if ($existing->old_wp_id === null) {
                if (! $dryRun) {
                    $existing->update(['old_wp_id' => $record->source_id]);
                }
                $counter->updated++;
            } else {
                $counter->duplicates++;
            }

            return;
        }

        $existing = $model::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $record->name))])
            ->first();
        if ($existing) {
            if ($existing->old_wp_id === null) {
                if (! $dryRun) {
                    $existing->update(['old_wp_id' => $record->source_id]);
                }
                $counter->updated++;
            } else {
                $counter->duplicates++;
            }

            return;
        }

        if (! $dryRun) {
            $model::query()->create([
                'old_wp_id' => $record->source_id, 'name' => $record->name,
                'slug' => $record->slug, 'description' => $record->description ?: null,
            ]);
        }
        $counter->imported++;
    }
}
