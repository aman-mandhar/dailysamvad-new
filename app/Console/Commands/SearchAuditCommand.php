<?php
namespace App\Console\Commands;
use App\Models\Post;
use Illuminate\Console\Command;
class SearchAuditCommand extends Command { protected $signature='search:audit {--json}'; protected $description='Audit database search readiness'; public function handle(): int { $data=['posts'=>Post::count(),'published'=>Post::published()->count(),'engine'=>'database','unicode'=>'UTF-8']; $this->line($this->option('json')?json_encode($data):print_r($data,true)); return self::SUCCESS; } }
