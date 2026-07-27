<?php

namespace Tests\Feature\Filament;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Observers\PostObserver;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PostFeaturedImageTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $this->editor = User::factory()->create();
        $this->editor->assignRole('editor');
        $this->category = Category::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_featured_image_can_be_uploaded(): void
    {
        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm($this->postData([
                'featured_image' => UploadedFile::fake()->image('Press Conference.jpg', 1200, 675),
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'featured-image-story')->firstOrFail();

        $this->assertStringStartsWith('posts/featured/', $post->featured_image);
        $this->assertStringContainsString('press-conference', $post->featured_image);
        $this->assertNotSame('posts/featured/Press Conference.jpg', $post->featured_image);
        Storage::disk('public')->assertExists($post->featured_image);
    }

    public function test_replacing_featured_image_deletes_the_old_file(): void
    {
        $post = $this->postWithFeaturedImage('posts/featured/old-image.jpg');

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'featured_image' => [UploadedFile::fake()->image('Replacement Image.png', 1200, 675)],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $newPath = $post->refresh()->featured_image;

        $this->assertNotSame('posts/featured/old-image.jpg', $newPath);
        Storage::disk('public')->assertMissing('posts/featured/old-image.jpg');
        Storage::disk('public')->assertExists($newPath);
        $this->assertStringContainsString('replacement-image', $newPath);
    }

    public function test_featured_image_can_be_removed(): void
    {
        $post = $this->postWithFeaturedImage('posts/featured/remove-me.jpg');

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->set('data.featured_image', null)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($post->refresh()->featured_image);
        Storage::disk('public')->assertMissing('posts/featured/remove-me.jpg');
    }

    public function test_existing_media_can_be_selected_and_detached_without_deleting_binary(): void
    {
        $media = Media::query()->create([
            'disk' => 'public', 'path' => 'media/library/selectable.jpg', 'mime_type' => 'image/jpeg',
        ]);
        Storage::disk('public')->put($media->path, 'image');
        $post = $this->postWithFeaturedImage('posts/featured/previous.jpg');

        Livewire::actingAs($this->editor)->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->set('data.featured_media_id', $media->id)->call('save')->assertHasNoFormErrors();

        $this->assertSame($media->id, $post->refresh()->featured_media_id);
        $this->assertSame($media->path, $post->featured_image);

        Livewire::actingAs($this->editor)->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->set('data.featured_media_id', null)->call('save')->assertHasNoFormErrors();

        $this->assertNull($post->refresh()->featured_media_id);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm($this->postData([
                'featured_image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ]))
            ->call('create')
            ->assertHasFormErrors(['featured_image']);

        $this->assertDatabaseMissing('posts', ['slug' => 'featured-image-story']);
    }

    public function test_force_deleting_post_removes_featured_image(): void
    {
        $post = $this->postWithFeaturedImage('posts/featured/permanent.jpg');

        $post->forceDelete();

        Storage::disk('public')->assertMissing('posts/featured/permanent.jpg');
    }

    public function test_model_replacement_removes_previous_featured_image(): void
    {
        $post = $this->postWithFeaturedImage('posts/featured/model-old.jpg');
        Storage::disk('public')->put('posts/featured/model-new.jpg', 'new-image');

        $post->update(['featured_image' => 'posts/featured/model-new.jpg']);

        Storage::disk('public')->assertMissing('posts/featured/model-old.jpg');
        Storage::disk('public')->assertExists('posts/featured/model-new.jpg');
    }

    public function test_soft_deleting_post_preserves_featured_image(): void
    {
        $post = $this->postWithFeaturedImage('posts/featured/soft-delete.jpg');

        $post->delete();

        Storage::disk('public')->assertExists('posts/featured/soft-delete.jpg');
        $this->assertNotNull($post->fresh()->deleted_at);
    }

    public function test_replacing_one_shared_featured_image_preserves_the_binary(): void
    {
        $path = 'posts/featured/shared.jpg';
        $post = $this->postWithFeaturedImage($path);
        Post::factory()->create(['featured_image' => $path]);
        Storage::disk('public')->put('posts/featured/replacement.jpg', 'replacement');

        $post->update(['featured_image' => 'posts/featured/replacement.jpg']);

        Storage::disk('public')->assertExists($path);
    }

    public function test_media_owned_or_content_referenced_file_is_not_deleted(): void
    {
        $owned = 'posts/featured/media-owned.jpg';
        $referenced = 'posts/featured/content-reference.jpg';
        Storage::disk('public')->put($owned, 'owned');
        Storage::disk('public')->put($referenced, 'referenced');
        Media::query()->create(['disk' => 'public', 'path' => $owned]);
        Post::factory()->create(['content' => '<img src="/storage/'.$referenced.'">']);

        $this->assertFalse(PostObserver::deleteManagedImage($owned));
        $this->assertFalse(PostObserver::deleteManagedImage($referenced));
        Storage::disk('public')->assertExists($owned);
        Storage::disk('public')->assertExists($referenced);
    }

    private function postWithFeaturedImage(string $path): Post
    {
        Storage::disk('public')->put($path, 'image-content');

        $post = Post::factory()->create([
            'author_id' => $this->editor,
            'featured_image' => $path,
        ]);
        $post->categories()->attach($this->category, ['is_primary' => true]);

        return $post;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function postData(array $overrides = []): array
    {
        return [
            'title' => 'Featured Image Story',
            'slug' => 'featured-image-story',
            'excerpt' => 'A featured image test story.',
            'content' => '<p>Complete featured image test content.</p>',
            'language' => 'en',
            'author_id' => $this->editor->id,
            'status' => PostStatus::Draft->value,
            'categories' => [$this->category->id],
            'primary_category_id' => $this->category->id,
            'tags' => [],
            ...$overrides,
        ];
    }
}
