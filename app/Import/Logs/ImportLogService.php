<?php

namespace App\Import\Logs;

use App\Import\Contracts\Logger;
use App\Import\DTOs\ImportStatistics;
use App\Import\Services\ImportReportStore;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

class ImportLogService implements Logger
{
    private LoggerInterface $logger;

    public function __construct(LogManager $logs, private readonly ImportReportStore $reports)
    {
        $this->logger = $logs->channel(config('import.logging.channel', 'stack'));
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    public function success(string $message, array $context = []): void
    {
        $this->write('info', $message, ['status' => 'success', ...$context]);
    }

    public function summary(ImportStatistics $statistics): void
    {
        $this->write('info', 'WordPress import summary.', $statistics->toArray());
    }

    /** @param array<string, mixed> $context */
    private function write(string $level, string $message, array $context): void
    {
        if (config('import.logging.enabled', true)) {
            $this->logger->{$level}($message, ['import' => $context]);
            $this->reports->recordEvent($level, $message, $context);
        }
    }
}
