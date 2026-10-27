<?php

namespace Tests\Feature;

use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    private const PAGES = ['/', '/generate/v7', '/generate/guid', '/decode', '/docs', '/what-is-uuid', '/uuid-versions-explained', '/terms', '/contact', '/sitemap'];

    private function inProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
    }

    public function test_ga4_measurement_id_is_the_default(): void
    {
        $this->assertSame('G-2SCNGWDT9C', config('uuidtools.google_analytics_id'));
    }

    public function test_ga4_tag_loads_on_every_public_page_in_production(): void
    {
        $this->inProduction();

        foreach (self::PAGES as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('<script async src="https://www.googletagmanager.com/gtag/js?id=G-2SCNGWDT9C"></script>', false)
                ->assertSee("gtag('config',\"G-2SCNGWDT9C\")", false);
        }

        $this->get('/does-not-exist')->assertNotFound()->assertSee('gtag/js?id=G-2SCNGWDT9C', false);
    }

    public function test_ga4_id_can_be_overridden_or_disabled(): void
    {
        $this->inProduction();

        config(['uuidtools.google_analytics_id' => 'G-OVERRIDE1']);
        $this->get('/')->assertSee('gtag/js?id=G-OVERRIDE1', false)->assertDontSee('G-2SCNGWDT9C');

        config(['uuidtools.google_analytics_id' => null]);
        $this->get('/')->assertDontSee('googletagmanager.com', false);
    }

    public function test_cloudflare_beacon_is_omitted_without_a_token(): void
    {
        $this->inProduction();
        config(['uuidtools.cloudflare_analytics_token' => '']);

        $this->get('/')->assertOk()->assertDontSee('cloudflareinsights', false);
    }

    public function test_cloudflare_beacon_loads_on_every_public_page_with_a_token(): void
    {
        $this->inProduction();
        config(['uuidtools.cloudflare_analytics_token' => 'abc123']);

        foreach (self::PAGES as $path) {
            $this->get($path)->assertOk()->assertSee(
                '<script defer src="https://static.cloudflareinsights.com/beacon.min.js" data-cf-beacon="{&quot;token&quot;:&quot;abc123&quot;}"></script>',
                false,
            );
        }
    }

    public function test_no_tracking_outside_production(): void
    {
        config(['uuidtools.cloudflare_analytics_token' => 'abc123']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('cloudflareinsights', false);
    }
}
