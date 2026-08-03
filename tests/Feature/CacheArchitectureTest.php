<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Pages\ReporterDashboard;
use App\Models\Post;
use App\Models\User;
use App\Services\CacheInvalidationService;
use App\Support\CacheKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cache_keys_are_deterministic_and_environment_isolated(): void
    {
        $keys = app(CacheKey::class);
        $this->assertSame($keys->make('public', 'post', 'default', 12, parameters: ['b' => 2, 'a' => 1]), $keys->make('public', 'post', 'default', 12, parameters: ['a' => 1, 'b' => 2]));
        config(['app.env' => 'testing']);
        $testing = $keys->make('public', 'post', 'default', 12);
        config(['app.env' => 'staging']);
        $this->assertNotSame($testing, $keys->make('public', 'post', 'default', 12));
    }

    public function test_public_response_cache_is_disabled_by_default_and_private_routes_are_never_cached(): void
    {
        $this->get('/')->assertOk()->assertHeaderMissing('X-Cache');
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertHeaderMissing('X-Cache');
    }

    public function test_invalidation_is_targeted_and_does_not_flush_unrelated_entries(): void
    {
        config(['cache_architecture.store' => 'array']);
        Cache::store('array')->put('unrelated', 'keep', 60);
        app(CacheInvalidationService::class)->invalidatePost(42);
        $this->assertSame('keep', Cache::store('array')->get('unrelated'));
        $this->assertGreaterThan(0, Cache::store('array')->get(app(CacheKey::class)->make('version', 'post', '42', 'all')) ?? 0);
    }

    public function test_publishing_a_post_invalidates_cached_homepage_content(): void
    {
        config([
            'cache_architecture.enabled' => true,
            'cache_architecture.full_page' => true,
            'cache_architecture.query' => true,
            'cache_architecture.store' => 'array',
        ]);

        $this->get('/')->assertOk()->assertDontSee('Fresh live headline');
        $this->get('/')->assertOk()->assertHeader('X-Cache', 'HIT');

        Post::factory()->create([
            'title' => 'Fresh live headline',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Cache', 'MISS')
            ->assertSee('Fresh live headline');
    }

    public function test_dashboard_cache_is_permission_scoped_by_user_and_page(): void
    {
        $user = User::factory()->create();
        $key = app(CacheKey::class)->make('dashboard', 'metrics', ReporterDashboard::class, $user->id);
        $other = app(CacheKey::class)->make('dashboard', 'metrics', ReporterDashboard::class, $user->id + 1);
        $this->assertNotSame($key, $other);
    }
}
