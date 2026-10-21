<?php

namespace Tests\Unit;

use App\Uuid\UuidInspector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UuidInspectorTest extends TestCase
{
    private UuidInspector $inspector;

    protected function setUp(): void
    {
        $this->inspector = new UuidInspector;
    }

    /** Issue #57: this all-zero v1 UUID crashed the old `uuid -d` based decoder. */
    public function test_v1_uuid_with_zero_timestamp_decodes_without_error(): void
    {
        $result = $this->inspector->inspect('00000000-0000-1000-8000-000000000000');

        $this->assertSame(1, $result['version']);
        $this->assertSame('1582-10-15T00:00:00.0000000Z', $result['time']['iso8601']);
        $this->assertSame(0, $result['clock_sequence']);
        $this->assertSame('00:00:00:00:00:00', $result['node']['mac']);
    }

    public function test_v1_decodes_timestamp_clock_and_node(): void
    {
        $result = $this->inspector->inspect('b01eb720-171a-11ea-b949-73c91bba743d');

        $this->assertSame(1, $result['version']);
        $this->assertSame('2019-12-05T04:49:57.9612960Z', $result['time']['iso8601']);
        $this->assertSame(1575521397, $result['time']['unix_seconds']);
        $this->assertSame(14665, $result['clock_sequence']);
        $this->assertSame('73:c9:1b:ba:74:3d', $result['node']['mac']);
        $this->assertTrue($result['node']['multicast_bit']);
        $this->assertSame('RFC 9562 (formerly RFC 4122 / DCE 1.1)', $result['variant']);
    }

    public function test_v6_decodes_same_instant_as_equivalent_v1(): void
    {
        $v1 = $this->inspector->inspect('b01eb720-171a-11ea-b949-73c91bba743d');
        $v6 = $this->inspector->inspect('1ea171ab-01eb-6720-b949-73c91bba743d');

        $this->assertSame(6, $v6['version']);
        $this->assertSame($v1['time']['iso8601'], $v6['time']['iso8601']);
        $this->assertTrue($v6['sortable_by_time']);
    }

    public function test_v7_decodes_millisecond_timestamp(): void
    {
        $result = $this->inspector->inspect('0190a5b2-3c4d-7e5f-8a1b-2c3d4e5f6a7b');

        $this->assertSame(7, $result['version']);
        $this->assertSame('2024-07-12T06:47:19.885Z', $result['time']['iso8601']);
        $this->assertSame(1720766839885, $result['time']['unix_ms']);
        $this->assertTrue($result['sortable_by_time']);
    }

    public function test_v4_has_no_timestamp(): void
    {
        $result = $this->inspector->inspect('f47ac10b-58cc-4372-a567-0e02b2c3d479');

        $this->assertSame(4, $result['version']);
        $this->assertNull($result['time']);
        $this->assertSame('Random', $result['version_name']);
    }

    public function test_v2_reports_domain_and_identifier(): void
    {
        $result = $this->inspector->inspect('000001f5-1e0d-21f0-8100-73c91bba743d');

        $this->assertSame(2, $result['version']);
        $this->assertSame(501, $result['local_identifier']);
        $this->assertSame('Person (POSIX UID)', $result['local_domain']['name']);
    }

    public function test_v3_and_v5_are_identified(): void
    {
        $this->assertSame(3, $this->inspector->inspect('9073926b-929f-31c2-abc9-fad77ae3e8eb')['version']);
        $this->assertSame(5, $this->inspector->inspect('cfbff0d1-9375-5685-968c-48ce8b15ae17')['version']);
    }

    public function test_v8_is_identified_as_custom(): void
    {
        $result = $this->inspector->inspect('320c3d4d-cc00-875b-8ec9-32d5f69181c0');

        $this->assertSame(8, $result['version']);
        $this->assertNull($result['time']);
    }

    public function test_nil_and_max(): void
    {
        $nil = $this->inspector->inspect('00000000-0000-0000-0000-000000000000');
        $this->assertTrue($nil['is_nil']);
        $this->assertSame('Nil UUID', $nil['version_name']);
        $this->assertNull($nil['version']);

        $max = $this->inspector->inspect('FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF');
        $this->assertTrue($max['is_max']);
        $this->assertSame('Max UUID', $max['version_name']);
        $this->assertSame('340282366920938463463374607431768211455', $max['formats']['integer']);
    }

    public function test_microsoft_variant_does_not_claim_a_version(): void
    {
        $result = $this->inspector->inspect('6ba7b810-9dad-11d1-c0b4-00c04fd430c8');

        $this->assertNull($result['version']);
        $this->assertNull($result['time']);
        $this->assertStringContainsString('Microsoft', $result['variant']);
    }

    public function test_reserved_version_digits_do_not_crash(): void
    {
        foreach (['0', '9', 'a', 'f'] as $digit) {
            $result = $this->inspector->inspect("12345678-1234-{$digit}234-8234-123456789abc");

            $this->assertSame(hexdec($digit), $result['version']);
            $this->assertNotEmpty($result['notes']);
        }
    }

    /** @return array<string, array{string}> */
    public static function equivalentNotations(): array
    {
        $uuid = '123e4567-e89b-12d3-a456-426614174000';

        return [
            'standard' => [$uuid],
            'uppercase' => [strtoupper($uuid)],
            'no hyphens' => [str_replace('-', '', $uuid)],
            'braces' => ['{'.$uuid.'}'],
            'urn' => ['urn:uuid:'.$uuid],
            'quoted and padded' => ["  \"{$uuid}\"  "],
        ];
    }

    #[DataProvider('equivalentNotations')]
    public function test_accepts_common_notations(string $input): void
    {
        $result = $this->inspector->inspect($input);

        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $result['uuid']);
        $this->assertSame('24249434048109030647017182301789831168', $result['formats']['integer']);
    }

    /** @return array<string, array{string}> */
    public static function invalidInputs(): array
    {
        return [
            'empty' => [''],
            'text' => ['not-a-uuid'],
            'too short' => ['123e4567-e89b-12d3-a456-42661417400'],
            'too long' => ['123e4567-e89b-12d3-a456-4266141740000'],
            'bad hex' => ['123e4567-e89b-12d3-a456-42661417400g'],
            'misplaced hyphens' => ['123e4567e-89b-12d3-a456-426614174000'],
            'newline injection' => ["123e4567-e89b-12d3-a456-426614174000\nx"],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_rejects_invalid_input(string $input): void
    {
        $this->assertNull($this->inspector->inspect($input));
    }

    public function test_guid_little_endian_formats(): void
    {
        $formats = $this->inspector->inspect('00112233-4455-6677-8899-aabbccddeeff')['formats'];

        $this->assertSame('33221100554477668899aabbccddeeff', $formats['guid_bytes_le']);
        $this->assertSame('{00112233-4455-6677-8899-AABBCCDDEEFF}', $formats['braces']);
        $this->assertSame('urn:uuid:00112233-4455-6677-8899-aabbccddeeff', $formats['urn']);
        $this->assertSame('ABEiM0RVZneImaq7zN3u/w==', $formats['base64']);
    }

    public function test_legacy_decode_shape_is_preserved(): void
    {
        $result = $this->inspector->inspect('b01eb720-171a-11ea-b949-73c91bba743d');

        $this->assertSame('b01eb720-171a-11ea-b949-73c91bba743d', $result['encode']['STR']);
        $this->assertSame('234103610387309579079392911688732406845', $result['encode']['SIV']);
        $this->assertArrayHasKey('time', $result['decode']['contents']);
    }
}
