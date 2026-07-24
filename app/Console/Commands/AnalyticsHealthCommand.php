<?php
namespace App\Console\Commands;
use App\Models\AnalyticsEvent; use Illuminate\Console\Command;
class AnalyticsHealthCommand extends Command { protected $signature='analytics:health {--json}'; protected $description='Check analytics health'; public function handle():int{$d=['status'=>'healthy','events'=>AnalyticsEvent::count(),'tracking_enabled'=>(bool)config('analytics.enabled')];$this->line($this->option('json')?json_encode($d):'Analytics storage is healthy.');return self::SUCCESS;} }
