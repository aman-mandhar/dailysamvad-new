<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PostDailyMetric extends Model { protected $table='post_daily_metrics'; protected $guarded=[]; protected function casts(): array{return ['metric_date'=>'date'];} }
