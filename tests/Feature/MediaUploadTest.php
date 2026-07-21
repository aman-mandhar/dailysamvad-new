<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use App\Services\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_valid_upload_uses_safe_path_real_metadata_and_unicode_text(): void
    {
        $user = User::factory()->create();
        $media = app(MediaUploadService::class)->store(
            UploadedFile::fake()->image('../Unsafe समाचार.JPG', 640, 360),
            $user->id,
            ['alt_text' => 'मुख्य समाचार', 'caption' => 'ਪੰਜਾਬੀ ਕੈਪਸ਼ਨ'],
        );

        $this->assertMatchesRegularExpression('#^media/library/\d{4}/\d{2}/[0-9a-f-]+\.jpg$#', $media->path);
        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertSame([640, 360], [$media->width, $media->height]);
        $this->assertSame('मुख्य समाचार', $media->alt_text);
        $this->assertSame('ਪੰਜਾਬੀ ਕੈਪਸ਼ਨ', $media->caption);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_duplicate_binary_reuses_record_without_second_file(): void
    {
        $service = app(MediaUploadService::class);
        $first = $service->store(UploadedFile::fake()->image('one.png'));
        $second = $service->store(UploadedFile::fake()->image('two.png'));

        $this->assertTrue($first->is($second));
        $this->assertCount(1, Storage::disk('public')->allFiles('media/library'));
    }

    public function test_duplicate_binary_does_not_reuse_another_users_owned_media_record(): void
    {
        $service = app(MediaUploadService::class);
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $first = $service->store(UploadedFile::fake()->image('one.png'), $firstUser->id);
        $second = $service->store(UploadedFile::fake()->image('two.png'), $secondUser->id);

        $this->assertFalse($first->is($second));
        $this->assertSame($firstUser->id, $first->uploaded_by);
        $this->assertSame($secondUser->id, $second->uploaded_by);
        $this->assertCount(2, Storage::disk('public')->allFiles('media/library'));
    }

    public function test_disguised_executable_and_empty_file_are_rejected(): void
    {
        foreach ([
            UploadedFile::fake()->createWithContent('shell.jpg', '<?php echo "unsafe";'),
            UploadedFile::fake()->createWithContent('empty.png', ''),
        ] as $upload) {
            try {
                app(MediaUploadService::class)->store($upload);
                $this->fail('Invalid upload was accepted.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('media', 0);
            }
        }
        $this->assertSame([], Storage::disk('public')->allFiles('media/library'));
    }

    public function test_database_failure_after_write_removes_new_binary(): void
    {
        Event::listen('eloquent.creating: '.Media::class, fn () => throw new RuntimeException('simulated database failure'));

        try {
            app(MediaUploadService::class)->store(UploadedFile::fake()->image('cleanup.jpg'));
            $this->fail('Expected database failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('simulated database failure', $exception->getMessage());
        }

        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('media/library'));
    }
}
