<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostWorkflowEvent;
use App\Models\PostVisit;
use App\Models\User;
use App\Support\Authorization\ContentAccess;
use Illuminate\Database\Eloquent\Builder;

class DashboardMetrics
{
    /** @return Builder<Post> */
    public function posts(User $user): Builder
    {
        return ContentAccess::scopePosts(Post::query(), $user);
    }

    /** @return Builder<Media> */
    public function media(User $user): Builder
    {
        return ContentAccess::scopeMedia(Media::query(), $user);
    }

    /** @return array<string, int> */
    public function postCounts(User $user): array
    {
        $query = $this->posts($user);
        $rows = (clone $query)->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        return collect(PostStatus::cases())->mapWithKeys(fn (PostStatus $status): array => [$status->value => (int) ($rows[$status->value] ?? 0)])->all();
    }

    /** @return array<string, int> */
    public function ownSummary(User $user): array
    {
        $counts = $this->postCounts($user);
        return [
            'drafts' => $counts[PostStatus::Draft->value],
            'pending_review' => $counts[PostStatus::PendingReview->value],
            'changes_requested' => $counts[PostStatus::ChangesRequested->value],
            'published' => $counts[PostStatus::Published->value],
            'views' => (int) $this->posts($user)->sum('views_count'),
        ];
    }

    /** @return array<string, int> */
    public function editorialSummary(User $user): array
    {
        $counts = $this->postCounts($user);
        return [
            'pending_review' => $counts[PostStatus::PendingReview->value],
            'changes_requested' => $counts[PostStatus::ChangesRequested->value],
            'approved' => $counts[PostStatus::Approved->value],
            'scheduled' => $counts[PostStatus::Scheduled->value],
            'published_today' => $this->posts($user)->where('status', PostStatus::Published)->whereDate('published_at', today())->count(),
        ];
    }

    /** @return array<string, int> */
    public function mediaSummary(User $user): array
    {
        $query = $this->media($user);
        return ['media' => (clone $query)->count(), 'images' => (clone $query)->where('mime_type', 'like', 'image/%')->count(), 'missing' => (clone $query)->whereNotNull('missing_at')->count()];
    }

    /** @return array<string, int> */
    public function seoSummary(User $user): array
    {
        $query = $this->posts($user);
        return [
            'posts' => (clone $query)->count(),
            'missing_titles' => (clone $query)->whereNull('meta_title')->count(),
            'missing_descriptions' => (clone $query)->whereNull('meta_description')->count(),
            'missing_canonical' => (clone $query)->whereNull('canonical_url')->count(),
        ];
    }

    /** @return array<string, int> */
    public function analyticsSummary(User $user): array
    {
        $base = $user->can('view all analytics') ? Post::query() : $this->posts($user);
        $query = $base->where('status', PostStatus::Published);
        return ['published_posts' => (clone $query)->count(), 'views' => (int) (clone $query)->sum('views_count'), 'visits' => (int) PostVisit::query()->whereIn('post_id', $query->select('posts.id'))->count()];
    }

    /** @return array<string, int> */
    public function adminSummary(User $user): array
    {
        return [...$this->editorialSummary($user), 'users' => User::query()->count(), 'categories' => \App\Models\Category::query()->count(), 'tags' => \App\Models\Tag::query()->count()];
    }

    /** @return array<int, array{event: string, post_id: int, created_at: string}> */
    public function recentActivity(User $user, int $limit = 8): array
    {
        return PostWorkflowEvent::query()->whereIn('post_id', $this->posts($user)->select('id'))->latest()->limit($limit)->get(['event', 'post_id', 'created_at'])->map(fn (PostWorkflowEvent $event): array => ['event' => $event->event, 'post_id' => (int) $event->post_id, 'created_at' => $event->created_at->toDateTimeString()])->all();
    }
}
