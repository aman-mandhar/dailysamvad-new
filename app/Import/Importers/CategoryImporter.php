<?php

namespace App\Import\Importers;

use App\Import\DTOs\ImportCheckpoint;
use App\Import\DTOs\ImportContext;
use App\Import\Support\ImportMode;
use App\Import\Support\StatisticsCounter;
use App\Models\Category;
use DateTimeImmutable;
use Illuminate\Support\Collection;

class CategoryImporter extends TagImporter
{
    public function key(): string
    {
        return 'categories';
    }

    protected function sourceRecords(int $cursor, int $limit): Collection
    {
        return $this->source->connection()->table($this->source->table('terms').' as terms')
            ->join($this->source->table('term_taxonomy').' as taxonomy', 'terms.term_id', '=', 'taxonomy.term_id')
            ->selectRaw('terms.term_id as source_id, terms.name, terms.slug, taxonomy.description, taxonomy.parent')
            ->where('taxonomy.taxonomy', 'category')->where('terms.term_id', '>', $cursor)
            ->orderBy('terms.term_id')->limit($limit)->get();
    }

    protected function processRecord(object $record, StatisticsCounter $counter, bool $dryRun): void
    {
        $this->importTerm(Category::class, $record, $counter, $dryRun);
    }

    protected function afterChunks(ImportContext $context, StatisticsCounter $counter): void
    {
        if ($context->mode === ImportMode::DryRun) {
            return;
        }

        $checkpointKey = $this->key().'.parents';
        $cursor = $context->resume ? (int) ($this->checkpoints->latest($context->importId, $checkpointKey)?->cursor ?? 0) : 0;

        while (true) {
            $records = $this->sourceRecords($cursor, $context->chunk);
            if ($records->isEmpty()) {
                break;
            }

            $this->database->connection()->transaction(function () use ($records, $counter): void {
                foreach ($records as $record) {
                    if ((int) $record->parent === 0) {
                        continue;
                    }

                    $child = Category::query()->where('old_wp_id', $record->source_id)->first();
                    $parent = Category::query()->where('old_wp_id', $record->parent)->first();

                    if (! $child || ! $parent || $child->is($parent)) {
                        $counter->skipped++;
                        $this->logger->warning('Category parent could not be linked.', [
                            'old_wp_id' => $record->source_id, 'parent_old_wp_id' => $record->parent,
                        ]);

                        continue;
                    }

                    if ($child->parent_id !== $parent->getKey()) {
                        $child->update(['parent_id' => $parent->getKey()]);
                        $counter->updated++;
                    }
                }
            });

            $cursor = (int) $records->last()->source_id;
            $this->checkpoints->store(new ImportCheckpoint(
                $context->importId, $checkpointKey, $cursor, $counter->statistics(), new DateTimeImmutable,
            ));
        }
    }
}
