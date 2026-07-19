<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_metadata_uses_priority_taxonomies_author_and_extended_robots(): void
    {
        $author = User::factory()->create(['name' => 'Editorial Author']);
        $category = Category::factory()->create(['name' => 'Punjab']);
        $tag = Tag::factory()->create(['name' => 'Politics']);
        $post = Post::factory()->published()->create([
            'author_id' => $author->id,
            'title' => 'Article title fallback',
            'meta_title' => 'Custom SEO title',
            'meta_description' => 'Custom SEO description.',
            'focus_keyword' => 'Punjab, politics, Punjab',
            'seo_data' => ['robots' => ['index' => false, 'follow' => false], 'noarchive' => true, 'nosnippet' => true],
        ]);
        $post->categories()->attach($category, ['is_primary' => true]);
        $post->tags()->attach($tag);

        $html = $this->get($post->publicUrl())->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<title>'));
        $this->assertSame(1, substr_count($html, '<meta name="description"'));
        $this->assertSame(1, substr_count($html, '<link rel="canonical"'));
        $this->assertStringContainsString('<title>Custom SEO title</title>', $html);
        $this->assertStringContainsString('name="description" content="Custom SEO description."', $html);
        $this->assertStringContainsString('name="author" content="Editorial Author"', $html);
        $this->assertStringContainsString('name="robots" content="noindex, nofollow, noarchive, nosnippet"', $html);
        $this->assertStringContainsString('name="keywords" content="Punjab, politics"', $html);
        $this->assertStringContainsString('property="og:type" content="article"', $html);
    }

    public function test_home_and_paginated_archive_canonicals_are_self_referencing_without_duplicates(): void
    {
        $category = Category::factory()->create(['name' => 'National', 'slug' => 'national']);
        $posts = Post::factory()->count(13)->published()->create();
        foreach ($posts as $post) {
            $post->categories()->attach($category, ['is_primary' => true]);
        }

        $home = $this->get('/?page=2')->assertOk()->getContent();
        $archive = $this->get(route('categories.show', $category->slug).'?page=2')->assertOk()->getContent();

        $this->assertSame(1, substr_count($home, '<link rel="canonical"'));
        $this->assertStringContainsString('href="'.route('home').'?page=2"', $home);
        $this->assertSame(1, substr_count($archive, '<link rel="canonical"'));
        $this->assertStringContainsString('href="'.route('categories.show', $category->slug).'?page=2"', $archive);
    }

    public function test_tag_author_search_and_static_pages_all_render_native_metadata(): void
    {
        $author = User::factory()->create(['name' => 'Public Journalist']);
        $tag = Tag::factory()->create(['name' => 'Election', 'slug' => 'election']);
        $post = Post::factory()->published()->create(['author_id' => $author->id, 'title' => 'Election coverage']);
        $post->tags()->attach($tag);

        foreach ([
            route('tags.show', $tag->slug),
            route('authors.show', $author->username),
            route('search', ['q' => 'Election']),
            route('pages.about'),
        ] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, '<title>'));
            $this->assertSame(1, substr_count($html, '<meta name="description"'));
            $this->assertSame(1, substr_count($html, '<meta name="keywords"'));
            $this->assertSame(1, substr_count($html, '<meta name="author"'));
            $this->assertSame(1, substr_count($html, '<meta name="robots"'));
            $this->assertSame(1, substr_count($html, '<link rel="canonical"'));
        }
    }
}
