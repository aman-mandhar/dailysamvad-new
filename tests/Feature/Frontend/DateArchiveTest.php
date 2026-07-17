<?php

namespace Tests\Feature\Frontend;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_year_month_and_day_archives_filter_by_range(): void
    {
        $inside = Post::factory()->create(['title' => 'Leap day report', 'status' => PostStatus::Published, 'published_at' => '2024-02-29 12:00:00']);
        $outside = Post::factory()->create(['title' => 'March report', 'status' => PostStatus::Published, 'published_at' => '2024-03-01 12:00:00']);

        $this->get(route('archives.year', 2024))->assertOk()->assertSee('data-archive-post="'.$inside->id.'"', false)->assertSee('data-archive-post="'.$outside->id.'"', false);
        $this->get(route('archives.month', [2024, 2]))->assertOk()->assertSee('data-archive-post="'.$inside->id.'"', false)->assertDontSee('data-archive-post="'.$outside->id.'"', false);
        $this->get(route('archives.day', [2024, 2, 29]))->assertOk()->assertSee('data-archive-post="'.$inside->id.'"', false)->assertDontSee('data-archive-post="'.$outside->id.'"', false)
            ->assertSee('CollectionPage')->assertSee('BreadcrumbList');
    }

    public function test_invalid_dates_return_404_and_valid_empty_date_returns_200(): void
    {
        $this->get('/archive/2026/13')->assertNotFound();
        $this->get('/archive/2026/2/31')->assertNotFound();
        $this->get('/archive/2023/2/29')->assertNotFound();
        $this->get('/archive/2024/2/29')->assertOk()->assertSee('No published news is available for this date.');
    }
}
