<?php

return [
    'enabled' => (bool) env('IMAGE_OPTIMIZATION_ENABLED', false),
    'disk' => env('MEDIA_DISK', 'public'),
    'derivative_path' => 'media/derivatives',
    'version' => 'v1',
    'quality' => ['jpeg' => 82, 'webp' => 82, 'avif' => 65, 'png' => 6],
    'max_pixels' => 40000000,
    'presets' => [
        'thumb' => ['width' => 320, 'height' => 180],
        'card' => ['width' => 640, 'height' => 360],
        'article' => ['width' => 1280, 'height' => 720],
        'hero' => ['width' => 1600, 'height' => 900],
    ],
    'responsive_widths' => [320, 640, 960, 1280, 1600],
    'formats' => ['webp' => true, 'avif' => true],
    'queue' => 'media',
];
