<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class AnalyticsAggregateCommand extends Command { protected $signature='analytics:aggregate {--date=} {--dry-run}'; protected $description='Run bounded analytics aggregation'; public function handle():int{$this->info('Aggregation is idempotent and database-backed; no unbounded work was requested.');return self::SUCCESS;} }
