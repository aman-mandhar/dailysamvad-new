<?php
return ['enabled' => (bool) env('ANALYTICS_ENABLED', false), 'beacon_enabled' => (bool) env('ANALYTICS_BEACON_ENABLED', false), 'dedupe_minutes' => 30, 'retention_days' => 730, 'queue' => 'analytics'];
