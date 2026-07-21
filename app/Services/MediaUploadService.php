<?php

namespace App\Services;

use App\Models\Media;
use App\Support\MediaPathNormalizer;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class MediaUploadService
{
    public function __construct(private readonly FilesystemManager $filesystems, private readonly MediaPathNormalizer $paths) {}

    public function store(UploadedFile $file, ?int $uploaderId = null, array $metadata = []): Media
    {
        $this->validate($file);
        $checksum = hash_file('sha256', $file->getRealPath());
        $existing = Media::query()
            ->where('checksum', $checksum)
            ->where('size', $file->getSize())
            ->when($uploaderId !== null, fn ($query) => $query->where('uploaded_by', $uploaderId))
            ->first();
        if ($existing) {
            return $existing;
        }

        $mime = (string) $file->getMimeType();
        $extension = match ($mime) {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
            default => throw ValidationException::withMessages(['upload' => 'The image format is not supported.']),
        };
        $dimensions = @getimagesize($file->getRealPath());
        $disk = (string) config('media.disk', 'public');
        $path = trim((string) config('media.library_path', 'media/library'), '/').'/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;
        $path = $this->paths->normalize($path);
        if ($path === null || ! $this->filesystems->disk($disk)->putFileAs(dirname($path), $file, basename($path))) {
            throw ValidationException::withMessages(['upload' => 'The file could not be stored.']);
        }

        try {
            return DB::transaction(fn (): Media => Media::query()->create([
                'disk' => $disk, 'path' => $path, 'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $mime, 'size' => $file->getSize(), 'width' => $dimensions[0] ?? null,
                'height' => $dimensions[1] ?? null, 'checksum' => $checksum, 'uploaded_by' => $uploaderId,
                'alt_text' => $metadata['alt_text'] ?? null, 'caption' => $metadata['caption'] ?? null,
                'credit' => $metadata['credit'] ?? null, 'copyright' => $metadata['copyright'] ?? null,
            ]));
        } catch (Throwable $exception) {
            $this->filesystems->disk($disk)->delete($path);
            throw $exception;
        }
    }

    private function validate(UploadedFile $file): void
    {
        $maxBytes = (int) config('media.max_upload_kilobytes', 10240) * 1024;
        $mime = $file->getMimeType();
        if (! $file->isValid() || ! $file->getSize() || $file->getSize() > $maxBytes
            || ! in_array($mime, (array) config('media.allowed_mime_types', []), true)
            || @getimagesize($file->getRealPath()) === false) {
            throw ValidationException::withMessages(['upload' => 'Upload a valid JPEG, PNG, GIF, or WebP image within the configured size limit.']);
        }
    }
}
