<?php

namespace App\Import\Importers;

use App\Import\DTOs\ImportContext;
use App\Import\DTOs\ImportResult;
use App\Import\DTOs\SeoImportVerification;
use App\Import\Support\StatisticsCounter;
use App\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SeoImporter extends AbstractWordPressImporter
{
    private SeoImportVerification $verification;

    public function key(): string
    {
        return 'seo';
    }

    public function verification(): SeoImportVerification
    {
        return $this->verification;
    }

    public function import(ImportContext $context): ImportResult
    {
        $this->verification = new SeoImportVerification;

        return parent::import($context);
    }

    protected function sourceRecords(int $cursor, int $limit): Collection
    {
        $posts = $this->source->connection()->table($this->source->table('posts'))
            ->selectRaw('ID as source_id, post_name, post_title, post_excerpt, post_content, guid')
            ->where('post_type', 'post')->where('ID', '>', $cursor)->orderBy('ID')->limit($limit)->get();

        $metadata = $this->source->connection()->table($this->source->table('postmeta'))
            ->whereIn('post_id', $posts->pluck('source_id'))->whereIn('meta_key', $this->metaKeys())
            ->get(['post_id', 'meta_key', 'meta_value'])->groupBy('post_id');

        return $posts->each(function (object $post) use ($metadata): void {
            $post->seo_metadata = $metadata->get($post->source_id, collect())->pluck('meta_value', 'meta_key')->all();
        });
    }

    protected function processRecord(object $record, StatisticsCounter $counter, bool $dryRun): void
    {
        $post = Post::withTrashed()->where('old_wp_id', $record->source_id)->first();
        if (! $post) {
            $this->verification->missingPost++;
            $counter->skipped++;

            return;
        }

        $meta = collect($record->seo_metadata);
        $hasYoast = $meta->only($this->yoastKeys())->filter(fn ($value) => filled($value))->isNotEmpty();
        $hasRankMath = $meta->only($this->rankMathKeys())->filter(fn ($value) => filled($value))->isNotEmpty();
        $provider = $hasYoast ? 'Yoast SEO' : ($hasRankMath ? 'Rank Math' : 'Generated');
        $generatedDescription = $this->value(collect(['excerpt' => $record->post_excerpt]), 'excerpt')
            ?: $this->generatedDescription((string) $record->post_content);

        $seoData = $post->seo_data ?? [];
        $imported = [
            'provider' => $seoData['provider'] ?? $provider,
            'robots' => $seoData['robots'] ?? $this->robots($meta),
            'open_graph' => array_filter([
                'title' => data_get($seoData, 'open_graph.title') ?: $this->value($meta, '_yoast_wpseo_opengraph-title') ?: $this->value($meta, 'rank_math_facebook_title'),
                'description' => data_get($seoData, 'open_graph.description') ?: $this->value($meta, '_yoast_wpseo_opengraph-description') ?: $this->value($meta, 'rank_math_facebook_description'),
            ]),
            'twitter' => array_filter([
                'title' => data_get($seoData, 'twitter.title') ?: $this->value($meta, '_yoast_wpseo_twitter-title') ?: $this->value($meta, 'rank_math_twitter_title'),
                'description' => data_get($seoData, 'twitter.description') ?: $this->value($meta, '_yoast_wpseo_twitter-description') ?: $this->value($meta, 'rank_math_twitter_description'),
            ]),
            'raw' => [...($seoData['raw'] ?? []), ...$meta->all()],
        ];
        $seoData = array_replace_recursive($imported, $seoData);
        $attributes = array_filter([
            'meta_title' => $post->meta_title ?: $this->value($meta, '_yoast_wpseo_title') ?: $this->value($meta, 'rank_math_title') ?: $this->nullable($record->post_title),
            'meta_description' => $post->meta_description ?: $this->value($meta, '_yoast_wpseo_metadesc') ?: $this->value($meta, 'rank_math_description') ?: $generatedDescription,
            'focus_keyword' => $post->focus_keyword ?: $this->value($meta, '_yoast_wpseo_focuskw') ?: $this->value($meta, 'rank_math_focus_keyword'),
            'canonical_url' => $post->canonical_url ?: $this->normalizeUrl($this->value($meta, '_yoast_wpseo_canonical') ?: $this->value($meta, 'rank_math_canonical_url')),
            'old_url' => $post->old_url ?: $this->historicalUrl($record),
            'source_name' => $post->source_name ?: 'WordPress',
            'source_url' => $post->source_url ?: $this->normalizeUrl((string) $record->guid),
            'seo_data' => array_filter($seoData, fn ($value) => $value !== [] && $value !== null),
        ], fn ($value) => $value !== null && $value !== '');

        if ($hasYoast || $hasRankMath) {
            $this->verification->seoImported++;
            $this->logger->info("Imported {$provider} metadata.", ['old_wp_id' => $record->source_id]);
        } elseif (filled($attributes['meta_title'] ?? null) && filled($attributes['meta_description'] ?? null)) {
            $this->verification->seoGenerated++;
            $this->logger->info('Generated SEO metadata.', ['old_wp_id' => $record->source_id]);
        } else {
            $this->verification->seoMissing++;
            $this->logger->warning('SEO metadata could not be generated.', ['old_wp_id' => $record->source_id]);
        }

        $changed = collect($attributes)->contains(fn ($value, $key) => $post->{$key} != $value);
        if (! $changed) {
            $counter->skipped++;

            return;
        }

        if (! $dryRun) {
            $post->update($attributes);
        }
        $counter->updated++;
    }

    /** @return array<string, bool>|null */
    private function robots(Collection $meta): ?array
    {
        $noIndex = $this->value($meta, '_yoast_wpseo_meta-robots-noindex');
        $noFollow = $this->value($meta, '_yoast_wpseo_meta-robots-nofollow');
        $rankMath = strtolower((string) $this->value($meta, 'rank_math_robots'));

        return $noIndex === null && $noFollow === null && $rankMath === '' ? null : [
            'index' => ! in_array($noIndex, ['1', 'noindex'], true) && ! str_contains($rankMath, 'noindex'),
            'follow' => ! in_array($noFollow, ['1', 'nofollow'], true) && ! str_contains($rankMath, 'nofollow'),
        ];
    }

    private function historicalUrl(object $record): ?string
    {
        $base = rtrim((string) config('import.profiles.wordpress.site_url'), '/');

        return $this->normalizeUrl($base && $record->post_name ? $base.'/'.$record->post_name.'/' : (string) $record->guid);
    }

    private function normalizeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        if (! isset($parts['scheme'], $parts['host'])) {
            return '/'.ltrim(preg_replace('#/+#', '/', $parts['path'] ?? ''), '/');
        }

        $path = '/'.ltrim(preg_replace('#/+#', '/', $parts['path'] ?? ''), '/');

        return strtolower($parts['scheme']).'://'.strtolower($parts['host']).($parts['port'] ?? null ? ':'.$parts['port'] : '').$path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    private function value(Collection $meta, string $key): ?string
    {
        $value = trim((string) $meta->get($key));

        return $value === '' ? null : $value;
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function generatedDescription(string $content): ?string
    {
        $plainText = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = Str::limit(Str::squish($plainText), 160, '');

        return $description === '' ? null : $description;
    }

    /** @return array<int, string> */
    private function yoastKeys(): array
    {
        return [
            '_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw', '_yoast_wpseo_canonical',
            '_yoast_wpseo_meta-robots-noindex', '_yoast_wpseo_meta-robots-nofollow',
            '_yoast_wpseo_opengraph-title', '_yoast_wpseo_opengraph-description',
            '_yoast_wpseo_twitter-title', '_yoast_wpseo_twitter-description',
        ];
    }

    /** @return array<int, string> */
    private function rankMathKeys(): array
    {
        return [
            'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword', 'rank_math_canonical_url',
            'rank_math_robots', 'rank_math_facebook_title', 'rank_math_facebook_description',
            'rank_math_twitter_title', 'rank_math_twitter_description',
        ];
    }

    /** @return array<int, string> */
    private function metaKeys(): array
    {
        return [...$this->yoastKeys(), ...$this->rankMathKeys()];
    }
}
