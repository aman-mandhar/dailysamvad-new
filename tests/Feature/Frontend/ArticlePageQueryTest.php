<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Post;
use App\Queries\ArticlePageQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArticlePageQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_contract_has_loaded_article_and_card_relationships(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->published()->create();
        $related = Post::factory()->published()->create();
        $post->categories()->attach($category, ['is_primary' => true]);
        $related->categories()->attach($category, ['is_primary' => true]);

        $article = app(ArticlePageQuery::class)->find($post->slug);

        $this->assertTrue($article->post->relationLoaded('author'));
        $this->assertTrue($article->post->relationLoaded('primaryCategory'));
        $this->assertTrue($article->post->relationLoaded('categories'));
        $this->assertTrue($article->post->relationLoaded('tags'));
        $this->assertTrue($article->relatedPosts->every->relationLoaded('primaryCategory'));

        DB::enableQueryLog();
        $article->post->author;
        $article->post->primaryCategory->first();
        $article->post->categories->count();
        $article->post->tags->count();
        $article->relatedPosts->each(fn (Post $item) => $item->primaryCategory->first());
        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_related_news_prefers_category_then_fills_without_duplicates(): void
    {
        config()->set('article.related_limit', 3);
        $category = Category::factory()->create();
        $post = Post::factory()->published()->create();
        $sameCategory = Post::factory()->published()->create();
        $fallbacks = Post::factory()->published()->count(2)->create();
        $post->categories()->attach($category, ['is_primary' => true]);
        $sameCategory->categories()->attach($category, ['is_primary' => true]);

        $related = app(ArticlePageQuery::class)->find($post->slug)->relatedPosts;

        $this->assertCount(3, $related);
        $this->assertSame($sameCategory->id, $related->first()->id);
        $this->assertNotContains($post->id, $related->modelKeys());
        $this->assertCount(3, array_unique($related->modelKeys()));
        $this->assertTrue($fallbacks->pluck('id')->intersect($related->modelKeys())->isNotEmpty());
    }
}
