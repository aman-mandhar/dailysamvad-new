<?php

namespace Tests\Unit\SEO;

use App\Models\Media;
use App\SEO\SocialImageResolver;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialImageResolverTest extends TestCase
{
    public function test_it_resolves_public_relative_external_and_default_images_to_absolute_urls(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('wordpress/uploads/social.jpg', 'image');
        $resolver = app(SocialImageResolver::class);

        $this->assertSame('http://localhost/images/seo/default-social.png', $resolver->resolve('/images/seo/default-social.png', 'Alt')?->url);
        $this->assertSame('http://localhost/images/seo/default-social.png', $resolver->resolve('images/seo/default-social.png', 'Alt')?->url);
        $this->assertSame('http://localhost/storage/wordpress/uploads/social.jpg', $resolver->resolve('wordpress/uploads/social.jpg', 'Alt')?->url);
        $this->assertSame('https://cdn.example.com/social.jpg', $resolver->resolve('https://cdn.example.com/social.jpg', 'Alt')?->url);
        $this->assertSame('http://localhost/images/seo/default-social.png', $resolver->configuredDefault()?->url);
    }

    public function test_it_rejects_unsafe_and_missing_media_sources(): void
    {
        $resolver = app(SocialImageResolver::class);
        $missing = new Media(['path' => 'missing.jpg', 'missing_at' => now()]);

        foreach (['javascript:alert(1)', 'C:\\private\\image.jpg', '/var/private/image.jpg', "image.jpg\nunsafe"] as $source) {
            $this->assertNull($resolver->resolve($source, 'Alt'));
        }
        $this->assertNull($resolver->resolve('missing.jpg', 'Alt', $missing));
    }

    public function test_it_forces_https_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $image = app(SocialImageResolver::class)->resolve('http://cdn.example.com/social.png', 'Social');

        $this->assertSame('https://cdn.example.com/social.png', $image?->url);
    }
}
