<?php
namespace App\Console\Commands;
use App\Models\Post;
use Illuminate\Console\Command;
class SearchReindexCommand extends Command { protected $signature='search:reindex {--limit=100} {--dry-run}'; protected $description='Bounded, non-destructive search index audit'; public function handle(): int { $count=Post::published()->limit(min(1000,max(1,(int)$this->option('limit'))))->count(); $this->info(($this->option('dry-run')?'Would inspect ':'Inspected ').$count.' published posts; database remains authoritative.'); return self::SUCCESS; } }
