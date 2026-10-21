<?php

namespace Tests\Feature;

use App\Uuid\GeneratorPage;
use Tests\TestCase;

class PagesTest extends TestCase
{
    public function test_static_pages_render(): void
    {
        foreach (['/', '/decode', '/docs', '/what-is-uuid', '/uuid-versions-explained', '/terms', '/contact', '/sitemap'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_every_generator_page_renders_with_seo_basics(): void
    {
        foreach (array_keys(GeneratorPage::all()) as $slug) {
            $this->get("/generate/{$slug}")
                ->assertOk()
                ->assertSee('<link rel="canonical"', false)
                ->assertSee('application/ld+json', false);
        }
    }

    public function test_generator_pages_render_uuids_without_javascript(): void
    {
        $this->get('/generate/v4')->assertOk()->assertSeeInOrder(['<textarea', '-4']);
    }

    public function test_unknown_generator_page_is_404(): void
    {
        $this->get('/generate/v99')->assertNotFound();
    }

    public function test_legacy_urls_redirect_permanently(): void
    {
        $this->get('/v4')->assertRedirect('/generate/v4')->assertStatus(301);
        $this->get('/minecraft')->assertRedirect('/generate/minecraft')->assertStatus(301);
        $this->get('/generator/v1')->assertRedirect('/generate/v1')->assertStatus(301);
        $this->get('/generate')->assertRedirect('/')->assertStatus(301);
    }

    public function test_decode_page_with_valid_and_invalid_uuid(): void
    {
        $this->get('/decode/00000000-0000-1000-8000-000000000000')->assertOk()->assertSee('Version 1');
        $this->get('/decode?uuid=nonsense')->assertOk()->assertSee('Not a valid UUID');
    }

    public function test_sitemap_xml_lists_pages(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringStartsWith('<?xml', $response->getContent());
        $this->assertStringContainsString('/generate/v7', $response->getContent());
    }

    public function test_health_check(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_pages_do_not_set_cookies(): void
    {
        $this->assertCount(0, $this->get('/')->headers->getCookies());
    }

    public function test_unknown_url_renders_the_404_page(): void
    {
        $this->get('/nope')->assertNotFound();
    }
}
