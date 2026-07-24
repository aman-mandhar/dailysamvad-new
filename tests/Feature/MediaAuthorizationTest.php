<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MediaAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manage_media_permission_controls_library_and_referenced_delete_is_denied(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $editor = User::factory()->create();
        $reviewer = User::factory()->create();
        $editor->assignRole('editor');
        $reviewer->assignRole('reviewer');
        $media = Media::query()->create(['disk' => 'public', 'path' => 'media/image.jpg']);

        $this->assertTrue(Gate::forUser($editor)->allows('viewAny', Media::class));
        $this->assertTrue(Gate::forUser($editor)->allows('create', Media::class));
        $this->assertTrue(Gate::forUser($editor)->allows('update', $media));
        $this->assertTrue(Gate::forUser($reviewer)->allows('viewAny', Media::class));
        $this->assertFalse(Gate::forUser($reviewer)->allows('create', Media::class));

        Post::factory()->create(['featured_media_id' => $media->id, 'featured_image' => $media->path]);
        $this->assertFalse(Gate::forUser($editor)->allows('delete', $media));
        $this->assertFalse(Gate::forUser($editor)->allows('forceDelete', $media));
    }
}
