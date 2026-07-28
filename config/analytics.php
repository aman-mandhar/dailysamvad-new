<?php

return ['enabled' => (bool) env('ANALYTICS_ENABLED', true), 'beacon_enabled' => (bool) env('ANALYTICS_BEACON_ENABLED', true), 'dedupe_minutes' => 30, 'retention_days' => 730, 'queue' => 'analytics'];
