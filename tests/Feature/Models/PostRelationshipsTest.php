<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_can_be_attached_to_a_post(): void
    {
        $post = Post::factory()->create();
        $categories = Category::factory()->count(2)->create();

        $post->categories()->attach($categories->modelKeys());

        $this->assertEqualsCanonicalizing(
            $categories->modelKeys(),
            $post->categories()->pluck('categories.id')->all(),
        );
    }

    public function test_tags_can_be_attached_to_a_post(): void
    {
        $post = Post::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $post->tags()->attach($tags->modelKeys());

        $this->assertEqualsCanonicalizing(
            $tags->modelKeys(),
            $post->tags()->pluck('tags.id')->all(),
        );
    }

    public function test_duplicate_category_pivot_is_prevented(): void
    {
        $post = Post::factory()->create();
        $category = Category::factory()->create();
        $post->categories()->attach($category);

        $this->expectException(QueryException::class);

        $post->categories()->attach($category);
    }

    public function test_duplicate_tag_pivot_is_prevented(): void
    {
        $post = Post::factory()->create();
        $tag = Tag::factory()->create();
        $post->tags()->attach($tag);

        $this->expectException(QueryException::class);

        $post->tags()->attach($tag);
    }

    public function test_primary_category_returns_only_the_primary_category(): void
    {
        $post = Post::factory()->create();
        $primary = Category::factory()->create();
        $secondary = Category::factory()->create();

        $post->categories()->attach([
            $primary->id => ['is_primary' => true],
            $secondary->id => ['is_primary' => false],
        ]);

        $this->assertTrue($post->primaryCategory()->sole()->is($primary));
        $this->assertSame(1, (int) $post->categories()->findOrFail($primary->id)->pivot->is_primary);
        $this->assertSame(0, (int) $post->categories()->findOrFail($secondary->id)->pivot->is_primary);
    }

    public function test_many_to_many_relationships_work_from_both_sides(): void
    {
        $post = Post::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $post->categories()->attach($category, ['is_primary' => true]);
        $post->tags()->attach($tag);

        $this->assertTrue($post->categories->contains($category));
        $this->assertTrue($post->tags->contains($tag));
        $this->assertTrue($category->posts->contains($post));
        $this->assertTrue($tag->posts->contains($post));
    }

    public function test_post_relationships_are_compatible_with_eager_loading(): void
    {
        $post = Post::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $post->categories()->attach($category, ['is_primary' => true]);
        $post->tags()->attach($tag);

        $loadedPost = Post::query()
            ->with(['author', 'categories', 'tags', 'primaryCategory'])
            ->findOrFail($post->id);

        $this->assertTrue($loadedPost->relationLoaded('author'));
        $this->assertTrue($loadedPost->relationLoaded('categories'));
        $this->assertTrue($loadedPost->relationLoaded('tags'));
        $this->assertTrue($loadedPost->relationLoaded('primaryCategory'));
        $this->assertTrue($loadedPost->primaryCategory->contains($category));
    }

    public function test_deleting_categories_and_tags_cascades_their_pivot_rows(): void
    {
        $post = Post::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $post->categories()->attach($category);
        $post->tags()->attach($tag);

        $category->delete();
        $tag->delete();

        $this->assertDatabaseMissing('category_post', [
            'post_id' => $post->id,
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseMissing('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_force_deleting_a_post_cascades_all_pivot_rows(): void
    {
        $post = Post::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $post->categories()->attach($category);
        $post->tags()->attach($tag);

        $post->forceDelete();

        $this->assertDatabaseMissing('category_post', ['post_id' => $post->id]);
        $this->assertDatabaseMissing('post_tag', ['post_id' => $post->id]);
    }
}
