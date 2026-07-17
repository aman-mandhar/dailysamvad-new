<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchivePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_archive_loads_and_hides_drafts(): void
    {
        $category = Category::factory()->create(['name' => 'Punjab']);
        $published = Post::factory()->published()->create(['title' => 'Published category story']);
        $draft = Post::factory()->create(['title' => 'Draft category story']);
        $category->posts()->attach([$published->id, $draft->id]);

        $this->get(route('categories.show', $category->slug))
            ->assertOk()->assertSee('Punjab')->assertSee($published->title)->assertDontSee($draft->title);
    }

    public function test_tag_archive_loads(): void
    {
        $tag = Tag::factory()->create(['name' => 'Politics']);
        $post = Post::factory()->published()->create();
        $tag->posts()->attach($post);

        $this->get(route('tags.show', $tag->slug))->assertOk()->assertSee('Politics')->assertSee($post->title);
    }

    public function test_author_archive_loads_only_published_posts(): void
    {
        $author = User::factory()->create(['username' => 'reporter-one']);
        $published = Post::factory()->published()->create(['author_id' => $author->id]);
        $draft = Post::factory()->create(['author_id' => $author->id]);

        $this->get(route('authors.show', $author->username))
            ->assertOk()->assertSee($author->name)->assertSee($published->title)->assertDontSee($draft->title);
    }

    public function test_archive_pagination_works(): void
    {
        $category = Category::factory()->create();
        $posts = Post::factory()->count(13)->published()->create();
        $category->posts()->attach($posts);

        $this->get(route('categories.show', ['slug' => $category->slug, 'page' => 2]))
            ->assertOk()
            ->assertViewHas('archive', fn ($archive): bool => $archive->posts->currentPage() === 2 && $archive->posts->count() === 1);
    }

    public function test_missing_archives_return_not_found(): void
    {
        $this->get('/category/missing')->assertNotFound();
        $this->get('/tag/missing')->assertNotFound();
        $this->get('/author/missing')->assertNotFound();
    }

    public function test_archive_breadcrumb_and_collection_schema_render(): void
    {
        $tag = Tag::factory()->create(['name' => 'Sports']);

        $this->get(route('tags.show', $tag->slug))
            ->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSeeInOrder(['Home', 'Tag', 'Sports'])
            ->assertSee('CollectionPage');
    }
}
