<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Post;
use App\Support\MediaPathNormalizer;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AuditMediaIntegrity extends Command
{
    protected $signature = 'media:audit {--chunk=500} {--no-storage-scan} {--fail-on-errors}';

    protected $description = 'Run a read-only, chunked media integrity audit';

    public function handle(MediaPathNormalizer $normalizer, FilesystemManager $filesystems): int
    {
        $chunk = max(1, min((int) $this->option('chunk'), 5000));
        $counts = array_fill_keys([
            'records', 'featured_references', 'soft_deleted_references', 'inline_reference_uncertainty',
            'missing_originals', 'missing_derivatives', 'derivative_without_original', 'invalid_paths',
            'zero_byte_files', 'unsupported_mime_metadata', 'missing_dimensions', 'deleted_media_assigned',
            'duplicate_checksum_groups', 'wordpress_identity_conflicts', 'files_without_records',
        ], 0);
        $allowedMime = (array) config('media.allowed_mime_types', []);
        $known = [(string) config('media.disk', 'public') => []];

        Media::withTrashed()->orderBy('id')->chunkById($chunk, function ($records) use (&$counts, &$known, $normalizer, $filesystems, $allowedMime): void {
            foreach ($records as $media) {
                $counts['records']++;
                $normalized = $normalizer->normalize($media->path);
                if ($normalized === null || $normalized !== $media->path) {
                    $counts['invalid_paths']++;

                    continue;
                }
                $known[$media->disk][$media->path] = true;
                $disk = $filesystems->disk($media->disk);
                $exists = $disk->exists($media->path);
                if (! $exists) {
                    $counts['missing_originals']++;
                } elseif ($disk->size($media->path) === 0) {
                    $counts['zero_byte_files']++;
                }
                if (! in_array($media->mime_type, $allowedMime, true)) {
                    $counts['unsupported_mime_metadata']++;
                }
                if (! $media->width || ! $media->height) {
                    $counts['missing_dimensions']++;
                }

                $liveReferences = $media->featuredPosts()->count();
                $allReferences = Post::withTrashed()->where('featured_media_id', $media->id)->count();
                $counts['featured_references'] += $liveReferences;
                $counts['soft_deleted_references'] += max(0, $allReferences - $liveReferences);
                if ($media->trashed() && $allReferences > 0) {
                    $counts['deleted_media_assigned'] += $allReferences;
                }
                if (Post::withTrashed()->where('content', 'like', '%'.$media->path.'%')->exists()) {
                    $counts['inline_reference_uncertainty']++;
                }

                foreach ((array) data_get($media->metadata, 'derivatives', []) as $derivative) {
                    $path = is_array($derivative) ? $normalizer->normalize($derivative['path'] ?? null) : null;
                    $derivativeExists = $path !== null && $disk->exists($path);
                    if (! $derivativeExists) {
                        $counts['missing_derivatives']++;
                    }
                    if (! $exists && $derivativeExists) {
                        $counts['derivative_without_original']++;
                    }
                }
            }
            $this->line("Audited {$counts['records']} database record(s)...");
        });

        $counts['duplicate_checksum_groups'] = Media::withTrashed()->whereNotNull('checksum')->selectRaw('checksum, size, COUNT(*) AS aggregate')
            ->groupBy('checksum', 'size')->havingRaw('COUNT(*) > 1')->count();
        $counts['wordpress_identity_conflicts'] = Media::withTrashed()->whereNotNull('old_wp_id')->selectRaw('old_wp_id, COUNT(*) AS aggregate')
            ->groupBy('old_wp_id')->havingRaw('COUNT(*) > 1')->count();

        if (! $this->option('no-storage-scan')) {
            foreach ($known as $diskName => $paths) {
                try {
                    foreach ($filesystems->disk($diskName)->getDriver()->listContents('', true) as $item) {
                        if ($item->isFile() && ! isset($paths[$item->path()])) {
                            $counts['files_without_records']++;
                        }
                    }
                } catch (Throwable) {
                    $this->warn("Storage scan unavailable for disk {$diskName}; database checks continued.");
                }
            }
        }

        $report = ['generated_at' => now()->toAtomString(), 'read_only' => true, 'chunk_size' => $chunk, 'counts' => $counts];
        $reportPath = 'media-audits/media-audit-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        foreach ($counts as $name => $count) {
            $this->line(str_replace('_', ' ', $name).": {$count}");
        }
        $this->info("No files or records were deleted. Read-only report written to storage/app/{$reportPath}.");

        $errors = $counts['missing_originals'] + $counts['invalid_paths'] + $counts['zero_byte_files'] + $counts['wordpress_identity_conflicts'];

        return $this->option('fail-on-errors') && $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
