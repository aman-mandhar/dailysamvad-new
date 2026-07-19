<?php

namespace Tests\Feature\Import;

use App\Import\Contracts\Logger;
use App\Import\Services\WordPressConnection;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class WordPressTaxonomyImportTest extends TestCase
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
        config()->set('import.checkpoint.disk', 'local');
        config()->set('import.dry_run', false);
        config()->set('import.resume', false);

        $this->wordpress = app(WordPressConnection::class)->connection();
        $schema = $this->wordpress->getSchemaBuilder();
        $schema->create('wp_users', function ($table): void {
            $table->unsignedBigInteger('ID')->primary();
            $table->string('user_login')->nullable();
            $table->string('user_nicename')->nullable();
            $table->string('user_email')->nullable();
            $table->string('display_name')->nullable();
            $table->dateTime('user_registered')->nullable();
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
        $schema->create('wp_term_relationships', function ($table): void {
            $table->unsignedBigInteger('object_id');
            $table->unsignedBigInteger('term_taxonomy_id');
        });
    }

    public function test_existing_user_email_is_linked_without_duplication(): void
    {
        $user = User::factory()->create(['email' => 'editor@example.com', 'old_wp_id' => null, 'slug' => null]);
        $this->insertUser(10, 'wp-editor', 'editor@example.com');

        $this->runImport(['--only' => ['users']]);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame(10, $user->refresh()->old_wp_id);
        $this->assertSame('wp-editor', $user->slug);
    }

    public function test_duplicate_category_slug_is_linked_and_rerun_is_idempotent(): void
    {
        Category::factory()->create(['slug' => 'politics', 'old_wp_id' => null]);
        $this->insertTerm(10, 'Politics', 'politics', 'category');

        $this->runImport(['--only' => ['categories']]);
        $this->runImport(['--only' => ['categories']]);

        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseHas('categories', ['slug' => 'politics', 'old_wp_id' => 10]);
    }

    public function test_category_hierarchy_is_linked_in_second_pass(): void
    {
        $this->insertTerm(20, 'Child', 'child', 'category', 10);
        $this->insertTerm(10, 'Parent', 'parent', 'category');

        $this->runImport(['--only' => ['categories'], '--chunk' => 1]);

        $parent = Category::query()->where('old_wp_id', 10)->firstOrFail();
        $this->assertTrue(Category::query()->where('old_wp_id', 20)->firstOrFail()->parent->is($parent));
    }

    public function test_tags_are_idempotent(): void
    {
        $this->insertTerm(30, 'Laravel', 'laravel', 'post_tag');
        $this->runImport(['--only' => ['tags']]);
        $this->runImport(['--only' => ['tags']]);

        $this->assertSame(1, Tag::query()->count());
    }

    public function test_tag_import_reuses_matching_name_without_replacing_existing_slug(): void
    {
        $existing = Tag::factory()->create([
            'old_wp_id' => null,
            'name' => 'Punjab Politics',
            'slug' => 'editorial-punjab-politics',
        ]);
        $this->insertTerm(31, 'Punjab Politics', 'wordpress-punjab-politics', 'post_tag');

        $this->runImport(['--only' => ['tags']]);
        $this->runImport(['--only' => ['tags']]);

        $this->assertDatabaseCount('tags', 1);
        $this->assertSame(31, $existing->refresh()->old_wp_id);
        $this->assertSame('editorial-punjab-politics', $existing->slug);
    }

    public function test_resume_continues_after_the_last_checkpoint(): void
    {
        $this->insertUser(1, 'one', 'one@example.com');
        $this->insertUser(2, 'two', 'two@example.com');
        $this->runImport(['--only' => ['users'], '--limit' => 1]);
        $this->runImport(['--only' => ['users'], '--resume' => true]);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_dry_run_writes_no_records_or_checkpoints(): void
    {
        $this->insertUser(1, 'one', 'one@example.com');
        $this->insertTerm(10, 'News', 'news', 'category');

        $this->runImport(['--dry-run' => true, '--only' => ['users', 'categories', 'tags']]);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('categories', 0);
        Storage::disk('local')->assertMissing('imports/checkpoints/wordpress/users.json');
    }

    public function test_failed_chunk_rolls_back_without_rolling_back_completed_chunks(): void
    {
        $this->insertUser(1, 'one', 'one@example.com');
        $this->insertUser(2, 'two', 'two@example.com');
        $this->insertUser(3, 'three', 'three@example.com');
        $this->insertUser(4, '', 'invalid');

        $logger = Mockery::mock(Logger::class);
        $logger->shouldReceive('info')->zeroOrMoreTimes();
        $logger->shouldReceive('success')->zeroOrMoreTimes();
        $logger->shouldReceive('error')->zeroOrMoreTimes();
        $logger->shouldReceive('warning')->andThrow(new RuntimeException('Simulated chunk failure'));
        $this->app->instance(Logger::class, $logger);

        try {
            $this->runImport(['--only' => ['users'], '--chunk' => 2]);
            $this->fail('The simulated chunk failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated chunk failure', $exception->getMessage());
        }

        $this->assertDatabaseHas('users', ['old_wp_id' => 1]);
        $this->assertDatabaseHas('users', ['old_wp_id' => 2]);
        $this->assertDatabaseMissing('users', ['old_wp_id' => 3]);
    }

    /** @param array<string, mixed> $options */
    private function runImport(array $options): void
    {
        $this->assertSame(0, Artisan::call('import:wordpress', $options));
    }

    private function insertUser(int $id, string $login, string $email): void
    {
        $this->wordpress->table('wp_users')->insert([
            'ID' => $id, 'user_login' => $login, 'user_email' => $email,
            'user_nicename' => $login, 'display_name' => ucfirst($login),
            'user_registered' => '2020-01-01 12:00:00',
        ]);
    }

    private function insertTerm(int $id, string $name, string $slug, string $taxonomy, int $parent = 0): void
    {
        $this->wordpress->table('wp_terms')->insert(['term_id' => $id, 'name' => $name, 'slug' => $slug]);
        $this->wordpress->table('wp_term_taxonomy')->insert([
            'term_taxonomy_id' => $id, 'term_id' => $id, 'taxonomy' => $taxonomy,
            'description' => "{$name} description", 'parent' => $parent,
        ]);
    }
}
