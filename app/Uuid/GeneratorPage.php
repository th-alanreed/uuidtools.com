<?php

namespace App\Uuid;

final readonly class GeneratorPage
{
    /**
     * @param  array<string, mixed>  $preset  default UuidFormatter options for this page
     */
    public function __construct(
        public string $slug,
        public UuidVersion $version,
        public string $title,
        public string $heading,
        public string $description,
        public int $count = 1,
        public array $preset = [],
    ) {}

    public static function find(string $slug): ?self
    {
        return self::all()[$slug] ?? null;
    }

    public function path(): string
    {
        return '/generate/'.$this->slug;
    }

    public function view(): string
    {
        return 'versions.'.$this->slug;
    }

    /**
     * @return array<string, self>
     */
    public static function all(): array
    {
        $pages = [
            new self('v4', UuidVersion::V4, 'UUID v4 Generator - Random UUIDs', 'Version 4 UUID Generator',
                'Generate random version 4 UUIDs instantly. Copy one, bulk generate up to 1,000, format as GUID, or use the free API. RFC 9562 compliant.'),
            new self('v7', UuidVersion::V7, 'UUID v7 Generator - Time-Ordered UUIDs', 'Version 7 UUID Generator',
                'Generate time-ordered version 7 UUIDs (RFC 9562). Sortable, database-friendly IDs with a Unix millisecond timestamp. Bulk generation and free API.'),
            new self('v1', UuidVersion::V1, 'UUID v1 Generator - Time-Based UUIDs', 'Version 1 UUID Generator',
                'Generate version 1 (time-based) UUIDs online. Learn how v1 encodes a Gregorian timestamp, clock sequence and node, and when to use v6 or v7 instead.'),
            new self('v3', UuidVersion::V3, 'UUID v3 Generator - MD5 Name-Based UUIDs', 'Version 3 UUID Generator',
                'Generate deterministic version 3 UUIDs from a namespace and name using MD5. Names are hashed as UTF-8. Supports DNS, URL, OID, X.500 and custom namespaces.'),
            new self('v5', UuidVersion::V5, 'UUID v5 Generator - SHA-1 Name-Based UUIDs', 'Version 5 UUID Generator',
                'Generate deterministic version 5 UUIDs from a namespace and name using SHA-1. Names are hashed as UTF-8. Supports DNS, URL, OID, X.500 and custom namespaces.'),
            new self('v6', UuidVersion::V6, 'UUID v6 Generator - Reordered Time-Based UUIDs', 'Version 6 UUID Generator',
                'Generate version 6 UUIDs (RFC 9562): the v1 timestamp reordered so IDs sort by creation time. Bulk generation, decoder and free API.'),
            new self('v8', UuidVersion::V8, 'UUID v8 Generator - Custom UUIDs', 'Version 8 UUID Generator',
                'Generate version 8 UUIDs (RFC 9562 custom format) and learn how to design your own vendor-specific layout with correct version and variant bits.'),
            new self('nil', UuidVersion::Nil, 'Nil UUID - 00000000-0000-0000-0000-000000000000', 'Nil UUID',
                'The Nil UUID 00000000-0000-0000-0000-000000000000 has all 128 bits set to zero. Copy it and learn when to use the nil (empty) UUID or Guid.Empty.'),
            new self('max', UuidVersion::Max, 'Max UUID - ffffffff-ffff-ffff-ffff-ffffffffffff', 'Max UUID',
                'The Max UUID ffffffff-ffff-ffff-ffff-ffffffffffff has all 128 bits set to one (RFC 9562). Copy it and learn when to use it as a sentinel value.'),
            new self('v2', UuidVersion::V2, 'UUID v2 Generator - DCE Security UUIDs', 'Version 2 UUID Generator (DCE Security)',
                'Generate legacy version 2 (DCE Security) UUIDs and learn why they are rarely used. Includes local domain and identifier decoding.'),
            new self('guid', UuidVersion::V4, 'GUID Generator - Online GUID for C#, .NET & SQL Server', 'Online GUID Generator',
                'Generate GUIDs for C#, .NET and SQL Server. Uppercase, braces, no hyphens, bulk up to 1,000. A GUID is a UUID: same 128-bit format.',
                preset: ['upper' => true, 'braces' => true]),
            new self('bulk', UuidVersion::V4, 'Bulk UUID Generator - Up to 1,000 UUIDs at Once', 'Bulk UUID Generator',
                'Generate up to 1,000 UUIDs at once (v1, v4, v6, v7, v8). Copy all, download as .txt, or format as GUIDs, CSV or quoted lists.',
                count: 100),
            new self('timestamp-first', UuidVersion::TimestampFirst, 'Timestamp-First (Ordered) UUID Generator', 'Timestamp-First UUID Generator',
                'Generate timestamp-first ("ordered", COMB) UUIDs like Laravel\'s Str::orderedUuid(). Learn why UUID v7 is now the standard alternative.'),
            new self('minecraft', UuidVersion::V4, 'Minecraft UUID Generator', 'Minecraft UUID Generator',
                'Generate random Minecraft UUIDs (version 4) for entities, items and players, and learn how Minecraft derives offline-mode player UUIDs.'),
        ];

        return array_column(array_map(fn (self $p) => [$p->slug, $p], $pages), 1, 0);
    }
}
