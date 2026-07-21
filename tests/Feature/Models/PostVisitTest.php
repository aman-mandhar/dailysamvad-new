<?php

namespace Tests\Feature\Models;

use App\Models\Post;
use App\Models\PostVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostVisitTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_belongs_to_post_and_optional_logged_in_visitor(): void
    {
        $user = User::factory()->create();
        $visit = PostVisit::factory()->create(['visitor_id' => $user]);

        $this->assertInstanceOf(Post::class, $visit->post);
        $this->assertTrue($visit->visitor->is($user));
        $this->assertTrue($visit->post->visits->contains($visit));
        $this->assertTrue($user->postVisits->contains($visit));
        $this->assertInstanceOf(Carbon::class, $visit->visited_at);
    }

    public function test_anonymous_visit_can_use_a_stable_uuid_without_a_user(): void
    {
        $uuid = (string) Str::uuid();
        $visit = PostVisit::factory()->create([
            'visitor_id' => null,
            'visitor_uuid' => $uuid,
        ]);

        $this->assertNull($visit->visitor);
        $this->assertSame($uuid, $visit->visitor_uuid);
    }

    public function test_deleting_visitor_nulls_visitor_id(): void
    {
        $user = User::factory()->create();
        $visit = PostVisit::factory()->create(['visitor_id' => $user]);

        $user->delete();

        $this->assertDatabaseHas('post_visits', ['id' => $visit->getKey(), 'visitor_id' => null]);
    }

    public function test_deleting_post_cascades_related_visits(): void
    {
        $visit = PostVisit::factory()->create();

        $visit->post->forceDelete();

        $this->assertDatabaseMissing('post_visits', ['id' => $visit->getKey()]);
    }
}
