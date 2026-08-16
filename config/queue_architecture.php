<?php

return [
    'enabled' => (bool) env('QUEUE_ARCHITECTURE_ENABLED', false),
    'probe_queue' => env('QUEUE_PROBE_QUEUE', 'maintenance'),
    'queues' => [
        'critical' => ['publishing'],
        'default' => ['default'],
        'external' => ['external'],
        'push' => ['push'],
        'maintenance' => ['maintenance'],
    ],
    'workers' => [
        'publishing' => ['tries' => 3, 'timeout' => 30, 'max_jobs' => 500, 'max_time' => 3600, 'memory' => 256],
        'external' => ['tries' => 3, 'timeout' => 30, 'max_jobs' => 250, 'max_time' => 1800, 'memory' => 192],
        'push' => ['tries' => 4, 'timeout' => 30, 'max_jobs' => 250, 'max_time' => 1800, 'memory' => 192],
        'maintenance' => ['tries' => 2, 'timeout' => 30, 'max_jobs' => 250, 'max_time' => 1800, 'memory' => 128],
    ],
];
