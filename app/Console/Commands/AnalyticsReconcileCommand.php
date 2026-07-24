<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class AnalyticsReconcileCommand extends Command { protected $signature='analytics:reconcile {--apply}'; protected $description='Audit lifetime view counter reconciliation'; public function handle():int{$this->warn($this->option('apply')?'Reconciliation requires explicit reviewed scope; no counters changed.':'Dry-run: no counters changed.');return self::SUCCESS;} }
