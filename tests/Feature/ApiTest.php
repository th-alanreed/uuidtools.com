<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiTest extends TestCase
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    public function test_generates_a_json_array_by_default(): void
    {
        $response = $this->getJson('/api/generate/v4')->assertOk();

        $this->assertCount(1, $response->json());
        $this->assertMatchesRegularExpression(self::UUID_PATTERN, $response->json(0));
    }

    public function test_every_version_generates(): void
    {
        foreach (['v1', 'v2', 'v4', 'v6', 'v7', 'v8', 'nil', 'max', 'timestamp-first'] as $version) {
            $this->getJson("/api/generate/{$version}")->assertOk()->assertJsonCount(1);
        }
    }

    public function test_count_via_path_and_query(): void
    {
        $this->getJson('/api/generate/v7/count/5')->assertOk()->assertJsonCount(5);
        $this->getJson('/api/generate/v7?count=3')->assertOk()->assertJsonCount(3);
    }

    public function test_count_limits(): void
    {
        $this->getJson('/api/generate/v4?count=1001')->assertStatus(400)->assertJsonStructure(['message', 'errors' => ['count']]);
        $this->getJson('/api/generate/v4?count=0')->assertStatus(400);
        $this->getJson('/api/generate/v4?count=1000')->assertOk()->assertJsonCount(1000);
    }

    public function test_plain_text_output(): void
    {
        $response = $this->get('/api/generate/v4/count/3?format=text')->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $this->assertCount(3, explode("\n", trim($response->getContent())));
    }

    public function test_accept_header_selects_plain_text(): void
    {
        $response = $this->get('/api/generate/v4', ['Accept' => 'text/plain'])->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
    }

    public function test_formatting_options(): void
    {
        $text = $this->get('/api/generate/nil?format=text&upper=1&braces=1&hyphens=0')->assertOk()->getContent();

        $this->assertSame('{00000000000000000000000000000000}', trim($text));
        $this->assertSame(
            'urn:uuid:ffffffff-ffff-ffff-ffff-ffffffffffff',
            trim($this->get('/api/generate/max?format=text&urn=1')->getContent()),
        );
    }

    public function test_name_based_query_form(): void
    {
        $this->getJson('/api/generate/v5?namespace=ns:DNS&name=example.com')
            ->assertOk()
            ->assertExactJson(['cfbff0d1-9375-5685-968c-48ce8b15ae17']);

        $this->getJson('/api/generate/v3?namespace=ns:DNS&name=www.example.com')
            ->assertOk()
            ->assertExactJson(['5df41881-3aed-3515-88a7-2f4a814cf09e']);
    }

    public function test_name_based_path_form(): void
    {
        $this->getJson('/api/generate/v5/namespace/ns:DNS/name/example.com')
            ->assertOk()
            ->assertExactJson(['cfbff0d1-9375-5685-968c-48ce8b15ae17']);
    }

    /** Issue #55: names are hashed as UTF-8 bytes, not as latin-1 or mangled text. */
    public function test_name_based_uses_utf8_for_non_ascii_names(): void
    {
        $expected = $this->getJson('/api/generate/v5?namespace=ns:DNS&name='.rawurlencode('café 🎉'))
            ->assertOk()
            ->json(0);

        $this->assertSame(
            $expected,
            $this->getJson('/api/generate/v5?namespace=ns:DNS&name=base64:'.rawurlencode(base64_encode('café 🎉')))->json(0),
        );
        $this->assertSame(
            $expected,
            $this->getJson('/api/generate/v5?namespace=ns:DNS&name=base64:'.rawurlencode(rtrim(strtr(base64_encode('café 🎉'), '+/', '-_'), '=')))->json(0),
        );
        $this->assertNotSame(
            $expected,
            $this->getJson('/api/generate/v5?namespace=ns:DNS&name=cafe')->json(0),
        );
    }

    public function test_name_based_validation(): void
    {
        $this->getJson('/api/generate/v5')->assertStatus(400)->assertJsonStructure(['errors' => ['namespace']]);
        $this->getJson('/api/generate/v5?namespace=nonsense&name=x')->assertStatus(400);
        $this->getJson('/api/generate/v5?namespace=ns:DNS&name=base64:***')->assertStatus(400)->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_unknown_version_is_a_json_404(): void
    {
        $this->getJson('/api/generate/v9')->assertNotFound()->assertJsonStructure(['message']);
    }

    /** Issue #57: decoding must not fail for valid UUIDs, including the all-zero v1. */
    public function test_decode_never_errors_for_valid_uuids(): void
    {
        foreach ([
            '00000000-0000-1000-8000-000000000000',
            '00000000-0000-0000-0000-000000000000',
            'ffffffff-ffff-ffff-ffff-ffffffffffff',
            '123e4567-e89b-12d3-a456-426614174000',
        ] as $uuid) {
            $this->getJson("/api/decode/{$uuid}")->assertOk()->assertJsonPath('valid', true);
        }
    }

    public function test_decode_reports_version_and_time(): void
    {
        $this->getJson('/api/decode/017f22e2-79b0-7cc3-98c4-dc0c0c07398f')
            ->assertOk()
            ->assertJsonPath('version', 7)
            ->assertJsonPath('variant', 'RFC 9562 (formerly RFC 4122 / DCE 1.1)');
    }

    public function test_decode_rejects_invalid_input(): void
    {
        $this->getJson('/api/decode/not-a-uuid')->assertStatus(400)->assertJsonPath('valid', false);
    }

    public function test_validate_always_returns_200(): void
    {
        $this->getJson('/api/validate/123e4567-e89b-12d3-a456-426614174000')->assertOk()->assertJsonPath('valid', true);
        $this->getJson('/api/validate/not-a-uuid')->assertOk()->assertExactJson(['valid' => false]);
    }

    public function test_cors_is_open(): void
    {
        $this->getJson('/api/generate/v4', ['Origin' => 'https://example.org'])
            ->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function test_api_is_rate_limited(): void
    {
        config(['uuidtools.api_rate_limit' => 2]);

        $this->getJson('/api/generate/v4')->assertOk();
        $this->getJson('/api/generate/v4')->assertOk();
        $this->getJson('/api/generate/v4')->assertStatus(429)->assertJsonStructure(['message']);
    }
}
