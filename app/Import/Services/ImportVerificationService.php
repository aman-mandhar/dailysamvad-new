<?php

namespace App\Import\Services;

use App\Import\Contracts\Verifier;
use App\Import\DTOs\ImportContext;
use App\Import\DTOs\ImportStatistics;
use App\Import\DTOs\VerificationResult;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;

class ImportVerificationService implements Verifier
{
    private array $lastReport = [];

    public function __construct(
        private readonly WordPressConnection $source,
        private readonly RedirectGenerator $redirects,
    ) {}

    public function verify(ImportContext $context): VerificationResult
    {
        $this->lastReport = $this->detailedReport($context->limit);
        $summary = $this->lastReport['summary'];

        return new VerificationResult(
            new ImportStatistics(imported: $summary['imported'], failed: $summary['failed']),
            $summary['failed'] === 0 && $summary['broken_redirect'] === 0,
        );
    }

    /** @return array<string, mixed> */
    public function detailedReport(?int $limit = null): array
    {
        $issues = [];
        $summary = [
            'imported' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0,
            'missing_media' => 0, 'seo_imported' => 0, 'seo_generated' => 0, 'seo_missing' => 0, 'missing_category' => 0,
            'missing_author' => 0, 'broken_redirect' => 0, 'duplicate_slug' => 0,
        ];

        $query = Post::with(['author:id,old_wp_id', 'categories:id,old_wp_id', 'tags:id,old_wp_id'])
            ->whereNotNull('old_wp_id')->orderBy('old_wp_id');
        if ($limit) {
            $query->limit($limit);
        }

        $query->get()->each(function (Post $post) use (&$issues, &$summary): void {
            $summary['imported']++;
            $source = $this->source->connection()->table($this->source->table('posts'))->where('ID', $post->old_wp_id)->first();
            $postIssues = [];
            if (! $source) {
                $postIssues[] = 'source_post_missing';
            } else {
                if ((string) $source->post_title !== $post->title) {
                    $postIssues[] = 'title_mismatch';
                }
                if ((string) $source->post_name !== $post->slug && ! str_ends_with($post->slug, '-wp-'.$post->old_wp_id)) {
                    $postIssues[] = 'slug_mismatch';
                }
                if (mb_strlen((string) $source->post_content) !== mb_strlen($post->content)) {
                    $postIssues[] = 'content_length_mismatch';
                }
                if ($post->published_at && substr((string) ($source->post_date_gmt ?: $source->post_date), 0, 19) !== $post->published_at->format('Y-m-d H:i:s')) {
                    $postIssues[] = 'published_date_mismatch';
                }
                if ((int) $source->post_author !== (int) ($post->author?->old_wp_id ?? 0)) {
                    $postIssues[] = 'author_mismatch';
                }
                $sourceTerms = $this->source->connection()->table($this->source->table('term_relationships').' as relationships')
                    ->join($this->source->table('term_taxonomy').' as taxonomy', 'relationships.term_taxonomy_id', '=', 'taxonomy.term_taxonomy_id')
                    ->where('relationships.object_id', $post->old_wp_id)
                    ->whereIn('taxonomy.taxonomy', ['category', 'post_tag'])
                    ->get(['taxonomy.term_id', 'taxonomy.taxonomy']);
                if ($sourceTerms->where('taxonomy', 'category')->pluck('term_id')->map(fn ($id) => (int) $id)->sort()->values()->all()
                    !== $post->categories->pluck('old_wp_id')->map(fn ($id) => (int) $id)->sort()->values()->all()) {
                    $postIssues[] = 'categories_mismatch';
                }
                if ($sourceTerms->where('taxonomy', 'post_tag')->pluck('term_id')->map(fn ($id) => (int) $id)->sort()->values()->all()
                    !== $post->tags->pluck('old_wp_id')->map(fn ($id) => (int) $id)->sort()->values()->all()) {
                    $postIssues[] = 'tags_mismatch';
                }
            }
            if (! $post->author) {
                $summary['missing_author']++;
                $postIssues[] = 'author_missing';
            }
            if ($post->categories->isEmpty()) {
                $summary['missing_category']++;
                $postIssues[] = 'category_missing';
            }
            $seoProvider = data_get($post->seo_data, 'provider');
            if (in_array($seoProvider, ['Yoast SEO', 'Rank Math'], true) || (filled($post->meta_title) && $seoProvider !== 'Generated')) {
                $summary['seo_imported']++;
            } elseif ($seoProvider === 'Generated' && filled($post->meta_title) && filled($post->meta_description)) {
                $summary['seo_generated']++;
            } else {
                $summary['seo_missing']++;
                $postIssues[] = 'seo_missing';
            }
            if (blank($post->featured_image) || (! str_starts_with($post->featured_image, 'http') && ! Storage::disk('public')->exists($post->featured_image))) {
                $summary['missing_media']++;
                $postIssues[] = 'featured_image_missing';
            }
            if (blank($post->old_url)) {
                $summary['broken_redirect']++;
                $postIssues[] = 'historical_url_missing';
            }
            if ($postIssues !== []) {
                $summary['failed']++;
                $issues[] = ['old_wp_id' => $post->old_wp_id, 'slug' => $post->slug, 'issues' => $postIssues];
            }
        });

        $redirects = $this->redirects->generate();
        $summary['broken_redirect'] += $redirects['broken'];
        $summary['duplicate_slug'] = Post::query()->select('slug')->groupBy('slug')->havingRaw('COUNT(*) > 1')->count();

        return [
            'generated_at' => now()->toIso8601String(),
            'summary' => $summary,
            'redirects' => ['generated' => count($redirects['redirects']), 'duplicates' => $redirects['duplicates'], 'broken' => $redirects['broken']],
            'issues' => $issues,
        ];
    }

    /** @return array<string, mixed> */
    public function lastReport(): array
    {
        return $this->lastReport;
    }
}
