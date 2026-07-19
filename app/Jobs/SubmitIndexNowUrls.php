<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubmitIndexNowUrls implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 15;

    public array $backoff = [60, 300];

    public function __construct(public readonly array $urls) {}

    public function handle(): void
    {
        $key = config('seo.indexnow.key');
        if (! config('seo.indexnow.enabled') || ! is_string($key) || ! preg_match('/^[A-Za-z0-9-]{8,128}$/', $key)) {
            return;
        }

        try {
            $response = Http::timeout((int) config('seo.indexnow.timeout', 5))->retry(2, 250, throw: false)
                ->post((string) config('seo.indexnow.endpoint'), [
                    'host' => parse_url(route('home'), PHP_URL_HOST),
                    'key' => $key,
                    'keyLocation' => route('seo.indexnow.key', ['key' => $key]),
                    'urlList' => $this->urls,
                ]);
            if ($response->failed()) {
                Log::warning('IndexNow submission failed.', ['status' => $response->status(), 'url_count' => count($this->urls)]);
            }
        } catch (Throwable $exception) {
            Log::warning('IndexNow submission failed safely.', ['exception' => $exception::class, 'url_count' => count($this->urls)]);
        }
    }
}
