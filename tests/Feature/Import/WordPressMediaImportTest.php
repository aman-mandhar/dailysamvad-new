<?php

namespace Tests\Feature\Import;

use App\Import\Services\WordPressConnection;
use App\Models\Post;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WordPressMediaImportTest extends TestCase
{
    use RefreshDatabase;

    private Connection $wordpress;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('wordpress-source');
        Storage::fake('public');
        Storage::fake('local');
        config()->set('import.profiles.wordpress.database', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        config()->set('import.profiles.wordpress.table_prefix', 'wp_');
        config()->set('import.media.source_disk', 'wordpress-source');
        config()->set('import.media.destination_disk', 'public');
        config()->set('import.media.destination_path', 'wordpress/uploads');
        config()->set('import.checkpoint.disk', 'local');
        config()->set('import.dry_run', false);
        config()->set('import.resume', false);

        $this->wordpress = app(WordPressConnection::class)->connection();
        $schema = $this->wordpress->getSchemaBuilder();
        $schema->create('wp_posts', function ($table): void {
            $table->unsignedBigInteger('ID')->primary();
            $table->string('post_type');
            $table->string('post_mime_type')->nullable();
            $table->text('post_excerpt')->nullable();
        });
        $schema->create('wp_postmeta', function ($table): void {
            $table->increments('meta_id');
            $table->unsignedBigInteger('post_id');
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
        });
    }

    public function test_featured_image_is_copied_and_mapped_to_an_imported_post(): void
    {
        $post = Post::factory()->create(['old_wp_id' => 10, 'featured_image' => null]);
        $this->insertAttachment(100, '2024/01/photo.png');
        $this->meta(10, '_thumbnail_id', '100');
        $this->meta(100, '_wp_attachment_image_alt', 'Press conference');
        Storage::disk('wordpress-source')->put('2024/01/photo.png', $this->png());

        $this->runImport();

        $path = 'wordpress/uploads/2024/01/photo.png';
        Storage::disk('public')->assertExists($path);
        $this->assertSame($path, $post->refresh()->featured_image);
        $this->assertSame('Press conference', $post->featured_image_alt);
        $this->assertSame('Imported caption', $post->featured_image_caption);
    }

    public function test_duplicate_run_skips_unchanged_file_without_creating_another_copy(): void
    {
        $this->insertAttachment(100, '2024/01/photo.png');
        Storage::disk('wordpress-source')->put('2024/01/photo.png', $this->png());
        $this->runImport();
        $this->runImport();

        $this->assertCount(1, Storage::disk('public')->allFiles('wordpress/uploads'));
        $this->assertStringContainsString('1', Artisan::output());
    }

    public function test_resume_continues_after_the_last_media_checkpoint(): void
    {
        $this->insertAttachment(100, '2024/01/one.png');
        $this->insertAttachment(101, '2024/01/two.png');
        Storage::disk('wordpress-source')->put('2024/01/one.png', $this->png());
        Storage::disk('wordpress-source')->put('2024/01/two.png', $this->png());

        $this->runImport(['--limit' => 1, '--chunk' => 1]);
        $this->runImport(['--limit' => 2, '--chunk' => 1, '--resume' => true]);

        Storage::disk('public')->assertExists('wordpress/uploads/2024/01/one.png');
        Storage::disk('public')->assertExists('wordpress/uploads/2024/01/two.png');
    }

    public function test_dry_run_does_not_copy_or_update_featured_image(): void
    {
        $post = Post::factory()->create(['old_wp_id' => 10, 'featured_image' => null]);
        $this->insertAttachment(100, '2024/01/photo.png');
        $this->meta(10, '_thumbnail_id', '100');
        Storage::disk('wordpress-source')->put('2024/01/photo.png', $this->png());

        $this->runImport(['--dry-run' => true]);

        Storage::disk('public')->assertMissing('wordpress/uploads/2024/01/photo.png');
        $this->assertNull($post->refresh()->featured_image);
        Storage::disk('local')->assertMissing('imports/checkpoints/wordpress/media.all.offset-0.json');
    }

    public function test_missing_file_is_reported_without_stopping_later_files(): void
    {
        $this->insertAttachment(100, '2024/01/missing.png');
        $this->insertAttachment(101, '2024/01/present.png');
        Storage::disk('wordpress-source')->put('2024/01/present.png', $this->png());

        $this->runImport();

        Storage::disk('public')->assertExists('wordpress/uploads/2024/01/present.png');
        $this->assertStringContainsString('missing', strtolower(Artisan::output()));
    }

    public function test_unsupported_mime_type_is_reported_and_not_copied(): void
    {
        $this->insertAttachment(100, '2024/01/script.txt', 'text/plain');
        Storage::disk('wordpress-source')->put('2024/01/script.txt', 'plain text');

        $this->runImport();

        Storage::disk('public')->assertMissing('wordpress/uploads/2024/01/script.txt');
        $this->assertStringContainsString('unsupported', strtolower(Artisan::output()));
    }

    /** @param array<string, mixed> $options */
    private function runImport(array $options = []): void
    {
        $this->assertSame(0, Artisan::call('import:wordpress', ['--only' => ['media'], ...$options]));
    }

    private function insertAttachment(int $id, string $path, string $mimeType = 'image/png'): void
    {
        $this->wordpress->table('wp_posts')->insert([
            'ID' => $id, 'post_type' => 'attachment', 'post_mime_type' => $mimeType,
            'post_excerpt' => 'Imported caption',
        ]);
        $this->meta($id, '_wp_attached_file', $path);
    }

    private function meta(int $postId, string $key, string $value): void
    {
        $this->wordpress->table('wp_postmeta')->insert(['post_id' => $postId, 'meta_key' => $key, 'meta_value' => $value]);
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z4x8AAAAASUVORK5CYII=', true);
    }
}
