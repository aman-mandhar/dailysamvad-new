<?php

namespace Tests\Feature\Frontend;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StaticPagesTest extends TestCase
{
    public function test_every_configured_static_page_returns_successfully(): void
    {
        foreach (config('static-pages') as $page) {
            $this->get(route($page['route']))
                ->assertOk()
                ->assertSee($page['title'])
                ->assertSee('<link rel="canonical" href="'.route($page['route']).'">', false);
        }
    }

    public function test_unknown_static_page_returns_not_found(): void
    {
        $this->get('/unknown-policy-page')->assertNotFound();
    }

    public function test_footer_policy_links_render(): void
    {
        $this->get(route('pages.about'))
            ->assertOk()
            ->assertSee(route('pages.copyright'), false)
            ->assertSee(route('pages.privacy'), false)
            ->assertSee(route('pages.contact'), false);
    }

    public function test_contact_details_come_from_central_configuration(): void
    {
        config()->set('organization.phone', '+91 12345 67890');
        config()->set('organization.email', 'contact@daily.test');
        config()->set('organization.social_links.facebook', 'https://facebook.test/daily');

        $this->get(route('pages.contact'))
            ->assertOk()
            ->assertSee('+91 12345 67890')
            ->assertSee('contact@daily.test')
            ->assertSee('https://facebook.test/daily', false);
    }

    public function test_route_names_are_unique(): void
    {
        $names = collect(Route::getRoutes()->getRoutes())->map->getName()->filter();

        $this->assertSame($names->count(), $names->unique()->count());
    }
}
