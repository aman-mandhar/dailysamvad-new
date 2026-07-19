<?php

namespace App\Import\Importers;

use App\Import\Contracts\CheckpointRepository;
use App\Import\Contracts\Logger;
use App\Import\Contracts\MediaImporter;
use App\Import\Contracts\MediaSource;
use App\Import\DTOs\ImportCheckpoint;
use App\Import\DTOs\ImportContext;
use App\Import\DTOs\ImportResult;
use App\Import\DTOs\MediaImportVerification;
use App\Import\Services\WordPressConnection;
use App\Import\Support\ImportMode;
use App\Import\Support\StatisticsCounter;
use App\Models\Media;
use App\Models\Post;
use App\Support\MediaPathNormalizer;
use DateTimeImmutable;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Collection;
use Throwable;

class WordPressMediaImporter implements MediaImporter
{
    private MediaImportVerification $verification;

    /** @var array<string, string> */
    private array $seenPaths = [];

    public function __construct(
        private readonly WordPressConnection $sourceDatabase,
        private readonly MediaSource $sourceMedia,
        private readonly FilesystemManager $filesystems,
        private readonly CheckpointRepository $checkpoints,
        private readonly Logger $logger,
        private readonly MediaPathNormalizer $paths,
    ) {
        $this->verification = new MediaImportVerification;
    }

    public function key(): string
    {
        return 'media';
    }

    public function verification(): MediaImportVerification
    {
        return $this->verification;
    }

    public function import(ImportContext $context): ImportResult
    {
        $this->verification = new MediaImportVerification;
        $this->seenPaths = [];
        $counter = new StatisticsCounter;
        $position = $context->resume
            ? (int) ($this->checkpoints->latest($context->importId, $this->checkpointKey($context))?->cursor ?? $context->offset)
            : $context->offset;
        $processed = max(0, $position - $context->offset);
        $started = microtime(true);
        $bytesCopied = 0;

        $this->logger->info('Starting WordPress media import.', [
            'mode' => $context->mode->value, 'offset' => $position, 'limit' => $context->limit,
            'memory_mb' => $this->memoryMb(),
        ]);

        while ($context->limit === null || $processed < $context->limit) {
            $take = min($context->chunk, $context->limit === null ? $context->chunk : $context->limit - $processed);
            $attachments = $this->attachments($context, $position, $take);
            if ($attachments->isEmpty()) {
                break;
            }

            $chunkStarted = microtime(true);
            $attachmentIds = $attachments->pluck('source_id')->map(fn ($id) => (int) $id)->all();
            $references = $this->featuredReferences($attachmentIds);
            $altText = $this->altText($attachmentIds);
            $chunkBytes = 0;

            foreach ($attachments as $attachment) {
                $chunkBytes += $this->processAttachment(
                    $attachment,
                    $references->get((int) $attachment->source_id, collect()),
                    $altText->get((int) $attachment->source_id),
                    $counter,
                    $context->mode === ImportMode::DryRun,
                );
            }

            $bytesCopied += $chunkBytes;
            $position += $attachments->count();
            $processed += $attachments->count();

            if ($context->mode === ImportMode::Live) {
                $this->checkpoints->store(new ImportCheckpoint(
                    $context->importId, $this->checkpointKey($context), $position,
                    $counter->statistics(), new DateTimeImmutable,
                ));
            }

            $elapsed = max(microtime(true) - $chunkStarted, 0.0001);
            $this->logger->info('Completed WordPress media chunk.', [
                'offset' => $position, 'records' => $attachments->count(),
                'seconds' => round($elapsed, 4), 'megabytes_per_second' => round(($chunkBytes / 1048576) / $elapsed, 2),
                'memory_mb' => $this->memoryMb(), ...$counter->statistics()->toArray(),
                ...$this->verification->toArray(),
            ]);
        }

        $statistics = $counter->statistics();
        $elapsed = max(microtime(true) - $started, 0.0001);
        $this->logger->success('Completed WordPress media import.', [
            'seconds' => round($elapsed, 4), 'megabytes' => round($bytesCopied / 1048576, 2),
            'megabytes_per_second' => round(($bytesCopied / 1048576) / $elapsed, 2),
            'memory_mb' => $this->memoryMb(), ...$statistics->toArray(), ...$this->verification->toArray(),
        ]);

        return new ImportResult($statistics, true);
    }

    private function attachments(ImportContext $context, int $offset, int $limit): Collection
    {
        $query = $this->sourceDatabase->connection()->table($this->sourceDatabase->table('posts').' as attachments')
            ->leftJoin($this->sourceDatabase->table('postmeta').' as files', function ($join): void {
                $join->on('attachments.ID', '=', 'files.post_id')->where('files.meta_key', '_wp_attached_file');
            })
            ->where('attachments.post_type', 'attachment')
            ->selectRaw('attachments.ID as source_id, attachments.post_mime_type, attachments.post_excerpt, files.meta_value as relative_path');

        if ($context->ids !== []) {
            $query->whereIn('attachments.ID', $context->ids);
        }

        return $query->orderBy('attachments.ID')->offset($offset)->limit($limit)->get();
    }

    private function featuredReferences(array $attachmentIds): Collection
    {
        return $this->sourceDatabase->connection()->table($this->sourceDatabase->table('postmeta'))
            ->where('meta_key', '_thumbnail_id')->whereIn('meta_value', $attachmentIds)
            ->get(['post_id', 'meta_value'])->groupBy(fn ($row) => (int) $row->meta_value);
    }

    private function altText(array $attachmentIds): Collection
    {
        return $this->sourceDatabase->connection()->table($this->sourceDatabase->table('postmeta'))
            ->where('meta_key', '_wp_attachment_image_alt')->whereIn('post_id', $attachmentIds)
            ->pluck('meta_value', 'post_id');
    }

    private function processAttachment(
        object $attachment,
        Collection $references,
        mixed $altText,
        StatisticsCounter $counter,
        bool $dryRun,
    ): int {
        $relativePath = $this->safeRelativePath($attachment->relative_path);
        $exists = $relativePath === null ? false : $this->sourceExists($relativePath, $attachment);
        if ($exists === null) {
            return 0;
        }

        if (! $exists) {
            $this->verification->missing++;
            $this->logger->warning('WordPress media file is missing.', ['attachment_id' => $attachment->source_id, 'path' => $relativePath]);

            return 0;
        }

        if (isset($this->seenPaths[$relativePath])) {
            $counter->duplicates++;
            $media = $this->mapMedia(
                $attachment,
                $this->destinationPath($relativePath),
                $this->seenPaths[$relativePath],
                $this->sourceMedia->size($relativePath),
                $altText,
                $dryRun,
            );
            $this->mapFeaturedImages($references, $media, $this->destinationPath($relativePath), $this->seenPaths[$relativePath], $attachment, $altText, $dryRun);

            return 0;
        }

        try {
            $size = $this->sourceMedia->size($relativePath);
            $mimeType = $this->sourceMedia->mimeType($relativePath);
            $stream = $this->sourceMedia->readStream($relativePath);

            if ($size < 1 || ! is_resource($stream)) {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $this->verification->unreadable++;
                $this->logger->warning('WordPress media file is empty or unreadable.', ['attachment_id' => $attachment->source_id, 'path' => $relativePath]);

                return 0;
            }
            fclose($stream);

            if (! in_array($mimeType, config('import.media.supported_mime_types', []), true)) {
                $this->verification->unsupported++;
                $this->logger->warning('WordPress media MIME type is unsupported.', [
                    'attachment_id' => $attachment->source_id, 'path' => $relativePath, 'mime_type' => $mimeType,
                ]);

                return 0;
            }
            $this->seenPaths[$relativePath] = (string) $mimeType;

            $destinationPath = $this->destinationPath($relativePath);
            $destination = $this->filesystems->disk(config('import.media.destination_disk', 'public'));
            $unchanged = $destination->exists($destinationPath)
                && $destination->size($destinationPath) === $size
                && $this->hash($this->sourceMedia->readStream($relativePath)) === $this->hash($destination->readStream($destinationPath));

            if ($unchanged) {
                $counter->skipped++;
            } else {
                if (! $dryRun) {
                    $copyStream = $this->sourceMedia->readStream($relativePath);
                    if (! is_resource($copyStream) || ! $destination->writeStream($destinationPath, $copyStream)) {
                        if (is_resource($copyStream)) {
                            fclose($copyStream);
                        }
                        throw new \RuntimeException('The destination filesystem rejected the media stream.');
                    }
                    fclose($copyStream);
                }
                $counter->imported++;
            }

            $media = $this->mapMedia($attachment, $destinationPath, (string) $mimeType, $size, $altText, $dryRun);
            $this->mapFeaturedImages($references, $media, $destinationPath, (string) $mimeType, $attachment, $altText, $dryRun);

            return $unchanged || $dryRun ? 0 : $size;
        } catch (Throwable $exception) {
            $this->verification->unreadable++;
            $this->logger->warning('WordPress media file could not be processed.', [
                'attachment_id' => $attachment->source_id, 'path' => $relativePath, 'error' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    private function mapMedia(object $attachment, string $destinationPath, string $mimeType, int $size, mixed $altText, bool $dryRun): ?Media
    {
        if ($dryRun) {
            return null;
        }

        return Media::query()->updateOrCreate(
            ['old_wp_id' => (int) $attachment->source_id],
            [
                'disk' => (string) config('import.media.destination_disk', 'public'),
                'path' => $destinationPath,
                'original_url' => $this->originalUrl((string) $attachment->relative_path),
                'mime_type' => $mimeType,
                'size' => $size,
                'alt_text' => filled($altText) ? (string) $altText : null,
                'caption' => filled($attachment->post_excerpt) ? (string) $attachment->post_excerpt : null,
            ],
        );
    }

    private function mapFeaturedImages(Collection $references, ?Media $media, string $destinationPath, string $mimeType, object $attachment, mixed $altText, bool $dryRun): void
    {
        if ($dryRun || ! str_starts_with($mimeType, 'image/')) {
            return;
        }

        $postIds = $references->pluck('post_id')->map(fn ($id) => (int) $id);
        Post::query()->whereIn('old_wp_id', $postIds)->get()->each(function (Post $post) use ($media, $destinationPath, $attachment, $altText): void {
            $post->update([
                'featured_image' => $destinationPath,
                'featured_media_id' => $media?->getKey(),
                'featured_image_alt' => filled($altText) ? (string) $altText : $post->featured_image_alt,
                'featured_image_caption' => filled($attachment->post_excerpt) ? (string) $attachment->post_excerpt : $post->featured_image_caption,
            ]);
        });
    }

    private function safeRelativePath(mixed $path): ?string
    {
        $path = $this->paths->normalize((string) $path);

        return $path !== null && ! str_starts_with($path, 'http') ? $path : null;
    }

    private function sourceExists(string $relativePath, object $attachment): ?bool
    {
        try {
            return $this->sourceMedia->exists($relativePath);
        } catch (Throwable $exception) {
            $this->verification->unreadable++;
            $this->logger->warning('WordPress media source could not be read.', [
                'attachment_id' => $attachment->source_id, 'path' => $relativePath, 'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function destinationPath(string $relativePath): string
    {
        return trim((string) config('import.media.destination_path', 'wordpress/uploads'), '/').'/'.$relativePath;
    }

    private function originalUrl(string $relativePath): ?string
    {
        $siteUrl = rtrim((string) config('import.profiles.wordpress.site_url'), '/');

        return $siteUrl === '' ? null : $siteUrl.'/wp-content/uploads/'.ltrim($relativePath, '/');
    }

    /** @param resource|false|null $stream */
    private function hash(mixed $stream): ?string
    {
        if (! is_resource($stream)) {
            return null;
        }

        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        fclose($stream);

        return hash_final($context);
    }

    private function checkpointKey(ImportContext $context): string
    {
        $selection = $context->ids === [] ? 'all' : 'ids-'.sha1(implode(',', $context->ids));

        return "media.{$selection}.offset-{$context->offset}";
    }

    private function memoryMb(): float
    {
        return round(memory_get_usage(true) / 1048576, 2);
    }
}
