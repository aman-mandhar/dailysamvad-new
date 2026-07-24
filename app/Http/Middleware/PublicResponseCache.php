<?php

namespace App\Http\Middleware;

use App\Support\CacheKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PublicResponseCache
{
    public function __construct(private readonly CacheKey $keys) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->eligible($request)) return $next($request);
        $key = $this->keys->make('public', 'page', 'route', $request->route()?->getName() ?? 'unknown', parameters: ['path' => $request->path(), 'query' => $request->query()]);
        try {
            $store = Cache::store(config('cache_architecture.store', 'redis'));
            if (($cached = $store->get($key)) !== null) return response($cached['body'], $cached['status'], $cached['headers'])->header('X-Cache', 'HIT');
            $lock = $store->lock($key.':lock', 10);
            if (! $lock->get()) return $next($request);
            try {
                if (($cached = $store->get($key)) !== null) return response($cached['body'], $cached['status'], $cached['headers'])->header('X-Cache', 'HIT');
            $response = $next($request);
            if ($response->isSuccessful() && ! $response->headers->has('Set-Cookie') && ! $response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
                $store->put($key, ['body' => $response->getContent(), 'status' => $response->getStatusCode(), 'headers' => ['Content-Type' => $response->headers->get('Content-Type', 'text/html; charset=UTF-8')]], config('cache_architecture.ttls.medium'));
                $response->headers->set('X-Cache', 'MISS');
            }
                return $response;
            } finally { $lock->release(); }
        } catch (Throwable) { return $next($request)->header('X-Cache', 'ERROR'); }
    }

    private function eligible(Request $request): bool
    {
        if (! config('cache_architecture.enabled') || ! config('cache_architecture.full_page')) return false;
        if (! in_array($request->method(), ['GET', 'HEAD'], true) || $request->user() || $request->expectsJson()) return false;
        if ($request->routeIs('search', 'login', 'register', 'password.*', 'news.legacy', 'news.legacy-root') || $request->is('admin/*', 'livewire/*')) return false;
        return $request->path() === '/' || $request->is('feed.xml') || $request->routeIs('home', 'news.show', 'categories.show', 'tags.show', 'authors.show', 'archives.*', 'static.*');
    }
}
