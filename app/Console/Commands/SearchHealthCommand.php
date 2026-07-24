<?php
namespace App\Console\Commands;
use App\Models\Post;
use Illuminate\Console\Command;
class SearchHealthCommand extends Command { protected $signature='search:health {--json}'; protected $description='Check database search health'; public function handle(): int { $data=['status'=>'healthy','published'=>Post::published()->count(),'external_engine'=>'not-configured']; $this->line($this->option('json')?json_encode($data):'Search database is healthy.'); return self::SUCCESS; } }
