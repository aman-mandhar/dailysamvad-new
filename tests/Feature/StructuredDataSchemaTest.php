<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructuredDataSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_one_valid_connected_schema_graph(): void
    {
        config([
            'seo.publisher_type' => 'NewsMediaOrganization',
            'seo.publisher_logo' => '/images/seo/default-social.png',
            'organization.social_links' => [
                'facebook' => 'https://facebook.com/dailysamvad',
                'duplicate' => 'https://facebook.com/dailysamvad',
                'unsafe' => 'javascript:alert(1)',
            ],
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();
        $schema = $this->schema($html);
        $website = $this->node($schema, 'WebSite');
        $publisher = $this->node($schema, 'NewsMediaOrganization');

        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertCount(1, $this->nodes($schema, 'WebSite'));
        $this->assertCount(1, $this->nodes($schema, 'NewsMediaOrganization'));
        $this->assertSame('http://localhost', $publisher['url']);
        $this->assertSame('http://localhost/images/seo/default-social.png', $publisher['logo']['url']);
        $this->assertSame(['https://facebook.com/dailysamvad'], $publisher['sameAs']);
        $this->assertSame($publisher['@id'], $website['publisher']['@id']);
        $this->assertSame('SearchAction', $website['potentialAction']['@type']);
        $this->assertSame('http://localhost/search?q={search_term_string}', $website['potentialAction']['target']['urlTemplate']);
        $this->assertSame('required name=search_term_string', $website['potentialAction']['query-input']);
        $this->assertSame('en-IN', $website['inLanguage']);
        $this->assertCount(1, $this->scripts($html));
        $this->assertEmpty($this->nodes($schema, 'BreadcrumbList'));
        $this->assertSame(count($schema['@graph']), count(array_unique(array_column($schema['@graph'], '@id'))));
        $this->assertNoEmptyValues($schema);
    }

    public function test_invalid_logo_and_disabled_search_action_are_omitted(): void
    {
        config([
            'seo.publisher_logo' => 'file:///private/logo.png',
            'seo.site_logo' => null,
            'seo.schema_search_action' => false,
        ]);

        $schema = $this->schema($this->get('/')->assertOk()->getContent());

        $this->assertArrayNotHasKey('logo', $this->node($schema, 'NewsMediaOrganization'));
        $this->assertArrayNotHasKey('potentialAction', $this->node($schema, 'WebSite'));
    }

    public function test_published_news_article_schema_is_complete_clean_and_consistent(): void
    {
        config(['seo.publisher_logo' => '/images/seo/default-social.png']);
        $author = User::factory()->create([
            'name' => 'ਪੱਤਰਕਾਰ लेखक',
            'username' => 'public-reporter',
            'email' => 'private@example.test',
            'bio' => '<b>Public biography</b>',
            'designation' => 'Reporter',
            'avatar_path' => null,
            'x_url' => 'https://x.com/public_reporter',
            'is_public' => true,
        ]);
        $media = Media::query()->create([
            'disk' => 'public',
            'path' => 'wordpress/uploads/schema story.webp',
            'mime_type' => 'image/webp',
            'width' => 1200,
            'height' => 675,
            'alt_text' => 'Primary image',
        ]);
        $category = Category::factory()->create(['name' => '<b>Punjab</b>']);
        $tag = Tag::factory()->create(['name' => 'Politics']);
        $published = now()->subDay();
        $post = Post::factory()->published()->create([
            'author_id' => $author->id,
            'featured_media_id' => $media->id,
            'featured_image' => 'wordpress/uploads/schema story.webp',
            'title' => 'ਪੰਜਾਬ "News" & भारत </script><b>Update</b>',
            'meta_description' => '<p>Clean <strong>article</strong> description.</p><script>alert(1)</script>',
            'content' => '<p>Public article body.</p><script>secret()</script>[caption] More text.',
            'language' => 'pa-IN',
            'published_at' => $published,
            'updated_at' => $published->copy()->subHour(),
        ]);
        $post->categories()->attach($category, ['is_primary' => true]);
        $post->tags()->attach($tag);

        $html = $this->get($post->publicUrl())->assertOk()->getContent();
        $schema = $this->schema($html);
        $article = $this->node($schema, 'NewsArticle');
        $webPage = $this->node($schema, 'WebPage');
        $person = $this->node($schema, 'Person');
        $image = $this->node($schema, 'ImageObject', '#primaryimage');
        $breadcrumb = $this->node($schema, 'BreadcrumbList');

        $this->assertCount(1, $this->nodes($schema, 'NewsArticle'));
        $this->assertSame('ਪੰਜਾਬ "News" & भारत Update', $article['headline']);
        $this->assertSame('Clean article description.', $article['description']);
        $this->assertSame($post->publicUrl(), $article['url']);
        $this->assertSame($webPage['@id'], $article['mainEntityOfPage']['@id']);
        $this->assertSame($this->node($schema, 'NewsMediaOrganization')['@id'], $article['publisher']['@id']);
        $this->assertSame($person['@id'], $article['author']['@id']);
        $this->assertSame($published->toIso8601String(), $article['datePublished']);
        $this->assertSame($article['datePublished'], $article['dateModified']);
        $this->assertSame('Punjab', $article['articleSection']);
        $this->assertSame(['Punjab', 'Politics'], $article['keywords']);
        $this->assertSame('pa-IN', $article['inLanguage']);
        $this->assertSame('Public article body. More text.', $article['articleBody']);
        $this->assertSame($image['@id'], $article['image']['@id']);
        $this->assertSame('http://localhost/storage/wordpress/uploads/schema%20story.webp', $image['contentUrl']);
        $this->assertSame(1200, $image['width']);
        $this->assertSame(675, $image['height']);
        $this->assertSame([1, 2, 3], array_column($breadcrumb['itemListElement'], 'position'));
        $this->assertSame($post->publicUrl(), $breadcrumb['itemListElement'][2]['item']);
        $this->assertStringNotContainsString($author->email, $html);
        $this->assertStringNotContainsString('/admin', $this->scripts($html)[0]);
        $this->assertStringNotContainsString('</script><b>', $this->scripts($html)[0]);
        $this->assertCount(1, $this->scripts($html));
        $this->assertNoEmptyValues($schema);
    }

    public function test_news_article_uses_organization_fallback_and_rejects_filesystem_image(): void
    {
        $author = User::factory()->create(['is_public' => false]);
        $post = Post::factory()->published()->create([
            'author_id' => $author->id,
            'featured_image' => 'C:\\private\\story.jpg',
        ]);

        $schema = $this->schema($this->get($post->publicUrl())->assertOk()->getContent());
        $article = $this->node($schema, 'NewsArticle');

        $this->assertSame($this->node($schema, 'NewsMediaOrganization')['@id'], $article['author']['@id']);
        $this->assertStringNotContainsString('C:\\', json_encode($schema, JSON_THROW_ON_ERROR));
        $this->assertSame('http://localhost/images/seo/default-social.png', $this->node($schema, 'ImageObject', '#primaryimage')['url']);
    }

    public function test_archive_search_author_date_and_static_pages_use_accurate_page_types(): void
    {
        $author = User::factory()->create(['name' => 'Public Writer', 'bio' => 'Writer biography.', 'is_public' => true]);
        $category = Category::factory()->create(['slug' => 'national']);
        $tag = Tag::factory()->create(['slug' => 'election']);
        $post = Post::factory()->published()->create(['author_id' => $author->id, 'published_at' => now()]);
        $post->categories()->attach($category, ['is_primary' => true]);
        $post->tags()->attach($tag);

        $pages = [
            route('categories.show', $category->slug) => 'CollectionPage',
            route('tags.show', $tag->slug) => 'CollectionPage',
            route('authors.show', $author->username) => 'ProfilePage',
            route('archives.year', now()->year) => 'CollectionPage',
            route('search', ['q' => '<script>bad()</script> ਪੰਜਾਬ']) => 'SearchResultsPage',
            route('pages.about') => 'AboutPage',
            route('pages.contact') => 'ContactPage',
            route('pages.privacy') => 'WebPage',
        ];

        foreach ($pages as $url => $type) {
            $html = $this->get($url)->assertOk()->getContent();
            $schema = $this->schema($html);
            $page = $this->node($schema, $type);
            $expectedUrl = $type === 'SearchResultsPage' ? route('search', ['q' => 'bad() ਪੰਜਾਬ']) : $url;
            $this->assertSame($expectedUrl, $page['url'], $url);
            $this->assertCount(1, $this->scripts($html), $url);
            $this->assertEmpty($this->nodes($schema, 'NewsArticle'), $url);
            $this->assertNoEmptyValues($schema);
        }

        $authorSchema = $this->schema($this->get(route('authors.show', $author->username))->getContent());
        $this->assertSame($this->node($authorSchema, 'Person')['@id'], $this->node($authorSchema, 'ProfilePage')['mainEntity']['@id']);
    }

    public function test_unpublished_articles_do_not_expose_news_article_schema(): void
    {
        $draft = Post::factory()->create(['slug' => 'schema-draft']);
        $scheduled = Post::factory()->scheduled()->create(['slug' => 'schema-scheduled']);

        $this->get('/2026/07/'.$draft->slug)->assertNotFound()->assertDontSee('NewsArticle');
        $this->get('/2026/07/'.$scheduled->slug)->assertNotFound()->assertDontSee('NewsArticle');
    }

    /** @return array<string, mixed> */
    private function schema(string $html): array
    {
        $scripts = $this->scripts($html);
        $this->assertCount(1, $scripts);

        return json_decode($scripts[0], true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array<int, string> */
    private function scripts(string $html): array
    {
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

        return $matches[1];
    }

    /** @return array<int, array<string, mixed>> */
    private function nodes(array $schema, string $type): array
    {
        return array_values(array_filter($schema['@graph'], fn (array $node): bool => ($node['@type'] ?? null) === $type));
    }

    /** @return array<string, mixed> */
    private function node(array $schema, string $type, ?string $idSuffix = null): array
    {
        $nodes = array_values(array_filter($this->nodes($schema, $type), fn (array $node): bool => $idSuffix === null || str_ends_with($node['@id'] ?? '', $idSuffix)));
        $this->assertNotEmpty($nodes, $type);

        return $nodes[0];
    }

    private function assertNoEmptyValues(array $value): void
    {
        foreach ($value as $key => $item) {
            $this->assertNotNull($item, (string) $key);
            $this->assertNotSame('', $item, (string) $key);
            if (is_array($item)) {
                $this->assertNotSame([], $item, (string) $key);
                $this->assertNoEmptyValues($item);
            }
        }
    }
}
