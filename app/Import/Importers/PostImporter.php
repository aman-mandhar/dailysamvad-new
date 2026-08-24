<?php

namespace App\Import\Importers;

use App\Enums\PostStatus;
use App\Import\Contracts\CheckpointRepository;
use App\Import\Contracts\Importer;
use App\Import\Contracts\Logger;
use App\Import\DTOs\ImportCheckpoint;
use App\Import\DTOs\ImportContext;
use App\Import\DTOs\ImportResult;
use App\Import\DTOs\PostImportVerification;
use App\Import\Services\WordPressConnection;
use App\Import\Support\ImportMode;
use App\Import\Support\MojibakeRepair;
use App\Import\Support\StatisticsCounter;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class PostImporter implements Importer
{
    private PostImportVerification $verification;

    public function __construct(
        private readonly WordPressConnection $source,
        private readonly CheckpointRepository $checkpoints,
        private readonly Logger $logger,
        private readonly DatabaseManager $database,
        private readonly MojibakeRepair $text,
    ) {
        $this->verification = new PostImportVerification;
    }

    public function key(): string
    {
        return 'posts';
    }

    public function verification(): PostImportVerification
    {
        return $this->verification;
    }

    public function import(ImportContext $context): ImportResult
    {
        $this->verification = new PostImportVerification;
        $counter = new StatisticsCounter;
        $limit = $context->limit ?? (int) config('import.pilot.limit', 100);
        $position = $context->resume
            ? (int) ($this->checkpoints->latest($context->importId, $this->checkpointKey($context))?->cursor ?? $context->offset)
            : $context->offset;
        $processed = max(0, $position - $context->offset);
        $started = microtime(true);

        $this->recordFilteredStatuses($context);

        $this->logger->info('Starting pilot post import.', [
            'mode' => $context->mode->value, 'limit' => $limit, 'offset' => $position,
            'order' => $context->order, 'status' => $context->status,
            'ids' => $context->ids, 'memory_mb' => $this->memoryMb(),
        ]);

        while ($processed < $limit) {
            $take = min($context->chunk, $limit - $processed);
            $posts = $this->sourcePosts($context, $position, $take);

            if ($posts->isEmpty()) {
                break;
            }

            $chunkStarted = microtime(true);
            $postIds = $posts->pluck('source_id')->map(fn ($id) => (int) $id)->all();
            $metadata = $this->metadata($postIds);
            $terms = $this->terms($postIds);

            try {
                $work = function () use ($posts, $metadata, $terms, $counter, $context): void {
                    foreach ($posts as $record) {
                        $this->processPost(
                            $record,
                            $metadata->get((int) $record->source_id, collect()),
                            $terms->get((int) $record->source_id, collect()),
                            $counter,
                            $context->mode === ImportMode::DryRun,
                        );
                    }
                };

                $context->mode === ImportMode::DryRun ? $work() : $this->database->connection()->transaction($work);
            } catch (Throwable $exception) {
                $counter->failed += $posts->count();
                $this->logger->error('Pilot post chunk rolled back.', [
                    'offset' => $position, 'records' => $posts->count(), 'error' => $exception->getMessage(),
                ]);
                throw $exception;
            }

            $position += $posts->count();
            $processed += $posts->count();

            if ($context->mode === ImportMode::Live) {
                $this->checkpoints->store(new ImportCheckpoint(
                    $context->importId, $this->checkpointKey($context), $position,
                    $counter->statistics(), new DateTimeImmutable,
                ));
            }

            $this->logger->info('Completed pilot post chunk.', [
                'offset' => $position, 'records' => $posts->count(),
                'seconds' => round(microtime(true) - $chunkStarted, 4),
                'memory_mb' => $this->memoryMb(), ...$counter->statistics()->toArray(),
                ...$this->verification->toArray(),
            ]);
        }

        $statistics = $counter->statistics();
        $this->logger->success('Completed pilot post import.', [
            'seconds' => round(microtime(true) - $started, 4), 'memory_mb' => $this->memoryMb(),
            ...$statistics->toArray(), ...$this->verification->toArray(),
        ]);

        return new ImportResult($statistics, true);
    }

    private function sourcePosts(ImportContext $context, int $offset, int $limit): Collection
    {
        $query = $this->source->connection()->table($this->source->table('posts'))
            ->selectRaw('ID as source_id, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, post_name, post_modified, post_modified_gmt, guid')
            ->where('post_type', 'post');

        if ($context->ids !== []) {
            $query->whereIn('ID', $context->ids);
        }

        if ($context->status !== 'all') {
            $query->where('post_status', $context->status);
        }

        $direction = $context->order === 'oldest' ? 'asc' : 'desc';

        return $query->orderBy('post_date', $direction)->orderBy('ID', $direction)
            ->offset($offset)->limit($limit)->get();
    }

    private function metadata(array $postIds): Collection
    {
        return $this->source->connection()->table($this->source->table('postmeta'))
            ->whereIn('post_id', $postIds)->whereIn('meta_key', [
                '_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw',
                '_yoast_wpseo_canonical', '_yoast_wpseo_primary_category', '_language', 'language',
                'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword',
                'rank_math_canonical_url', 'rank_math_primary_category',
                '_thumbnail_id',
            ])->get(['post_id', 'meta_key', 'meta_value'])->groupBy('post_id');
    }

    private function terms(array $postIds): Collection
    {
        return $this->source->connection()->table($this->source->table('term_relationships').' as relationships')
            ->join($this->source->table('term_taxonomy').' as taxonomy', 'relationships.term_taxonomy_id', '=', 'taxonomy.term_taxonomy_id')
            ->join($this->source->table('terms').' as terms', 'taxonomy.term_id', '=', 'terms.term_id')
            ->whereIn('relationships.object_id', $postIds)
            ->whereIn('taxonomy.taxonomy', ['category', 'post_tag', 'language'])
            ->get(['relationships.object_id', 'taxonomy.term_id', 'taxonomy.taxonomy', 'terms.slug'])
            ->groupBy('object_id');
    }

    private function processPost(object $record, Collection $metadata, Collection $terms, StatisticsCounter $counter, bool $dryRun): void
    {
        $oldId = (int) $record->source_id;
        $post = Post::withTrashed()->where('old_wp_id', $oldId)->first();
        $alreadyImported = $post !== null;
        $status = $this->status((string) $record->post_status);
        if ($status === null) {
            $this->verification->unsupportedStatus++;
            $counter->skipped++;
            $this->logger->warning('Skipped unsupported WordPress post status.', [
                'old_wp_id' => $oldId, 'status' => $record->post_status,
            ]);

            return;
        }

        $author = User::query()->where('old_wp_id', $record->post_author)->first();
        if (! $author) {
            $this->verification->missingAuthor++;
        }

        $meta = $metadata->pluck('meta_value', 'meta_key')
            ->map(fn ($value) => $this->text->repair($value === null ? null : (string) $value));
        $slug = $this->safeSlug((string) $record->post_name, $oldId, $post);
        $publishedAt = $status === PostStatus::Published ? $this->date($record->post_date_gmt ?: $record->post_date) : null;
        $scheduledAt = $status === PostStatus::Scheduled ? $this->date($record->post_date_gmt ?: $record->post_date) : null;
        $seo = $this->seo($record, $meta, $post);

        $attributes = [
            'old_wp_id' => $oldId,
            'author_id' => $author?->getKey(),
            'title' => $this->text->repair((string) $record->post_title),
            'slug' => $slug,
            'excerpt' => $this->nullable($this->text->repair($record->post_excerpt)),
            'content' => $this->text->repair((string) $record->post_content),
            'status' => $status,
            'language' => $this->language($meta, $terms),
            'published_at' => $publishedAt,
            'scheduled_at' => $scheduledAt,
            'meta_title' => $seo['meta_title'],
            'meta_description' => $seo['meta_description'],
            'focus_keyword' => $seo['focus_keyword'],
            'canonical_url' => $seo['canonical_url'],
            'old_url' => $this->historicalUrl($record),
            'source_url' => $this->nullable($this->text->repair($record->guid)),
            'source_name' => 'WordPress',
            'source_data' => [
                'wordpress_id' => $oldId,
                'original_status' => $record->post_status,
                'guid' => $record->guid,
                'modified_at' => $record->post_modified_gmt ?: $record->post_modified,
            ],
            'seo_data' => ['provider' => $seo['provider'], 'raw' => $seo['raw']],
            'created_at' => $this->date($record->post_date_gmt ?: $record->post_date),
            'updated_at' => $this->date($record->post_modified_gmt ?: $record->post_modified),
        ];

        if (! $dryRun) {
            $post ??= new Post;
            $post->forceFill($attributes);
            $post->timestamps = false;
            $post->save();
            $post->timestamps = true;
            $this->resolveFeaturedImage($post, $meta);
            $this->syncTaxonomy($post, $terms, $meta, $status);
        } else {
            $this->verifyTaxonomy($terms, $status);
        }

        $alreadyImported ? $counter->updated++ : $counter->imported++;
    }

    private function resolveFeaturedImage(Post $post, Collection $meta): void
    {
        $attachmentId = (int) $meta->get('_thumbnail_id');
        if ($attachmentId < 1) {
            return;
        }

        $media = Media::withTrashed()->where('old_wp_id', $attachmentId)->first();
        if (! $media) {
            return;
        }

        if ($media->trashed()) {
            $media->restore();
        }

        $post->update([
            'featured_media_id' => $media->getKey(),
            'featured_image' => $media->path,
            'featured_image_alt' => $media->alt_text ?: $post->featured_image_alt,
            'featured_image_caption' => $media->caption ?: $post->featured_image_caption,
        ]);
    }

    private function syncTaxonomy(Post $post, Collection $terms, Collection $meta, PostStatus $status): void
    {
        $categoryOldIds = $terms->where('taxonomy', 'category')->pluck('term_id')->map(fn ($id) => (int) $id);
        $categories = Category::query()->whereIn('old_wp_id', $categoryOldIds)->get()->keyBy('old_wp_id');
        $this->recordCategoryVerification($categoryOldIds, $categories->keys(), $status);
        $primaryOldId = (int) ($meta->get('_yoast_wpseo_primary_category') ?: $meta->get('rank_math_primary_category') ?: $categoryOldIds->first());
        $categorySync = $categories->mapWithKeys(fn (Category $category) => [
            $category->getKey() => ['is_primary' => (int) $category->old_wp_id === $primaryOldId],
        ])->all();
        if ($categorySync !== [] && ! collect($categorySync)->contains('is_primary', true)) {
            $first = array_key_first($categorySync);
            $categorySync[$first]['is_primary'] = true;
        }
        $post->categories()->sync($categorySync);

        $tagOldIds = $terms->where('taxonomy', 'post_tag')->pluck('term_id')->map(fn ($id) => (int) $id);
        $tags = Tag::query()->whereIn('old_wp_id', $tagOldIds)->get();
        $this->verification->missingTag += $tagOldIds->diff($tags->pluck('old_wp_id'))->count();
        $post->tags()->sync($tags->modelKeys());
    }

    private function verifyTaxonomy(Collection $terms, PostStatus $status): void
    {
        $categoryIds = $terms->where('taxonomy', 'category')->pluck('term_id');
        $foundCategories = Category::query()->whereIn('old_wp_id', $categoryIds)->pluck('old_wp_id');
        $this->recordCategoryVerification($categoryIds, $foundCategories, $status);
        $tagIds = $terms->where('taxonomy', 'post_tag')->pluck('term_id');
        $this->verification->missingTag += max(0, $tagIds->count() - Tag::query()->whereIn('old_wp_id', $tagIds)->count());
    }

    private function recordCategoryVerification(Collection $sourceCategoryIds, Collection $mappedCategoryIds, PostStatus $status): void
    {
        if ($sourceCategoryIds->isEmpty()) {
            if ($status === PostStatus::Draft) {
                $this->verification->draftWithoutCategory++;
            } else {
                $this->verification->missingCategory++;
            }

            return;
        }

        $this->verification->categoryMappingFailure += $sourceCategoryIds
            ->map(fn ($id) => (int) $id)
            ->diff($mappedCategoryIds->map(fn ($id) => (int) $id))
            ->count();
    }

    /** @return array{meta_title: ?string, meta_description: ?string, focus_keyword: ?string, canonical_url: ?string, provider: string, raw: array<string, mixed>} */
    private function seo(object $record, Collection $meta, ?Post $post): array
    {
        $yoastKeys = ['_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw', '_yoast_wpseo_canonical'];
        $rankMathKeys = ['rank_math_title', 'rank_math_description', 'rank_math_focus_keyword', 'rank_math_canonical_url'];
        $hasYoast = $meta->only($yoastKeys)->filter(fn ($value) => filled($value))->isNotEmpty();
        $hasRankMath = $meta->only($rankMathKeys)->filter(fn ($value) => filled($value))->isNotEmpty();
        $provider = $hasYoast ? 'Yoast SEO' : ($hasRankMath ? 'Rank Math' : 'Generated');
        $generatedDescription = $this->nullable($record->post_excerpt) ?: $this->generatedDescription((string) $record->post_content);
        $metaTitle = $post?->meta_title
            ?: $this->nullable($meta->get('_yoast_wpseo_title'))
            ?: $this->nullable($meta->get('rank_math_title'))
            ?: $this->nullable($record->post_title);
        $metaDescription = $post?->meta_description
            ?: $this->nullable($meta->get('_yoast_wpseo_metadesc'))
            ?: $this->nullable($meta->get('rank_math_description'))
            ?: $generatedDescription;

        if ($hasYoast || $hasRankMath) {
            $this->verification->seoImported++;
            $this->logger->info("Imported {$provider} metadata.", ['old_wp_id' => $record->source_id]);
        } elseif ($metaTitle && $metaDescription) {
            $this->verification->seoGenerated++;
            $this->logger->info('Generated SEO metadata.', ['old_wp_id' => $record->source_id]);
        } else {
            $this->verification->seoMissing++;
            $this->logger->warning('SEO metadata could not be generated.', ['old_wp_id' => $record->source_id]);
        }

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'focus_keyword' => $post?->focus_keyword
                ?: $this->nullable($meta->get('_yoast_wpseo_focuskw'))
                ?: $this->nullable($meta->get('rank_math_focus_keyword')),
            'canonical_url' => $post?->canonical_url
                ?: $this->nullable($meta->get('_yoast_wpseo_canonical'))
                ?: $this->nullable($meta->get('rank_math_canonical_url')),
            'provider' => $provider,
            'raw' => $meta->only([...$yoastKeys, ...$rankMathKeys])->all(),
        ];
    }

    private function generatedDescription(string $content): ?string
    {
        $plainText = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = Str::limit(Str::squish($plainText), 160, '');

        return $description === '' ? null : $description;
    }

    private function recordFilteredStatuses(ImportContext $context): void
    {
        if ($context->status === 'all') {
            return;
        }

        $query = $this->source->connection()->table($this->source->table('posts'))
            ->where('post_type', 'post');
        if ($context->ids !== []) {
            $query->whereIn('ID', $context->ids);
        }

        $this->verification->skippedByFilter = (clone $query)
            ->whereIn('post_status', $this->supportedStatuses())
            ->where('post_status', '!=', $context->status)
            ->count();

        if ($this->verification->skippedByFilter > 0) {
            $this->logger->info('Skipped WordPress posts excluded by status filter.', [
                'status' => $context->status, 'count' => $this->verification->skippedByFilter,
            ]);
        }

        (clone $query)->whereNotIn('post_status', $this->supportedStatuses())
            ->selectRaw('post_status, COUNT(*) as aggregate')->groupBy('post_status')->get()
            ->each(function (object $status): void {
                $this->verification->unsupportedStatus += (int) $status->aggregate;
                $this->logger->warning('Skipped unsupported WordPress status.', [
                    'status' => $status->post_status, 'count' => (int) $status->aggregate,
                ]);
            });
    }

    /** @return array<int, string> */
    private function supportedStatuses(): array
    {
        return ['publish', 'draft', 'pending', 'future', 'private'];
    }

    private function safeSlug(string $sourceSlug, int $oldId, ?Post $post): string
    {
        $slug = $sourceSlug !== '' ? $sourceSlug : "post-{$oldId}";
        $conflict = Post::withTrashed()->where('slug', $slug)
            ->when($post, fn ($query) => $query->where($post->getKeyName(), '!=', $post->getKey()))->exists();
        if (! $conflict) {
            return $slug;
        }

        $this->verification->slugConflict++;

        return Str::limit($slug, 240, '').'-wp-'.$oldId;
    }

    private function language(Collection $meta, Collection $terms): string
    {
        $value = $meta->get('_language') ?: $meta->get('language') ?: $terms->firstWhere('taxonomy', 'language')?->slug;
        $normalized = strtolower((string) $value);

        return match ($normalized) {
            'en', 'english' => 'en',
            'pa', 'punjabi', 'panjabi' => 'pa',
            'hi', 'hindi' => 'hi',
            default => (string) config('import.pilot.default_language', 'hi'),
        };
    }

    private function status(string $status): ?PostStatus
    {
        return match ($status) {
            'publish' => PostStatus::Published,
            'draft' => PostStatus::Draft,
            'pending' => PostStatus::PendingReview,
            'future' => PostStatus::Scheduled,
            'private' => PostStatus::Archived,
            default => null,
        };
    }

    private function historicalUrl(object $record): ?string
    {
        $siteUrl = rtrim((string) config('import.profiles.wordpress.site_url'), '/');

        return $siteUrl !== '' && $record->post_name !== '' ? $siteUrl.'/'.$record->post_name.'/' : $this->nullable($record->guid);
    }

    private function date(?string $value): ?Carbon
    {
        return ! $value || str_starts_with($value, '0000-00-00') ? null : Carbon::parse($value);
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function checkpointKey(ImportContext $context): string
    {
        $selection = $context->ids === [] ? $context->order : 'ids-'.sha1(implode(',', $context->ids));

        return "posts.{$context->status}.{$selection}.offset-{$context->offset}";
    }

    private function memoryMb(): float
    {
        return round(memory_get_usage(true) / 1048576, 2);
    }
}
