<?php
namespace App\Console\Commands;
use App\Models\AnalyticsEvent; use App\Models\PostDailyMetric; use Illuminate\Console\Command;
class AnalyticsAuditCommand extends Command { protected $signature='analytics:audit {--json}'; protected $description='Audit analytics storage and configuration'; public function handle():int{$d=['enabled'=>(bool)config('analytics.enabled'),'events'=>AnalyticsEvent::count(),'daily_metrics'=>PostDailyMetric::count(),'queue'=>config('analytics.queue')];$this->line($this->option('json')?json_encode($d):print_r($d,true));return self::SUCCESS;} }
