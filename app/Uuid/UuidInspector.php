<?php

namespace App\Uuid;

use Brick\Math\BigInteger;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Decodes any 128-bit UUID/GUID string per RFC 9562 without assuming it is well-formed
 * for its version: every bit pattern (nil, max, unknown versions, legacy variants)
 * produces a result instead of an error.
 */
final class UuidInspector
{
    /** 100-ns intervals between 1582-10-15 (Gregorian epoch) and 1970-01-01 (Unix epoch). */
    private const int GREGORIAN_OFFSET = 122_192_928_000_000_000;

    private const array VERSION_NAMES = [
        0 => 'Unused',
        1 => 'Gregorian time-based',
        2 => 'DCE Security',
        3 => 'Name-based (MD5)',
        4 => 'Random',
        5 => 'Name-based (SHA-1)',
        6 => 'Reordered Gregorian time-based',
        7 => 'Unix Epoch time-based',
        8 => 'Custom (vendor-specific)',
    ];

    /**
     * Accepts "xxxxxxxx-xxxx-…", 32 bare hex digits, "{…}" (Microsoft), "urn:uuid:…",
     * optional surrounding quotes/whitespace, and any letter case.
     *
     * @return string|null 32 lowercase hex digits, or null when the input is not a UUID
     */
    public static function normalize(string $input): ?string
    {
        $value = strtolower(trim($input, " \t\n\r\0\x0B\"'"));

        if (str_starts_with($value, 'urn:uuid:')) {
            $value = substr($value, 9);
        }

        if (str_starts_with($value, '{') && str_ends_with($value, '}')) {
            $value = substr($value, 1, -1);
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D', $value)) {
            return str_replace('-', '', $value);
        }

        if (preg_match('/^[0-9a-f]{32}$/D', $value)) {
            return $value;
        }

        return null;
    }

    public static function hyphenate(string $hex): string
    {
        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20);
    }

    /**
     * @return array<string, mixed>|null null when the input is not a UUID
     */
    public function inspect(string $input): ?array
    {
        $hex = self::normalize($input);

        if ($hex === null) {
            return null;
        }

        $uuid = self::hyphenate($hex);
        $isNil = $hex === str_repeat('0', 32);
        $isMax = $hex === str_repeat('f', 32);
        [$variant, $variantBits] = $this->variant($hex);
        $isRfc = $variantBits === '10';
        $versionNibble = hexdec($hex[12]);
        $version = $isRfc ? $versionNibble : null;

        $result = [
            'valid' => true,
            'uuid' => $uuid,
            'version' => $version,
            'version_name' => $this->versionName($hex, $isRfc, $versionNibble),
            'variant' => $variant,
            'variant_bits' => $variantBits,
            'is_nil' => $isNil,
            'is_max' => $isMax,
            'time' => null,
            'clock_sequence' => null,
            'node' => null,
            'sortable_by_time' => $isRfc && in_array($version, [6, 7], true),
            'notes' => [],
            'fields' => [],
            'formats' => $this->formats($hex),
        ];

        if ($isRfc) {
            $result = $this->decodeRfc($hex, $version, $result);
        } elseif ($isNil) {
            $result['notes'][] = 'The Nil UUID (RFC 9562 section 5.9) has all 128 bits set to zero. It is used to represent "no UUID".';
            $result['fields'] = [['name' => 'nil', 'hex' => $hex, 'description' => 'All bits zero']];
        } elseif ($isMax) {
            $result['notes'][] = 'The Max UUID (RFC 9562 section 5.10) has all 128 bits set to one. It is often used as a sentinel or upper bound.';
            $result['fields'] = [['name' => 'max', 'hex' => $hex, 'description' => 'All bits one']];
        } else {
            $result['notes'][] = 'This is not an RFC 9562 variant UUID, so the version digit has no standard meaning.';
            $result['fields'] = [['name' => 'data', 'hex' => $hex, 'description' => 'Uninterpreted 128 bits']];
        }

        $result['encode'] = ['STR' => $uuid, 'SIV' => $result['formats']['integer']];
        $result['decode'] = $this->legacyDecode($result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function decodeRfc(string $hex, int $version, array $result): array
    {
        $result['fields'] = $this->fields($hex, $version);

        switch ($version) {
            case 1:
            case 6:
                $ticks = $version === 1
                    ? (hexdec(substr($hex, 13, 3)) << 48) | (hexdec(substr($hex, 8, 4)) << 32) | hexdec(substr($hex, 0, 8))
                    : (hexdec(substr($hex, 0, 8)) << 28) | (hexdec(substr($hex, 8, 4)) << 12) | hexdec(substr($hex, 13, 3));
                $result['time'] = $this->gregorianTime($ticks, 'Exact to 100 nanoseconds');
                $result['clock_sequence'] = hexdec(substr($hex, 16, 4)) & 0x3FFF;
                $result['node'] = $this->node(substr($hex, 20, 12));
                $result['notes'][] = $version === 1
                    ? 'Version 1 UUIDs reveal when they were made and, unless the multicast bit is set, the MAC address of the machine that made them. For new systems RFC 9562 recommends v7.'
                    : 'Version 6 is version 1 with the timestamp reordered (most significant bits first) so the UUIDs sort by creation time.';
                break;

            case 2:
                $ticks = (hexdec(substr($hex, 13, 3)) << 48) | (hexdec(substr($hex, 8, 4)) << 32);
                $result['time'] = $this->gregorianTime($ticks, 'Approximate: the low 32 bits of the timestamp are replaced by the local identifier, so the true time is up to ~7 minutes later');
                $result['clock_sequence'] = hexdec(substr($hex, 16, 2)) & 0x3F;
                $result['node'] = $this->node(substr($hex, 20, 12));
                $domain = hexdec(substr($hex, 18, 2));
                $result['local_domain'] = ['value' => $domain, 'name' => [0 => 'Person (POSIX UID)', 1 => 'Group (POSIX GID)', 2 => 'Organization'][$domain] ?? 'Site-defined'];
                $result['local_identifier'] = hexdec(substr($hex, 0, 8));
                $result['notes'][] = 'Version 2 (DCE Security) is reserved by RFC 9562 but its layout is defined by the DCE 1.1 spec. It is rarely used.';
                break;

            case 3:
            case 5:
                $result['notes'][] = sprintf(
                    'Version %d UUIDs are a %s hash of a namespace and a name. The hash cannot be reversed, but the same namespace and name always produce this same UUID.',
                    $version,
                    $version === 3 ? 'MD5' : 'SHA-1',
                );
                break;

            case 4:
                $result['notes'][] = 'Version 4 UUIDs carry 122 random bits and no other information. There is no timestamp to recover.';
                break;

            case 7:
                $ms = hexdec(substr($hex, 0, 12));
                $result['time'] = $this->unixMillisTime($ms);
                $result['notes'][] = 'Version 7 UUIDs start with a 48-bit Unix timestamp in milliseconds followed by random bits, so they sort by creation time.';
                break;

            case 8:
                $result['notes'][] = 'Version 8 UUIDs have a vendor-defined layout. Only the version and variant bits are standardised, so the remaining 122 bits cannot be interpreted generically.';
                break;

            default:
                $result['notes'][] = "Version {$version} is not assigned by RFC 9562 (versions 0 and 9-15 are reserved).";
        }

        return $result;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function variant(string $hex): array
    {
        $nibble = hexdec($hex[16]);

        return match (true) {
            $nibble < 0x8 => ['Reserved, NCS backward compatibility', '0'],
            $nibble < 0xC => ['RFC 9562 (formerly RFC 4122 / DCE 1.1)', '10'],
            $nibble < 0xE => ['Reserved, Microsoft Corporation backward compatibility', '110'],
            default => ['Reserved for future definition', '111'],
        };
    }

    private function versionName(string $hex, bool $isRfc, int $nibble): string
    {
        return match (true) {
            $hex === str_repeat('0', 32) => 'Nil UUID',
            $hex === str_repeat('f', 32) => 'Max UUID',
            ! $isRfc => 'Not applicable (non-RFC 9562 variant)',
            default => self::VERSION_NAMES[$nibble] ?? 'Reserved / unassigned',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function gregorianTime(int $ticks, string $precision): array
    {
        $unixTicks = $ticks - self::GREGORIAN_OFFSET;
        $seconds = intdiv($unixTicks, 10_000_000);
        $fraction = $unixTicks % 10_000_000;

        if ($fraction < 0) {
            $fraction += 10_000_000;
            $seconds--;
        }

        return [
            'iso8601' => $this->utc($seconds)->format('Y-m-d\TH:i:s').'.'.str_pad((string) $fraction, 7, '0', STR_PAD_LEFT).'Z',
            'unix_seconds' => $seconds,
            'gregorian_100ns_ticks' => $ticks,
            'precision' => $precision,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function unixMillisTime(int $ms): array
    {
        $seconds = intdiv($ms, 1000);

        return [
            'iso8601' => $this->utc($seconds)->format('Y-m-d\TH:i:s').'.'.str_pad((string) ($ms % 1000), 3, '0', STR_PAD_LEFT).'Z',
            'unix_seconds' => $seconds,
            'unix_ms' => $ms,
            'precision' => 'Milliseconds',
        ];
    }

    private function utc(int $seconds): DateTimeImmutable
    {
        return new DateTimeImmutable('@'.$seconds)->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * @return array<string, mixed>
     */
    private function node(string $hex): array
    {
        $multicast = (hexdec(substr($hex, 0, 2)) & 0x01) === 1;

        return [
            'mac' => implode(':', str_split($hex, 2)),
            'multicast_bit' => $multicast,
            'source' => $multicast
                ? 'Random node ID (multicast bit set; not a real MAC address)'
                : 'Possibly a real IEEE 802 MAC address of the generating machine',
        ];
    }

    /**
     * @return list<array{name: string, hex: string, description: string}>
     */
    private function fields(string $hex, int $version): array
    {
        $layout = match ($version) {
            1 => [['time_low', 0, 8, 'Low 32 bits of the timestamp'], ['time_mid', 8, 4, 'Middle 16 bits of the timestamp'], ['ver', 12, 1, 'Version'], ['time_high', 13, 3, 'High 12 bits of the timestamp'], ['var + clock_seq', 16, 4, 'Variant (top 2 bits) + 14-bit clock sequence'], ['node', 20, 12, '48-bit node ID']],
            2 => [['local_id', 0, 8, '32-bit local identifier (e.g. POSIX UID)'], ['time_mid', 8, 4, 'Middle 16 bits of the timestamp'], ['ver', 12, 1, 'Version'], ['time_high', 13, 3, 'High 12 bits of the timestamp'], ['var + clock_seq', 16, 2, 'Variant (top 2 bits) + 6-bit clock sequence'], ['local_domain', 18, 2, 'Local domain (0 person, 1 group, 2 org)'], ['node', 20, 12, '48-bit node ID']],
            3, 5 => [['hash', 0, 12, ($version === 3 ? 'MD5' : 'SHA-1').' hash bits'], ['ver', 12, 1, 'Version'], ['hash', 13, 3, 'Hash bits'], ['var + hash', 16, 16, 'Variant (top 2 bits) + hash bits']],
            6 => [['time_high', 0, 8, 'High 32 bits of the timestamp'], ['time_mid', 8, 4, 'Middle 16 bits of the timestamp'], ['ver', 12, 1, 'Version'], ['time_low', 13, 3, 'Low 12 bits of the timestamp'], ['var + clock_seq', 16, 4, 'Variant (top 2 bits) + 14-bit clock sequence'], ['node', 20, 12, '48-bit node ID']],
            7 => [['unix_ts_ms', 0, 12, '48-bit Unix timestamp in milliseconds'], ['ver', 12, 1, 'Version'], ['rand_a', 13, 3, '12 random bits (or sub-ms precision / counter)'], ['var + rand_b', 16, 16, 'Variant (top 2 bits) + 62 random bits']],
            8 => [['custom_a', 0, 12, '48 vendor-defined bits'], ['ver', 12, 1, 'Version'], ['custom_b', 13, 3, '12 vendor-defined bits'], ['var + custom_c', 16, 16, 'Variant (top 2 bits) + 62 vendor-defined bits']],
            4 => [['random_a', 0, 12, '48 random bits'], ['ver', 12, 1, 'Version'], ['random_b', 13, 3, '12 random bits'], ['var + random_c', 16, 16, 'Variant (top 2 bits) + 62 random bits']],
            default => [['data', 0, 12, 'Unassigned'], ['ver', 12, 1, 'Version (reserved value)'], ['data', 13, 3, 'Unassigned'], ['var + data', 16, 16, 'Variant + unassigned bits']],
        };

        return array_map(
            fn (array $f) => ['name' => $f[0], 'hex' => substr($hex, $f[1], $f[2]), 'description' => $f[3]],
            $layout,
        );
    }

    /**
     * @return array<string, string>
     */
    private function formats(string $hex): array
    {
        $bytes = hex2bin($hex);
        $standard = self::hyphenate($hex);
        $guidBytes = strrev(substr($bytes, 0, 4)).strrev(substr($bytes, 4, 2)).strrev(substr($bytes, 6, 2)).substr($bytes, 8);

        return [
            'standard' => $standard,
            'uppercase' => strtoupper($standard),
            'braces' => '{'.strtoupper($standard).'}',
            'urn' => 'urn:uuid:'.$standard,
            'hex' => $hex,
            'base64' => base64_encode($bytes),
            'integer' => (string) BigInteger::fromBase($hex, 16),
            'guid_bytes_le' => bin2hex($guidBytes),
            'guid_base64_le' => base64_encode($guidBytes),
        ];
    }

    /**
     * Shape returned by the original (pre-2026) /api/decode endpoint, kept so existing
     * API consumers keep working.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function legacyDecode(array $result): array
    {
        $version = $result['version'] !== null ? "{$result['version']} ({$result['version_name']})" : $result['version_name'];

        $contents = $result['time'] !== null
            ? array_filter([
                'time' => $result['time']['iso8601'],
                'clock' => $result['clock_sequence'] !== null ? (string) $result['clock_sequence'] : null,
                'node' => $result['node']['mac'] ?? null,
            ], fn ($v) => $v !== null)
            : implode(' ', $result['notes']);

        return ['variant' => $result['variant'], 'version' => $version, 'contents' => $contents];
    }
}
