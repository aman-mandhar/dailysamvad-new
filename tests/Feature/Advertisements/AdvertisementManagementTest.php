<?php

namespace Tests\Feature\Advertisements;

use App\Enums\AdvertisementPosition;
use App\Enums\AdvertisementStatus;
use App\Filament\Resources\Advertisements\Pages\CreateAdvertisement;
use App\Models\Advertisement;
use App\Models\AdvertisementDailyStat;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\Advertisements\AdvertisementCacheService;
use App\Services\Advertisements\AdvertisementResolver;
use App\Support\AdvertisementUrl;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class AdvertisementManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache_architecture.store' => 'array']);
        Cache::flush();
    }

    public function test_active_database_ad_wins_over_config_and_respects_schedule_status_and_targeting(): void
    {
        $post = Post::factory()->create();
        $active = $this->advertisement(['priority' => 10]);
        $active->placements()->create(['position' => AdvertisementPosition::ArticleTop->value, 'page_type' => 'article', 'post_id' => $post->id, 'device' => 'desktop']);
        $active->creatives()->create(['type' => 'html', 'html_code' => '<div onclick="bad()"><strong>Database ad</strong><script>bad()</script></div>']);
        foreach ([
            ['status' => AdvertisementStatus::Draft],
            ['status' => AdvertisementStatus::Paused],
            ['status' => AdvertisementStatus::Active, 'start_at' => now()->addHour()],
            ['status' => AdvertisementStatus::Active, 'end_at' => now()->subSecond()],
        ] as $attributes) {
            $ad = $this->advertisement($attributes);
            $ad->placements()->create(['position' => 'ARTICLE_TOP', 'device' => 'all']);
            $ad->creatives()->create(['type' => 'html', 'html_code' => '<b>Invalid candidate</b>']);
        }
        $resolved = app(AdvertisementResolver::class)->resolve(AdvertisementPosition::ArticleTop, ['page_type' => 'article', 'post_id' => $post->id], 'desktop');
        $this->assertSame($active->getKey(), $resolved->advertisementId);
        $this->assertStringContainsString('Database ad', $resolved->html);
        $this->assertStringNotContainsString('onclick', $resolved->html);
        $this->assertStringNotContainsString('script', $resolved->html);
        $fallback = app(AdvertisementResolver::class)->resolve(AdvertisementPosition::ArticleTop, ['page_type' => 'article', 'post_id' => 99], 'mobile');
        $this->assertNull($fallback->advertisementId);
    }

    public function test_priority_rotation_uses_only_highest_priority_and_positive_weights(): void
    {
        $low = $this->advertisement(['priority' => 1]);
        $high = $this->advertisement(['priority' => 20, 'rotation_weight' => 3]);
        foreach ([$low, $high] as $ad) {
            $ad->placements()->create(['position' => 'FOOTER_TOP', 'device' => 'all']);
            $ad->creatives()->create(['type' => 'html', 'html_code' => '<b>'.$ad->title.'</b>']);
        }
        $this->assertSame($high->getKey(), app(AdvertisementResolver::class)->resolve(AdvertisementPosition::FooterTop, ['page_type' => 'footer'], 'desktop')->advertisementId);
    }

    public function test_header_top_advertisement_record_is_retained_but_not_rendered_in_the_header(): void
    {
        $ad = $this->advertisement(['title' => 'Managed header campaign']);
        $ad->placements()->create([
            'position' => AdvertisementPosition::HeaderTop->value,
            'page_type' => 'home',
            'device' => 'all',
        ]);
        $ad->creatives()->create([
            'type' => 'html',
            'html_code' => '<strong>Managed header creative</strong>',
            'width' => 970,
            'height' => 90,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Managed header creative', false)
            ->assertDontSee('data-ad-slot="HEADER_TOP"', false);

        $this->assertDatabaseHas('advertisements', ['id' => $ad->getKey()]);
    }

    public function test_advertisement_schedule_fields_use_the_configured_display_timezone(): void
    {
        config(['app.display_timezone' => 'Asia/Kolkata']);
        $admin = User::factory()->create();
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin->assignRole('admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($admin)
            ->test(CreateAdvertisement::class)
            ->assertFormFieldExists('start_at', fn (DateTimePicker $field): bool => $field->getTimezone() === 'Asia/Kolkata')
            ->assertFormFieldExists('end_at', fn (DateTimePicker $field): bool => $field->getTimezone() === 'Asia/Kolkata');
    }

    public function test_resolver_uses_serialization_safe_cache_payloads(): void
    {
        config(['cache_architecture.store' => 'database']);
        $ad = $this->advertisement();
        $ad->placements()->create(['position' => 'FOOTER_TOP', 'device' => 'all']);
        $ad->creatives()->create(['type' => 'html', 'html_code' => '<b>Cached ad</b>']);
        $resolver = app(AdvertisementResolver::class);

        $first = $resolver->resolve(AdvertisementPosition::FooterTop, ['page_type' => 'footer'], 'desktop');
        $second = $resolver->resolve(AdvertisementPosition::FooterTop, ['page_type' => 'footer'], 'desktop');

        $this->assertSame($ad->getKey(), $first->advertisementId);
        $this->assertSame($ad->getKey(), $second->advertisementId);
        $this->assertSame($first->toCacheArray(), $second->toCacheArray());
    }

    public function test_click_and_impression_tracking_deduplicates_and_redirect_is_not_request_controlled(): void
    {
        $ad = $this->advertisement(['target_url' => 'https://advertiser.test/path?utm_source=site']);
        $ad->placements()->create(['position' => 'FOOTER_TOP', 'device' => 'all']);
        $ad->creatives()->create(['type' => 'html', 'html_code' => '<b>Ad</b>']);
        $this->postJson(route('advertisements.impression', $ad->uuid), ['visitor' => 'opaque-1'])->assertNoContent();
        $this->postJson(route('advertisements.impression', $ad->uuid), ['visitor' => 'opaque-1'])->assertNoContent();
        $this->get(route('advertisements.click', [$ad->uuid, 'url' => 'https://evil.test']))->assertRedirect('https://advertiser.test/path?utm_source=site');
        $stat = AdvertisementDailyStat::query()->firstOrFail();
        $this->assertSame(1, $stat->impressions);
        $this->assertSame(1, $stat->clicks);
    }

    public function test_unsafe_destination_is_never_redirected(): void
    {
        $ad = $this->advertisement(['target_url' => 'javascript:alert(1)']);
        $this->assertNull(AdvertisementUrl::normalize($ad->target_url));
        $this->get(route('advertisements.click', $ad->uuid))->assertRedirect(route('home'));
    }

    public function test_frontend_update_is_permission_based_and_guest_is_blocked(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $ad = $this->advertisement();
        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $reporter = User::factory()->create();
        $reporter->assignRole('reporter');
        $payload = ['title' => 'Updated ad', 'target_url' => 'https://safe.test', 'status' => 'paused', 'open_in_new_tab' => '1', 'sponsored' => '1', 'nofollow' => '1'];
        $this->patch(route('advertisements.frontend.update', $ad), $payload)->assertRedirect(route('login'));
        $this->actingAs($reporter)->patch(route('advertisements.frontend.update', $ad), $payload)->assertForbidden();
        $this->actingAs($editor)->patch(route('advertisements.frontend.update', $ad), $payload)->assertRedirect();
        $this->assertDatabaseHas('advertisements', ['id' => $ad->id, 'title' => 'Updated ad', 'status' => 'paused']);
    }

    public function test_cache_version_changes_when_campaign_changes(): void
    {
        $ad = $this->advertisement();
        $before = app(AdvertisementCacheService::class)->version();
        $ad->update(['title' => 'Changed']);
        $this->assertGreaterThan($before, app(AdvertisementCacheService::class)->version());
    }

    public function test_role_matrix_and_frontend_overlay_are_authorized_server_side(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $ad = $this->advertisement();
        $ad->placements()->create(['position' => 'FOOTER_TOP', 'device' => 'all']);
        $ad->creatives()->create(['type' => 'html', 'html_code' => '<strong>Managed ad</strong>']);

        foreach (['super-admin' => true, 'admin' => true, 'editor' => true, 'reporter' => false, 'reviewer' => false, 'seo-manager' => false, 'analytics-manager' => false, 'subscriber' => false] as $role => $canQuickEdit) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->assertSame($canQuickEdit, $user->can('updateFromFrontend', $ad), $role);
        }

        auth()->logout();
        $guest = Blade::render('<x-advertisement.slot :position="\App\Enums\AdvertisementPosition::FooterTop" :context="[\'page_type\' => \'footer\']" />');
        $this->assertStringNotContainsString('ds-ad-editor', $guest);
        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $managed = $this->actingAs($editor);
        $managedHtml = Blade::render('<x-advertisement.slot :position="\App\Enums\AdvertisementPosition::FooterTop" :context="[\'page_type\' => \'footer\']" />');
        $this->assertStringContainsString('ds-ad-editor', $managedHtml);
        $managed->get('/admin/advertisements')->assertOk();
    }

    public function test_create_form_renders_when_media_filename_is_missing(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Media::query()->create([
            'disk' => 'public',
            'path' => 'media/library/imported-banner.jpg',
            'original_filename' => null,
            'mime_type' => 'image/jpeg',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($admin)
            ->test(CreateAdvertisement::class)
            ->assertOk();
    }

    private function advertisement(array $attributes = []): Advertisement
    {
        return Advertisement::factory()->create($attributes);
    }
}
