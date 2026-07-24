<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable; use Illuminate\Database\Eloquent\Model;
#[Fillable(['event_id','post_id','visitor_uuid','event_type','referrer_class','device_type','is_bot','is_internal','occurred_at'])]
class AnalyticsEvent extends Model { protected function casts(): array{return ['is_bot'=>'boolean','is_internal'=>'boolean','occurred_at'=>'datetime'];} }
