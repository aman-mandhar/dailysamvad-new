<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class AnalyticsPruneCommand extends Command { protected $signature='analytics:prune {--apply}'; protected $description='Audit retention pruning; dry-run by default'; public function handle():int{$this->warn($this->option('apply')?'No records removed without an explicit bounded retention window.':'Dry-run: no analytics records removed.');return self::SUCCESS;} }
