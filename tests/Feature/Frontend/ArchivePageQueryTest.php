<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Post;
use App\Queries\ArchivePageQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArchivePageQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_paginates_deterministically_and_eager_loads_card_relationships(): void
    {
        config()->set('archive.per_page', 2);
        $category = Category::factory()->create();
        $posts = Post::factory()->published()->count(3)->create();
        $category->posts()->attach($posts, ['is_primary' => true]);

        $archive = app(ArchivePageQuery::class)->forCategory($category->slug);

        $this->assertSame(2, $archive->posts->count());
        $this->assertTrue($archive->posts->getCollection()->every->relationLoaded('primaryCategory'));
        $this->assertSame($archive->posts[0]->published_at->greaterThanOrEqualTo($archive->posts[1]->published_at), true);
        $this->assertSame('category', $archive->contextType);
        $this->assertSame(route('categories.show', $category->slug), $archive->canonicalUrl);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $archive->posts->each(fn (Post $post) => $post->primaryCategory->first());
        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_second_page_has_current_page_canonical_and_no_duplicate_parameters(): void
    {
        config()->set('archive.per_page', 1);
        $category = Category::factory()->create();
        $category->posts()->attach(Post::factory()->published()->count(2)->create());

        $this->get(route('categories.show', ['slug' => $category->slug, 'page' => 2]))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('categories.show', $category->slug).'?page=2">', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('aria-label="Pagination"', false);
    }
}
