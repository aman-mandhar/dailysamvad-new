<?php

namespace Tests\Feature\Import;

use App\Import\Services\ImportReportStore;
use App\Import\Services\RedirectGenerator;
use App\Import\Services\WordPressConnection;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoRedirectVerificationTest extends TestCase
{
    use RefreshDatabase;

    private Connection $wordpress;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        config()->set('import.profiles.wordpress.database', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]);
        config()->set('import.profiles.wordpress.table_prefix', 'wp_');
        config()->set('import.profiles.wordpress.site_url', 'https://old.example.com');
        config()->set('import.reports.disk', 'local');
        config()->set('import.redirects.disk', 'local');
        config()->set('import.checkpoint.disk', 'local');

        $this->wordpress = app(WordPressConnection::class)->connection();
        $schema = $this->wordpress->getSchemaBuilder();
        $schema->create('wp_posts', function ($table): void {
            $table->unsignedBigInteger('ID')->primary();
            $table->unsignedBigInteger('post_author')->default(0);
            $table->string('post_type');
            $table->string('post_status')->default('publish');
            $table->string('post_title')->nullable();
            $table->text('post_excerpt')->nullable();
            $table->string('post_name')->nullable();
            $table->longText('post_content')->nullable();
            $table->dateTime('post_date')->nullable();
            $table->dateTime('post_date_gmt')->nullable();
            $table->text('guid')->nullable();
        });
        $schema->create('wp_postmeta', function ($table): void {
            $table->increments('meta_id');
            $table->unsignedBigInteger('post_id');
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
        });
        $schema->create('wp_term_taxonomy', function ($table): void {
            $table->unsignedBigInteger('term_taxonomy_id')->primary();
            $table->unsignedBigInteger('term_id');
            $table->string('taxonomy');
        });
        $schema->create('wp_term_relationships', function ($table): void {
            $table->unsignedBigInteger('object_id');
            $table->unsignedBigInteger('term_taxonomy_id');
        });
    }

    public function test_seo_metadata_is_mapped_without_overwriting_existing_laravel_values(): void
    {
        $post = Post::factory()->create(['old_wp_id' => 10, 'meta_title' => 'Editorial override', 'seo_data' => null]);
        $this->sourcePost(10);
        $this->meta(10, '_yoast_wpseo_title', 'Imported title');
        $this->meta(10, '_yoast_wpseo_metadesc', 'Imported description');
        $this->meta(10, '_yoast_wpseo_focuskw', 'daily news');
        $this->meta(10, '_yoast_wpseo_canonical', 'HTTPS://OLD.EXAMPLE.COM//canonical/');
        $this->meta(10, '_yoast_wpseo_meta-robots-noindex', '1');
        $this->meta(10, '_yoast_wpseo_opengraph-title', 'OpenGraph title');
        $this->meta(10, '_yoast_wpseo_twitter-description', 'Twitter description');

        $this->assertSame(0, Artisan::call('import:wordpress', ['--only' => ['seo']]));

        $post->refresh();
        $this->assertSame('Editorial override', $post->meta_title);
        $this->assertSame('Imported description', $post->meta_description);
        $this->assertSame('daily news', $post->focus_keyword);
        $this->assertSame(['index' => false, 'follow' => true], $post->seo_data['robots']);
        $this->assertSame('OpenGraph title', $post->seo_data['open_graph']['title']);
        $this->assertSame('Twitter description', $post->seo_data['twitter']['description']);
        $this->assertSame('WordPress', $post->source_name);
    }

    public function test_latest_seo_selection_matches_the_latest_imported_post_batch(): void
    {
        foreach ([10, 20, 30] as $id) {
            Post::factory()->create(['old_wp_id' => $id, 'meta_title' => null]);
            $this->sourcePost($id);
            $this->wordpress->table('wp_posts')->where('ID', $id)->update(['post_date' => "2024-01-{$id} 10:00:00"]);
            $this->meta($id, '_yoast_wpseo_title', "SEO {$id}");
        }

        $this->assertSame(0, Artisan::call('import:wordpress', [
            '--only' => ['seo'], '--limit' => 2, '--order' => 'latest',
        ]));

        $this->assertNull(Post::query()->where('old_wp_id', 10)->firstOrFail()->meta_title);
        $this->assertSame('SEO 20', Post::query()->where('old_wp_id', 20)->firstOrFail()->meta_title);
        $this->assertSame('SEO 30', Post::query()->where('old_wp_id', 30)->firstOrFail()->meta_title);
        $this->assertStringContainsString('0', Artisan::output());
    }

    public function test_redirects_are_normalized_deduplicated_and_exported_in_every_format(): void
    {
        Post::factory()->create(['old_wp_id' => 1, 'slug' => 'first', 'old_url' => 'https://old.example.com//news/first/']);
        Post::factory()->create(['old_wp_id' => 2, 'slug' => 'second', 'old_url' => '/news/first']);

        $generator = app(RedirectGenerator::class);
        $generated = $generator->generate();
        $paths = $generator->export();

        $this->assertCount(1, $generated['redirects']);
        $this->assertSame(1, $generated['duplicates']);
        $this->assertSame('/news/first', $generated['redirects'][0]['old_url']);
        foreach ($paths as $path) {
            Storage::disk('local')->assertExists($path);
        }
    }

    public function test_verification_command_generates_content_and_redirect_report(): void
    {
        $author = User::factory()->create(['old_wp_id' => 5]);
        $category = Category::factory()->create(['old_wp_id' => 20]);
        $tag = Tag::factory()->create(['old_wp_id' => 30]);
        $post = Post::factory()->published()->create([
            'old_wp_id' => 10, 'author_id' => $author->id, 'title' => 'Source title', 'slug' => 'source-title',
            'content' => '<p>Source content</p>', 'published_at' => '2024-01-01 10:00:00',
            'old_url' => 'https://old.example.com/source-title/', 'meta_title' => 'SEO',
            'featured_image' => 'wordpress/uploads/photo.png',
        ]);
        $post->categories()->attach($category, ['is_primary' => true]);
        $post->tags()->attach($tag);
        Storage::disk('public')->put('wordpress/uploads/photo.png', 'image');
        $this->sourcePost(10, author: 5);
        $this->wordpress->table('wp_term_taxonomy')->insert([
            ['term_taxonomy_id' => 20, 'term_id' => 20, 'taxonomy' => 'category'],
            ['term_taxonomy_id' => 30, 'term_id' => 30, 'taxonomy' => 'post_tag'],
        ]);
        $this->wordpress->table('wp_term_relationships')->insert([
            ['object_id' => 10, 'term_taxonomy_id' => 20],
            ['object_id' => 10, 'term_taxonomy_id' => 30],
        ]);

        $this->assertSame(0, Artisan::call('import:verify'));

        Storage::disk('local')->assertExists('imports/reports/verification-latest.json');
        $report = app(ImportReportStore::class)->read('verification-latest');
        $this->assertSame(1, $report['summary']['imported']);
        $this->assertSame(0, $report['summary']['failed']);
        $this->assertSame(1, $report['redirects']['generated']);
    }

    public function test_import_dashboard_loads_for_authorized_administrator(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('super-admin');
        app(ImportReportStore::class)->recordRun([
            'statistics' => ['imported' => 10, 'updated' => 2, 'skipped' => 1, 'failed' => 0],
            'importers' => ['posts'], 'mode' => 'live', 'resume' => false, 'dry_run' => false,
            'completed_at' => now()->toIso8601String(),
        ]);

        $this->actingAs($user)->get(route('filament.admin.pages.import-dashboard'))
            ->assertOk()->assertSee('Latest import')->assertSee('10');
    }

    private function sourcePost(int $id, int $author = 0): void
    {
        $this->wordpress->table('wp_posts')->insert([
            'ID' => $id, 'post_author' => $author, 'post_type' => 'post', 'post_title' => 'Source title',
            'post_name' => 'source-title', 'post_content' => '<p>Source content</p>',
            'post_date' => '2024-01-01 10:00:00', 'post_date_gmt' => '2024-01-01 10:00:00',
            'guid' => "https://old.example.com/?p={$id}",
        ]);
    }

    private function meta(int $postId, string $key, string $value): void
    {
        $this->wordpress->table('wp_postmeta')->insert(['post_id' => $postId, 'meta_key' => $key, 'meta_value' => $value]);
    }
}
