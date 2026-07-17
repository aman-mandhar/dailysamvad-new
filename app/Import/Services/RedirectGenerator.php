<?php

namespace App\Import\Services;

use App\Models\Post;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Collection;

class RedirectGenerator
{
    public function __construct(private readonly FilesystemManager $filesystems) {}

    /** @return array{redirects: array<int, array<string, mixed>>, duplicates: int, broken: int} */
    public function generate(): array
    {
        $redirects = [];
        $seen = [];
        $duplicates = 0;
        $broken = 0;

        Post::query()->whereNotNull('old_wp_id')->select(['id', 'slug', 'old_url'])->orderBy('id')
            ->each(function (Post $post) use (&$redirects, &$seen, &$duplicates, &$broken): void {
                $old = $this->normalizeOldUrl($post->old_url);
                if ($old === null || blank($post->slug)) {
                    $broken++;

                    return;
                }

                if (isset($seen[$old])) {
                    $duplicates++;

                    return;
                }
                $seen[$old] = true;
                $redirects[] = ['old_url' => $old, 'new_url' => route('news.show', $post->slug), 'status' => 301];
            });

        return compact('redirects', 'duplicates', 'broken');
    }

    /** @param array<int, string> $formats
     * @return array<string, string>
     */
    public function export(array $formats = ['csv', 'json', 'apache', 'nginx', 'laravel']): array
    {
        $generated = $this->generate();
        $redirects = collect($generated['redirects']);
        $outputs = [];

        foreach (array_unique($formats) as $format) {
            $content = match ($format) {
                'csv' => $this->csv($redirects),
                'json' => json_encode($redirects->values()->all(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'apache' => $redirects->map(fn ($redirect) => "Redirect 301 {$redirect['old_url']} {$redirect['new_url']}")->implode(PHP_EOL).PHP_EOL,
                'nginx' => $redirects->map(fn ($redirect) => "location = {$redirect['old_url']} { return 301 {$redirect['new_url']}; }")->implode(PHP_EOL).PHP_EOL,
                'laravel' => '<?php'.PHP_EOL.PHP_EOL.'return '.var_export($redirects->mapWithKeys(fn ($redirect) => [$redirect['old_url'] => $redirect['new_url']])->all(), true).';'.PHP_EOL,
                default => throw new \InvalidArgumentException("Unsupported redirect format [{$format}]."),
            };
            $extension = $format === 'apache' ? 'conf' : ($format === 'nginx' ? 'conf' : ($format === 'laravel' ? 'php' : $format));
            $path = trim(config('import.redirects.path', 'imports/redirects'), '/')."/redirects-{$format}.{$extension}";
            $this->filesystems->disk(config('import.redirects.disk', 'local'))->put($path, $content);
            $outputs[$format] = $path;
        }

        return $outputs;
    }

    public function normalizeOldUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $path = '/'.ltrim(preg_replace('#/+#', '/', $parts['path'] ?? ''), '/');
        if ($path === '/' && empty($parts['query'])) {
            return null;
        }

        $normalized = rtrim($path, '/') ?: '/';

        return $normalized.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    private function csv(Collection $redirects): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['old_url', 'new_url', 'status']);
        $redirects->each(fn ($redirect) => fputcsv($stream, $redirect));
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content;
    }
}
