<?php

namespace Tests\Unit;

use App\Uuid\UuidGenerator;
use App\Uuid\UuidInspector;
use App\Uuid\UuidVersion;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UuidGeneratorTest extends TestCase
{
    private UuidGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new UuidGenerator;
    }

    /** @return array<string, array{UuidVersion, int}> */
    public static function versions(): array
    {
        return [
            'v1' => [UuidVersion::V1, 1], 'v2' => [UuidVersion::V2, 2], 'v4' => [UuidVersion::V4, 4],
            'v6' => [UuidVersion::V6, 6], 'v7' => [UuidVersion::V7, 7], 'v8' => [UuidVersion::V8, 8],
            'timestamp-first' => [UuidVersion::TimestampFirst, 4],
        ];
    }

    #[DataProvider('versions')]
    public function test_generates_valid_uuids_with_correct_version_and_variant(UuidVersion $version, int $expected): void
    {
        $inspector = new UuidInspector;

        foreach ($this->generator->generate($version, 5) as $uuid) {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid);
            $result = $inspector->inspect($uuid);
            $this->assertSame($expected, $result['version'], $uuid);
            $this->assertSame('10', $result['variant_bits'], $uuid);
        }
    }

    public function test_generates_requested_count_of_unique_values(): void
    {
        $uuids = $this->generator->generate(UuidVersion::V4, 1000);

        $this->assertCount(1000, $uuids);
        $this->assertCount(1000, array_unique($uuids));
    }

    public function test_v7_values_are_strictly_increasing_even_within_one_millisecond(): void
    {
        $uuids = $this->generator->generate(UuidVersion::V7, 500);
        $sorted = $uuids;
        sort($sorted, SORT_STRING);

        $this->assertSame($sorted, $uuids);
        $this->assertCount(500, array_unique($uuids));
    }

    public function test_v7_embeds_the_current_time(): void
    {
        $before = (int) (microtime(true) * 1000);
        $uuid = $this->generator->generate(UuidVersion::V7)[0];
        $after = (int) (microtime(true) * 1000);

        $ms = (new UuidInspector)->inspect($uuid)['time']['unix_ms'];

        $this->assertGreaterThanOrEqual($before, $ms);
        $this->assertLessThanOrEqual($after, $ms);
    }

    public function test_v1_and_v6_never_expose_a_real_mac_address(): void
    {
        $inspector = new UuidInspector;

        foreach ([UuidVersion::V1, UuidVersion::V2, UuidVersion::V6] as $version) {
            $node = $inspector->inspect($this->generator->generate($version)[0])['node'];
            $this->assertTrue($node['multicast_bit'], $version->name);
        }
    }

    public function test_nil_and_max_ignore_count(): void
    {
        $this->assertSame(['00000000-0000-0000-0000-000000000000'], $this->generator->generate(UuidVersion::Nil, 10));
        $this->assertSame(['ffffffff-ffff-ffff-ffff-ffffffffffff'], $this->generator->generate(UuidVersion::Max, 10));
    }

    public function test_timestamp_first_values_sort_by_creation_time(): void
    {
        $first = $this->generator->generate(UuidVersion::TimestampFirst)[0];
        usleep(2000);
        $second = $this->generator->generate(UuidVersion::TimestampFirst)[0];

        $this->assertLessThan(0, strcmp($first, $second));
    }

    public function test_name_based_matches_rfc_and_reference_implementations(): void
    {
        $this->assertSame('cfbff0d1-9375-5685-968c-48ce8b15ae17', $this->generator->nameBased(UuidVersion::V5, 'ns:dns', 'example.com'));
        $this->assertSame('9073926b-929f-31c2-abc9-fad77ae3e8eb', $this->generator->nameBased(UuidVersion::V3, 'NS:DNS', 'example.com'));
        $this->assertSame('1e030374-bd85-555f-a342-148591e8d651', $this->generator->nameBased(UuidVersion::V5, 'ns:url', 'https://www.uuidtools.com/'));
        $this->assertSame('cfbff0d1-9375-5685-968c-48ce8b15ae17', $this->generator->nameBased(UuidVersion::V5, '6BA7B810-9DAD-11D1-80B4-00C04FD430C8', 'example.com'));
    }

    /** Issue #55: names must be hashed as UTF-8, matching Python/.NET/Java reference values. */
    public function test_name_based_hashes_non_ascii_names_as_utf8(): void
    {
        $namespace = '33b23789-087d-458a-91be-0458615b9b77';

        $this->assertSame('12fc7835-e25c-3ffe-abe9-a73aa487dd57', $this->generator->nameBased(UuidVersion::V3, $namespace, 'lol test balle|lll|lll|æøå'));
        $this->assertSame('41f06a67-bc17-3067-8976-52b9e4219fd1', $this->generator->nameBased(UuidVersion::V3, $namespace, 'lol test balle|lll|lll|𠜎'));
        $this->assertSame('1822ecb3-b48d-59c1-8da0-4ce9afb6f73c', $this->generator->nameBased(UuidVersion::V5, 'ns:dns', 'Français'));
    }

    public function test_invalid_namespace_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->generator->nameBased(UuidVersion::V5, 'not-a-namespace', 'x');
    }

    public function test_name_based_versions_cannot_be_bulk_generated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->generator->generate(UuidVersion::V5);
    }
}
