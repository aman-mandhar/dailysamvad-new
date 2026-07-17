<?php

return [
    'per_page' => 12,
    'search_max_length' => 200,
    'search_min_length' => 1,
    'author_archives_enabled' => true,
    'robots' => [
        'category' => 'index, follow',
        'tag' => 'index, follow',
        'search' => 'noindex, follow',
        'date' => 'index, follow',
        'author' => 'index, follow',
    ],
    'sidebar_contexts' => [
        'category' => 'archive',
        'tag' => 'archive',
        'search' => 'archive',
        'date' => 'archive',
        'author' => 'archive',
    ],
    'advertisements' => [
        'category' => ['top' => 'CATEGORY_TOP', 'inline' => 'ARCHIVE_INLINE'],
        'tag' => ['top' => 'TAG_TOP', 'inline' => 'ARCHIVE_INLINE'],
        'search' => ['top' => 'SEARCH_TOP', 'inline' => 'SEARCH_INLINE'],
        'date' => ['top' => 'ARCHIVE_TOP', 'inline' => 'ARCHIVE_INLINE'],
        'author' => ['top' => 'AUTHOR_TOP', 'inline' => 'ARCHIVE_INLINE'],
    ],
    'labels' => [
        'category' => 'Category',
        'tag' => 'Tag',
        'search' => 'Search',
        'date' => 'Archive',
        'author' => 'Author',
    ],
    'empty_states' => [
        'category' => 'No published news is available in this category.',
        'tag' => 'No published news is available for this tag.',
        'search' => 'No published news matched your search.',
        'date' => 'No published news is available for this date.',
        'author' => 'No published news is available from this author.',
    ],
];
