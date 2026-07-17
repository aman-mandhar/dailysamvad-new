<?php

namespace App\Import\Commands;

use App\Import\Services\ImportReportStore;
use App\Import\Services\ImportVerificationService;
use App\Import\Services\RedirectGenerator;
use Illuminate\Console\Command;
use InvalidArgumentException;

class VerifyWordPressImportCommand extends Command
{
    protected $signature = 'import:verify
        {--format=* : Redirect formats: csv, json, apache, nginx, laravel}
        {--limit= : Limit imported posts checked}';

    protected $description = 'Verify imported WordPress content and export production redirects';

    public function handle(
        ImportVerificationService $verifier,
        RedirectGenerator $redirects,
        ImportReportStore $reports,
    ): int {
        $formats = collect($this->option('format'))->flatMap(fn (string $value) => explode(',', $value))->filter()->values()->all();
        $formats = $formats ?: ['csv', 'json', 'apache', 'nginx', 'laravel'];
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        if ($limit !== null && $limit < 1) {
            $this->error('The --limit value must be a positive integer.');

            return self::INVALID;
        }

        try {
            $exports = $redirects->export($formats);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        $report = $verifier->detailedReport($limit);
        $report['redirect_exports'] = $exports;
        $path = $reports->store('verification-latest', $report);

        $this->table(array_keys($report['summary']), [array_values($report['summary'])]);
        $this->info("Verification report written to {$path}.");
        foreach ($exports as $format => $exportPath) {
            $this->line("{$format}: {$exportPath}");
        }

        return self::SUCCESS;
    }
}
