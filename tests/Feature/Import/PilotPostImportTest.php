<?php

namespace Tests\Feature\Import;

use App\Import\Services\WordPressConnection;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PilotPostImportTest extends TestCase
{
    use RefreshDatabase;

    private Connection $wordpress;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config()->set('import.profiles.wordpress.database', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        config()->set('import.profiles.wordpress.table_prefix', 'wp_');
        config()->set('import.profiles.wordpress.site_url', 'https://old.example.com');
        config()->set('import.checkpoint.disk', 'local');
        config()->set('import.dry_run', false);
        config()->set('import.resume', false);

        $this->wordpress = app(WordPressConnection::class)->connection();
        $schema = $this->wordpress->getSchemaBuilder();
        $schema->create('wp_posts', function ($table): void {
            $table->unsignedBigInteger('ID')->primary();
            $table->unsignedBigInteger('post_author')->default(0);
            $table->dateTime('post_date')->nullable();
            $table->dateTime('post_date_gmt')->nullable();
            $table->longText('post_content')->nullable();
            $table->string('post_title')->nullable();
            $table->text('post_excerpt')->nullable();
            $table->string('post_status');
            $table->string('post_name')->nullable();
            $table->dateTime('post_modified')->nullable();
            $table->dateTime('post_modified_gmt')->nullable();
            $table->text('guid')->nullable();
            $table->string('post_type');
        });
        $schema->create('wp_postmeta', function ($table): void {
            $table->increments('meta_id');
            $table->unsignedBigInteger('post_id');
            $table->string('meta_key')->nullable();
            $table->text('meta_value')->nullable();
        });
        $schema->create('wp_terms', function ($table): void {
            $table->unsignedBigInteger('term_id')->primary();
            $table->string('name');
            $table->string('slug');
        });
        $schema->create('wp_term_taxonomy', function ($table): void {
            $table->unsignedBigInteger('term_taxonomy_id')->primary();
            $table->unsignedBigInteger('term_id');
            $table->string('taxonomy');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent')->default(0);
        });
        $schema->create('wp_term_relationships', function ($table): void {
            $table->unsignedBigInteger('object_id');
            $table->unsignedBigInteger('term_taxonomy_id');
        });
    }

    public function test_default_pilot_imports_one_hundred_latest_posts_and_preserves_html(): void
    {
        foreach (range(1, 105) as $id) {
            $this->insertPost($id);
        }

        $this->runImport();

        $this->assertDatabaseCount('posts', 100);
        $this->assertDatabaseHas('posts', ['old_wp_id' => 105, 'content' => '<p>Exact&nbsp;<strong>HTML</strong></p>']);
        $this->assertDatabaseMissing('posts', ['old_wp_id' => 1]);
    }

    public function test_duplicate_run_updates_instead_of_creating_another_post(): void
    {
        $this->insertPost(10, title: 'Original');
        $this->runImport(['--limit' => 1]);
        $this->wordpress->table('wp_posts')->where('ID', 10)->update(['post_title' => 'Updated']);
        $this->runImport(['--limit' => 1]);

        $this->assertDatabaseCount('posts', 1);
        $this->assertDatabaseHas('posts', ['old_wp_id' => 10, 'title' => 'Updated']);
    }

    public function test_resume_continues_from_the_completed_chunk(): void
    {
        $this->insertPost(1);
        $this->insertPost(2);
        $this->insertPost(3);
        $this->runImport(['--limit' => 1, '--chunk' => 1]);
        $this->runImport(['--limit' => 3, '--chunk' => 1, '--resume' => true]);

        $this->assertDatabaseCount('posts', 3);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->insertPost(1);
        $this->runImport(['--dry-run' => true]);

        $this->assertDatabaseCount('posts', 0);
        Storage::disk('local')->assertMissing('imports/checkpoints/wordpress/posts.publish.latest.offset-0.json');
    }

    public function test_categories_tags_author_and_yoast_metadata_are_mapped(): void
    {
        User::factory()->create(['old_wp_id' => 7]);
        $category = Category::factory()->create(['old_wp_id' => 20]);
        $tag = Tag::factory()->create(['old_wp_id' => 30]);
        $this->insertPost(1, author: 7);
        $this->insertTerm(20, 'News', 'news', 'category');
        $this->insertTerm(30, 'Laravel', 'laravel', 'post_tag');
        $this->relate(1, 20);
        $this->relate(1, 30);
        $this->meta(1, '_yoast_wpseo_title', 'SEO title');
        $this->meta(1, '_yoast_wpseo_primary_category', '20');

        $this->runImport();

        $post = Post::query()->where('old_wp_id', 1)->firstOrFail();
        $this->assertSame(7, $post->author->old_wp_id);
        $this->assertTrue($post->categories->contains($category));
        $this->assertTrue($post->tags->contains($tag));
        $this->assertTrue($post->primaryCategory->contains($category));
        $this->assertSame('SEO title', $post->meta_title);
    }

    public function test_slug_conflicts_are_resolved_deterministically(): void
    {
        Post::factory()->create(['slug' => 'shared']);
        $this->insertPost(50, slug: 'shared');
        $this->runImport();
        $this->runImport();

        $this->assertDatabaseCount('posts', 2);
        $this->assertDatabaseHas('posts', ['old_wp_id' => 50, 'slug' => 'shared-wp-50']);
    }

    public function test_oldest_offset_and_specific_id_selection_are_supported(): void
    {
        $this->insertPost(1);
        $this->insertPost(2);
        $this->insertPost(3);
        $this->runImport(['--order' => 'oldest', '--offset' => 1, '--limit' => 1]);
        $this->runImport(['--ids' => ['3'], '--limit' => 1]);

        $this->assertDatabaseHas('posts', ['old_wp_id' => 2]);
        $this->assertDatabaseHas('posts', ['old_wp_id' => 3]);
        $this->assertDatabaseMissing('posts', ['old_wp_id' => 1]);
    }

    public function test_post_import_resolves_an_already_imported_featured_media_idempotently(): void
    {
        $this->insertPost(10);
        $this->meta(10, '_thumbnail_id', '100');
        $media = Media::query()->create([
            'old_wp_id' => 100, 'path' => 'wordpress/uploads/photo.jpg',
            'alt_text' => 'Photo alt', 'caption' => 'Photo caption',
            'disk' => 'public', 'mime_type' => 'image/jpeg', 'size' => 1,
        ]);

        $this->runImport();
        $this->runImport();

        $post = Post::query()->where('old_wp_id', 10)->firstOrFail();
        $this->assertSame($media->id, $post->featured_media_id);
        $this->assertSame($media->path, $post->featured_image);
        $this->assertSame('Photo alt', $post->featured_image_alt);
        $this->assertSame('Photo caption', $post->featured_image_caption);
        $this->assertSame(1, Post::query()->where('old_wp_id', 10)->count());
    }

    public function test_publish_is_the_default_status_filter(): void
    {
        $this->insertPost(1, status: 'publish');
        $this->insertPost(2, status: 'draft');

        $this->runImport();

        $this->assertDatabaseHas('posts', ['old_wp_id' => 1]);
        $this->assertDatabaseMissing('posts', ['old_wp_id' => 2]);
        $this->assertStringContainsString('skipped_by_filter', Artisan::output());
    }

    public function test_status_filter_can_import_drafts_only(): void
    {
        $this->insertPost(1, status: 'publish');
        $this->insertPost(2, status: 'draft');

        $this->runImport(['--status' => 'draft']);

        $this->assertDatabaseMissing('posts', ['old_wp_id' => 1]);
        $this->assertDatabaseHas('posts', ['old_wp_id' => 2, 'status' => 'draft']);
    }

    public function test_trash_and_auto_draft_posts_are_skipped_as_unsupported(): void
    {
        $this->insertPost(1, status: 'trash');
        $this->insertPost(2, status: 'auto-draft');
        $this->insertPost(3, status: 'publish');

        $this->runImport(['--status' => 'all']);

        $this->assertDatabaseCount('posts', 1);
        $this->assertDatabaseHas('posts', ['old_wp_id' => 3]);
        $this->assertStringContainsString('unsupported_status', Artisan::output());
    }

    public function test_rank_math_metadata_is_imported(): void
    {
        $this->insertPost(1);
        $this->meta(1, 'rank_math_title', 'Rank Math title');
        $this->meta(1, 'rank_math_description', 'Rank Math description');
        $this->meta(1, 'rank_math_focus_keyword', 'rank keyword');

        $this->runImport();

        $post = Post::query()->where('old_wp_id', 1)->firstOrFail();
        $this->assertSame('Rank Math title', $post->meta_title);
        $this->assertSame('Rank Math description', $post->meta_description);
        $this->assertSame('Rank Math', $post->seo_data['provider']);
    }

    public function test_yoast_takes_priority_over_rank_math(): void
    {
        $this->insertPost(1);
        $this->meta(1, '_yoast_wpseo_title', 'Yoast title');
        $this->meta(1, 'rank_math_title', 'Rank Math title');

        $this->runImport();

        $post = Post::query()->where('old_wp_id', 1)->firstOrFail();
        $this->assertSame('Yoast title', $post->meta_title);
        $this->assertSame('Yoast SEO', $post->seo_data['provider']);
    }

    public function test_seo_is_generated_when_plugin_metadata_is_absent(): void
    {
        $this->insertPost(1);

        $this->runImport();

        $post = Post::query()->where('old_wp_id', 1)->firstOrFail();
        $this->assertSame('Pilot post 1', $post->meta_title);
        $this->assertSame('Pilot excerpt', $post->meta_description);
        $this->assertSame('Generated', $post->seo_data['provider']);
    }

    public function test_verification_report_uses_hardened_status_seo_and_category_fields(): void
    {
        $this->insertPost(1);

        $this->runImport(['--dry-run' => true]);

        $output = Artisan::output();
        foreach (['seo_imported', 'seo_generated', 'seo_missing', 'skipped_by_filter', 'unsupported_status', 'category_mapping_failure'] as $field) {
            $this->assertStringContainsString($field, $output);
        }
        $this->assertStringNotContainsString('missing_seo', $output);
        $this->assertStringNotContainsString('unknown_status', $output);
    }

    /** @param array<string, mixed> $options */
    private function runImport(array $options = []): void
    {
        $this->assertSame(0, Artisan::call('import:wordpress', ['--only' => ['posts'], ...$options]));
    }

    private function insertPost(int $id, string $title = 'Pilot post', string $slug = '', int $author = 0, string $status = 'publish'): void
    {
        $date = sprintf('2024-01-%02d 10:00:00', (($id - 1) % 28) + 1);
        $this->wordpress->table('wp_posts')->insert([
            'ID' => $id, 'post_author' => $author, 'post_date' => $date, 'post_date_gmt' => $date,
            'post_content' => '<p>Exact&nbsp;<strong>HTML</strong></p>', 'post_title' => "{$title} {$id}",
            'post_excerpt' => 'Pilot excerpt', 'post_status' => $status,
            'post_name' => $slug ?: "pilot-post-{$id}", 'post_modified' => $date,
            'post_modified_gmt' => $date, 'guid' => "https://old.example.com/?p={$id}", 'post_type' => 'post',
        ]);
    }

    private function insertTerm(int $id, string $name, string $slug, string $taxonomy): void
    {
        $this->wordpress->table('wp_terms')->insert(['term_id' => $id, 'name' => $name, 'slug' => $slug]);
        $this->wordpress->table('wp_term_taxonomy')->insert([
            'term_taxonomy_id' => $id, 'term_id' => $id, 'taxonomy' => $taxonomy, 'description' => null, 'parent' => 0,
        ]);
    }

    private function relate(int $postId, int $taxonomyId): void
    {
        $this->wordpress->table('wp_term_relationships')->insert(['object_id' => $postId, 'term_taxonomy_id' => $taxonomyId]);
    }

    private function meta(int $postId, string $key, string $value): void
    {
        $this->wordpress->table('wp_postmeta')->insert(['post_id' => $postId, 'meta_key' => $key, 'meta_value' => $value]);
    }
}
