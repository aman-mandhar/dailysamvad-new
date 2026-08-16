<?php

namespace App\Providers;

use App\Contracts\Push\AccessTokenProvider;
use App\Contracts\Push\PushTransport;
use App\Import\Contracts\CheckpointRepository;
use App\Import\Contracts\Logger;
use App\Import\Contracts\MediaSource;
use App\Import\Contracts\Verifier;
use App\Import\Logs\ImportLogService;
use App\Import\Services\FileCheckpointRepository;
use App\Import\Services\FilesystemMediaSource;
use App\Import\Services\ImportVerificationService;
use App\Import\Services\WordPressConnection;
use App\Queries\BreakingNewsQuery;
use App\Queries\NavigationQuery;
use App\Services\Push\FirebaseAccessTokenProvider;
use App\Services\Push\FirebaseMessagingClient;
use App\View\Components\YouTubePlaylistPlayer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Logger::class, ImportLogService::class);
        $this->app->singleton(CheckpointRepository::class, FileCheckpointRepository::class);
        $this->app->singleton(WordPressConnection::class);
        $this->app->singleton(MediaSource::class, FilesystemMediaSource::class);
        $this->app->singleton(Verifier::class, ImportVerificationService::class);
        $this->app->singleton(AccessTokenProvider::class, FirebaseAccessTokenProvider::class);
        $this->app->singleton(PushTransport::class, FirebaseMessagingClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component(YouTubePlaylistPlayer::class, 'youtube-playlist-player');

        RateLimiter::for('push-subscriptions', fn (Request $request): Limit => Limit::perMinute(max(1, (int) config('firebase.security.subscription_limit', 30)))
            ->by('push-subscriptions:'.$request->ip()));
        RateLimiter::for('push-preferences-read', fn (Request $request): Limit => Limit::perMinute(max(1, (int) config('firebase.security.preference_read_limit', 60)))
            ->by('push-preferences-read:'.$request->ip()));
        RateLimiter::for('push-preferences-write', fn (Request $request): Limit => Limit::perMinute(max(1, (int) config('firebase.security.preference_write_limit', 20)))
            ->by('push-preferences-write:'.$request->ip()));
        RateLimiter::for('push-clicks', fn (Request $request): Limit => Limit::perMinute(max(1, (int) config('firebase.security.click_limit', 240)))
            ->by('push-clicks:'.$request->ip()));

        Gate::before(function ($user, string $ability): ?bool {
            if (! $user->is_active) {
                return false;
            }

            return $user->hasRole('super-admin') ? true : null;
        });

        View::composer('layouts.frontend', function ($view): void {
            $view->with('mainMenu', app(NavigationQuery::class)->mainMenu());
            $view->with(
                'globalBreakingNews',
                config('frontend.breaking_news.enabled', true) && ! View::hasSection('hide_breaking_news')
                    ? app(BreakingNewsQuery::class)->latest((int) config('frontend.breaking_news.limit', 12))
                    : new Collection,
            );
        });
    }
}
