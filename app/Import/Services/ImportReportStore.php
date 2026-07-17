<?php

namespace App\Import\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Throwable;

class ImportReportStore
{
    public function __construct(private readonly FilesystemManager $filesystems) {}

    /** @param array<string, mixed> $report */
    public function store(string $name, array $report): string
    {
        $path = $this->path($name.'.json');
        $this->disk()->put($path, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /** @return array<string, mixed>|null */
    public function read(string $name): ?array
    {
        try {
            $path = $this->path($name.'.json');

            return $this->disk()->exists($path) ? json_decode($this->disk()->get($path), true, flags: JSON_THROW_ON_ERROR) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /** @param array<string, mixed> $run */
    public function recordRun(array $run): void
    {
        $history = $this->read('history') ?? ['runs' => []];
        array_unshift($history['runs'], $run);
        $history['runs'] = array_slice($history['runs'], 0, 25);
        $this->store('history', $history);
        $this->store('latest', $run);
    }

    /** @param array<string, mixed> $context */
    public function recordEvent(string $level, string $message, array $context): void
    {
        $events = $this->read('events') ?? ['events' => []];
        array_unshift($events['events'], [
            'level' => $level, 'message' => $message, 'context' => $context, 'recorded_at' => now()->toIso8601String(),
        ]);
        $events['events'] = array_slice($events['events'], 0, 100);
        $this->store('events', $events);
    }

    private function disk(): FilesystemAdapter
    {
        return $this->filesystems->disk(config('import.reports.disk', 'local'));
    }

    private function path(string $file): string
    {
        return trim(config('import.reports.path', 'imports/reports'), '/').'/'.$file;
    }
}
