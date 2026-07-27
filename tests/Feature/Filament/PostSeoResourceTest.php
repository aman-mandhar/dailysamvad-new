<?php

namespace Tests\Feature\Filament;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostSeoResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->editor = User::factory()->create();
        $this->editor->assignRole('editor');
        $this->category = Category::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_seo_and_source_fields_can_be_saved(): void
    {
        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm($this->postData([
                'meta_title' => 'Optimized Punjab News Title',
                'meta_description' => 'A concise description of the latest Punjab news story.',
                'focus_keyword' => 'Punjab news',
                'canonical_url' => 'https://www.dailysamvad.test/news/seo-resource-story',
                'robots' => 'noindex_follow',
                'source_name' => 'Daily News Agency',
                'source_url' => 'https://source.example.com/story',
                'old_url' => '/legacy-wordpress-path/?p=500',
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'seo-resource-story')->firstOrFail();

        $this->assertSame('Optimized Punjab News Title', $post->meta_title);
        $this->assertSame('Punjab news', $post->focus_keyword);
        $this->assertSame('Daily News Agency', $post->source_name);
        $this->assertSame('/legacy-wordpress-path/?p=500', $post->old_url);
        $this->assertSame(['index' => false, 'follow' => true], $post->seo_data['robots']);
    }

    public function test_meta_title_and_description_length_validation(): void
    {
        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm($this->postData([
                'meta_title' => str_repeat('T', 256),
                'meta_description' => str_repeat('D', 161),
            ]))
            ->call('create')
            ->assertHasFormErrors([
                'meta_title' => 'max',
                'meta_description' => 'max',
            ]);
    }

    public function test_canonical_and_source_urls_must_be_valid(): void
    {
        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm($this->postData([
                'canonical_url' => 'not a canonical url',
                'source_url' => 'not a source url',
            ]))
            ->call('create')
            ->assertHasFormErrors([
                'canonical_url' => 'url',
                'source_url' => 'url',
            ]);
    }

    public function test_historical_url_accepts_imported_nonstandard_values(): void
    {
        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm($this->postData([
                'old_url' => 'wordpress:?p=123 malformed legacy value',
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('posts', [
            'slug' => 'seo-resource-story',
            'old_url' => 'wordpress:?p=123 malformed legacy value',
        ]);
    }

    public function test_existing_seo_helper_methods_continue_to_use_saved_values(): void
    {
        $post = $this->postWithTaxonomy([
            'title' => 'Fallback Title',
            'excerpt' => 'Fallback description.',
        ]);

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'meta_title' => 'Explicit Meta Title',
                'meta_description' => 'Explicit meta description.',
                'canonical_url' => 'https://www.dailysamvad.test/news/explicit-canonical',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();

        $this->assertSame('Explicit Meta Title', $post->effectiveMetaTitle());
        $this->assertSame('Explicit meta description.', $post->effectiveMetaDescription());
        $this->assertSame(
            'https://www.dailysamvad.test/news/explicit-canonical',
            $post->effectiveCanonicalUrl(),
        );
    }

    public function test_editing_seo_preserves_imported_wordpress_and_existing_json_metadata(): void
    {
        $post = $this->postWithTaxonomy([
            ...Post::factory()->importedFromWordPress()->raw(),
            'seo_data' => [
                'open_graph' => ['type' => 'article'],
                'robots' => ['index' => true, 'follow' => true],
            ],
        ]);
        $oldWpId = $post->old_wp_id;
        $oldUrl = $post->old_url;
        $sourceData = $post->source_data;

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'meta_title' => 'Updated Imported Story SEO',
                'robots' => 'index_nofollow',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();

        $this->assertSame($oldWpId, $post->old_wp_id);
        $this->assertSame($oldUrl, $post->old_url);
        $this->assertSame($sourceData, $post->source_data);
        $this->assertSame('WordPress', $post->source_name);
        $this->assertSame('article', $post->seo_data['open_graph']['type']);
        $this->assertSame(['index' => true, 'follow' => false], $post->seo_data['robots']);
    }

    /** @param array<string, mixed> $attributes */
    private function postWithTaxonomy(array $attributes = []): Post
    {
        $post = Post::factory()->create([
            ...$attributes,
            'author_id' => $this->editor,
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
            'title' => 'SEO Resource Story',
            'slug' => 'seo-resource-story',
            'excerpt' => 'A Post Resource SEO test story.',
            'content' => '<p>Complete Post Resource SEO test content.</p>',
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
