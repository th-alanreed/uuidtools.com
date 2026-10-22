<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Tests\TestCase;

class UrlSchemeTest extends TestCase
{
    public function test_https_app_url_forces_https_links_on_plain_http_requests(): void
    {
        config(['app.url' => 'https://uuidtools.com']);
        (new AppServiceProvider($this->app))->boot();

        $response = $this->get('http://uuidtools.com/')->assertOk();

        $response->assertSee('data-api="https://uuidtools.com/api/generate/v4"', false);
        $response->assertSee('href="https://uuidtools.com/css/app.css', false);
        $response->assertDontSee('"http://uuidtools.com', false);

        $this->assertStringNotContainsString('http://uuidtools.com', $this->get('http://uuidtools.com/sitemap.xml')->getContent());
    }

    public function test_http_app_url_keeps_http_for_local_development(): void
    {
        $this->assertSame('http://localhost', config('app.url'));

        $this->get('http://localhost/')
            ->assertOk()
            ->assertSee('data-api="http://localhost/api/generate/v4"', false);
    }

    public function test_api_allows_cross_origin_requests_from_the_https_site(): void
    {
        config(['app.url' => 'https://uuidtools.com']);
        (new AppServiceProvider($this->app))->boot();

        $this->getJson('http://uuidtools.com/api/generate/v4', ['Origin' => 'https://uuidtools.com'])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*');

        $this->call('OPTIONS', 'http://uuidtools.com/api/generate/v4', server: [
            'HTTP_ORIGIN' => 'https://uuidtools.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'accept',
        ])->assertNoContent()->assertHeader('Access-Control-Allow-Origin', '*');
    }
}
