<?php

return [
    'disk' => env('MEDIA_DISK', 'public'),
    'wordpress_path' => env('IMPORT_MEDIA_PATH', 'wordpress/uploads'),
    'managed_paths' => ['posts/featured'],
    'library_path' => env('MEDIA_LIBRARY_PATH', 'media/library'),
    'max_upload_kilobytes' => (int) env('MEDIA_MAX_UPLOAD_KILOBYTES', 10240),
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    'executable_extensions' => ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'cgi', 'pl', 'py', 'sh', 'exe', 'bat', 'cmd', 'com'],
];
