<?php

namespace App\Providers;

use App\Import\Contracts\CheckpointRepository;
use App\Import\Contracts\Logger;
use App\Import\Contracts\MediaSource;
use App\Import\Contracts\Verifier;
use App\Import\Logs\ImportLogService;
use App\Import\Services\FileCheckpointRepository;
use App\Import\Services\FilesystemMediaSource;
use App\Import\Services\ImportVerificationService;
use App\Import\Services\WordPressConnection;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
