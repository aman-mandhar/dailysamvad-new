<?php

$pages = [
    'about-us' => ['pages.about', 'About Us', 'About Daily Samvad', 'Information about Daily Samvad will be published here.'],
    'contact-us' => ['pages.contact', 'Contact Us', 'Contact Daily Samvad', 'Centralized contact information for Daily Samvad.'],
    'copyright-policy' => ['pages.copyright', 'Copyright Policy', 'Daily Samvad Copyright Policy', 'The official copyright policy will be migrated from the existing website.'],
    'fact-checking-policy' => ['pages.fact-checking', 'Fact-Checking Policy', 'Daily Samvad Fact-Checking Policy', 'The official fact-checking policy will be migrated from the existing website.'],
    'editorial-policy' => ['pages.editorial', 'Editorial Policy', 'Daily Samvad Editorial Policy', 'The official editorial policy will be migrated from the existing website.'],
    'disclaimer' => ['pages.disclaimer', 'Disclaimer', 'Daily Samvad Disclaimer', 'The official disclaimer will be migrated from the existing website.'],
    'terms-and-conditions' => ['pages.terms', 'Terms and Conditions', 'Daily Samvad Terms and Conditions', 'The official terms and conditions will be migrated from the existing website.'],
    'privacy-policy' => ['pages.privacy', 'Privacy Policy', 'Daily Samvad Privacy Policy', 'The official privacy policy will be migrated from the existing website.'],
    'advertising-and-sponsored-content-policy' => ['pages.advertising', 'Advertising and Sponsored Content Policy', 'Advertising and Sponsored Content Policy', 'The official advertising policy will be migrated from the existing website.'],
    'grievance-redressal-policy' => ['pages.grievance', 'Grievance Redressal Policy', 'Grievance Redressal Policy', 'The official grievance redressal policy will be migrated from the existing website.'],
    'dmca-and-copyright-infringement-policy' => ['pages.dmca', 'DMCA and Copyright Infringement Policy', 'DMCA and Copyright Infringement Policy', 'The official DMCA and copyright infringement policy will be migrated from the existing website.'],
];

$configuredPages = [];

foreach ($pages as $slug => $page) {
    $configuredPages[$slug] = [
        'slug' => $slug,
        'route' => $page[0],
        'title' => $page[1],
        'subtitle' => null,
        'last_updated' => null,
        'seo_title' => $page[2],
        'seo_description' => $page[3],
        'robots' => 'index, follow',
        'sections' => [[
            'heading' => 'Content migration pending',
            'paragraphs' => [
                $page[3],
                'This structured placeholder is ready to receive the exact approved content from the existing WordPress page without changing the public layout or URL.',
            ],
        ]],
    ];
}

return $configuredPages;
