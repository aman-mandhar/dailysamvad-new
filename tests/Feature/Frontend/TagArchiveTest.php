<?php

namespace Tests\Feature\Frontend;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_filters_published_posts_and_renders_archive_metadata(): void
    {
        $tag = Tag::factory()->create(['name' => 'Politics']);
        $published = Post::factory()->published()->create(['title' => 'Tagged public report']);
        $draft = Post::factory()->create(['title' => 'Tagged draft']);
        $tag->posts()->attach([$published->id, $draft->id]);

        $response = $this->get(route('tags.show', $tag->slug))->assertOk()
            ->assertSee($published->title)->assertDontSee($draft->title)
            ->assertSee('index, follow')->assertSee('CollectionPage');

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }

    public function test_valid_empty_tag_returns_200_and_invalid_tag_returns_404(): void
    {
        $tag = Tag::factory()->create();
        $this->get(route('tags.show', $tag->slug))->assertOk()->assertSee('No published news is available for this tag.');
        $this->get('/tag/not-real')->assertNotFound();
    }

    public function test_tag_archive_paginates_in_existing_newest_first_order_and_escapes_name(): void
    {
        config()->set('archive.per_page', 2);
        $tag = Tag::factory()->create(['name' => '<script>Punjab</script>']);
        $oldest = Post::factory()->published()->create(['published_at' => now()->subDays(2)]);
        $middle = Post::factory()->published()->create(['published_at' => now()->subDay()]);
        $newest = Post::factory()->published()->create(['published_at' => now()]);
        $tag->posts()->attach([$oldest->id, $middle->id, $newest->id]);

        $firstPage = $this->get(route('tags.show', $tag->slug))->assertOk()
            ->assertSee('&lt;script&gt;Punjab&lt;/script&gt;', false)
            ->assertDontSee('<script>Punjab</script>', false)
            ->assertSee($newest->title)
            ->assertSee($middle->title)
            ->assertViewHas('archive', fn ($archive): bool => $archive->posts->modelKeys() === [$newest->id, $middle->id]);

        $this->assertLessThan(
            strpos($firstPage->getContent(), $middle->title),
            strpos($firstPage->getContent(), $newest->title),
        );
        $this->get(route('tags.show', ['slug' => $tag->slug, 'page' => 2]))
            ->assertOk()
            ->assertSee($oldest->title);
    }
}
