<?php

namespace Tests\Feature\Import;

use App\Import\Contracts\Importer;
use App\Import\Contracts\Logger;
use App\Import\Contracts\MediaImporter;
use App\Import\Contracts\TaxonomyImporter;
use App\Import\Contracts\Verifier;
use App\Import\DTOs\ImportContext;
use App\Import\DTOs\ImportProgress;
use App\Import\DTOs\ImportStatistics;
use App\Import\Support\ImportMode;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ImportFoundationTest extends TestCase
{
    public function test_command_exists(): void
    {
        $code = Artisan::call('help', ['command_name' => 'import:wordpress']);
        $this->assertSame(0, $code);
        $this->assertStringContainsString('pilot posts, and media', Artisan::output());
    }

    public function test_configuration_loads(): void
    {
        $this->assertSame(500, config('import.chunk_size'));
        $this->assertArrayHasKey('timeouts', config('import'));
    }

    public function test_typed_context_dto_works(): void
    {
        $context = new ImportContext('run-1', 'wordpress', new ImportProgress(5, 10), 100, ImportMode::DryRun, new ImportStatistics(imported: 5));
        $this->assertSame(50.0, $context->progress->percentage());
        $this->assertSame(5, $context->statistics->imported);
    }

    public function test_contracts_exist_and_logger_resolves(): void
    {
        foreach ([Importer::class, MediaImporter::class, TaxonomyImporter::class, Logger::class, Verifier::class] as $contract) {
            $this->assertTrue(interface_exists($contract));
        }
        $this->assertInstanceOf(Logger::class, app(Logger::class));
    }
}
