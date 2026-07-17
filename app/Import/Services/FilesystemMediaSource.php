<?php

namespace App\Import\Services;

use App\Import\Contracts\MediaSource;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use RuntimeException;

class FilesystemMediaSource implements MediaSource
{
    private ?FilesystemAdapter $filesystem = null;

    public function __construct(private readonly FilesystemManager $filesystems) {}

    public function exists(string $path): bool
    {
        return $this->filesystem()->exists($path);
    }

    public function readStream(string $path): mixed
    {
        return $this->filesystem()->readStream($path);
    }

    public function size(string $path): int
    {
        return $this->filesystem()->size($path);
    }

    public function mimeType(string $path): ?string
    {
        return $this->filesystem()->mimeType($path);
    }

    private function filesystem(): FilesystemAdapter
    {
        if ($this->filesystem) {
            return $this->filesystem;
        }

        $disk = config('import.media.source_disk');
        if (filled($disk)) {
            return $this->filesystem = $this->filesystems->disk($disk);
        }

        $path = config('import.media.source_path');
        if (blank($path)) {
            throw new RuntimeException('Configure WORDPRESS_UPLOADS_PATH or WORDPRESS_UPLOADS_DISK before importing media.');
        }

        return $this->filesystem = $this->filesystems->build(['driver' => 'local', 'root' => $path, 'throw' => false]);
    }
}
