<?php

return [
    'web' => [
        'api_key' => env('FIREBASE_WEB_API_KEY'),
        'auth_domain' => env('FIREBASE_WEB_AUTH_DOMAIN'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
        'app_id' => env('FIREBASE_WEB_APP_ID'),
        'measurement_id' => env('FIREBASE_MEASUREMENT_ID'),
        'vapid_key' => env('FIREBASE_VAPID_KEY'),
    ],
    'messaging' => [
        'sending_enabled' => (bool) env('PUSH_SENDING_ENABLED', false),
        'project_id' => env('FIREBASE_MESSAGING_PROJECT_ID', env('FIREBASE_PROJECT_ID')),
        'service_account_path' => env('FIREBASE_SERVICE_ACCOUNT_PATH'),
        'timeout' => (int) env('FIREBASE_MESSAGING_TIMEOUT', 10),
        'connect_timeout' => (int) env('FIREBASE_MESSAGING_CONNECT_TIMEOUT', 5),
        'token_expiry_margin' => (int) env('FIREBASE_OAUTH_EXPIRY_MARGIN', 300),
        'queue' => env('FIREBASE_PUSH_QUEUE', 'push'),
        'fanout_chunk_size' => (int) env('PUSH_FANOUT_CHUNK_SIZE', 500),
        'job_tries' => (int) env('PUSH_JOB_TRIES', 4),
        'job_timeout' => (int) env('PUSH_JOB_TIMEOUT', 30),
        'job_backoff' => array_map('intval', explode(',', (string) env('PUSH_JOB_BACKOFF', '60,300,900'))),
        'oauth_lock_seconds' => (int) env('FIREBASE_OAUTH_LOCK_SECONDS', 15),
        'default_icon' => env('FIREBASE_PUSH_DEFAULT_ICON'),
    ],
    'security' => [
        'subscription_limit' => (int) env('PUSH_SUBSCRIPTION_RATE_LIMIT', 30),
        'preference_read_limit' => (int) env('PUSH_PREFERENCE_READ_RATE_LIMIT', 60),
        'preference_write_limit' => (int) env('PUSH_PREFERENCE_WRITE_RATE_LIMIT', 20),
        'click_limit' => (int) env('PUSH_CLICK_RATE_LIMIT', 240),
        'manual_send_limit' => (int) env('PUSH_MANUAL_SEND_RATE_LIMIT', 6),
    ],
    'maintenance' => [
        'stuck_after_minutes' => (int) env('PUSH_STUCK_AFTER_MINUTES', 15),
        'inactive_retention_days' => (int) env('PUSH_INACTIVE_RETENTION_DAYS', 90),
        'batch_size' => (int) env('PUSH_MAINTENANCE_BATCH_SIZE', 500),
    ],
    'automation' => [
        'enabled' => (bool) env('PUSH_AUTO_PUBLISH_ENABLED', false),
        'body_length' => (int) env('PUSH_AUTO_PUBLISH_BODY_LENGTH', 180),
    ],
];
