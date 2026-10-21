<?php

namespace App\Uuid;

/**
 * Editorial content (intro, structure, guidance, FAQ) for each generator page.
 * Kept as data so one Blade template renders every page and the FAQ can feed JSON-LD.
 */
final class VersionContent
{
    /**
     * @return array{intro: list<string>, layout: ?string, when: list<string>, avoid: list<string>, faq: list<array{0: string, 1: string}>}
     */
    public static function for(string $slug): array
    {
        $content = self::all()[$slug] ?? self::all()['v4'];

        return $content + ['layout' => null, 'when' => [], 'avoid' => [], 'faq' => []];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function all(): array
    {
        return [
            'v4' => [
                'intro' => [
                    'A version 4 UUID is made almost entirely of random data: 122 of its 128 bits are random, and the other 6 bits record the version (4) and variant. It is the most widely used UUID and the right default when you just need a unique, unguessable identifier.',
                    'Our generator uses the operating system\'s cryptographically secure random number generator on the server. You can generate up to 1,000 at once, format them as GUIDs, and copy or download the result.',
                ],
                'layout' => 'xxxxxxxx-xxxx-4xxx-Nxxx-xxxxxxxxxxxx   (x = random, N = 8, 9, a or b)',
                'when' => ['General-purpose primary keys, request IDs, file names and API resource IDs.', 'Anywhere identifiers must not reveal when or where they were created.'],
                'avoid' => ['Large indexed database tables where insert locality matters: prefer v7, which is time-ordered.'],
                'faq' => [
                    ['How likely is a UUID v4 collision?', 'With 122 random bits there are about 5.3 x 10^36 possible values. You would need to generate roughly 2.7 quintillion UUIDs to have a 50% chance of a single collision, so accidental duplicates are not a practical concern.'],
                    ['Is a v4 UUID safe to use as a secret?', 'It is unguessable when produced by a secure random source, but UUIDs are identifiers, not credentials. Use a purpose-built token for password resets and API keys.'],
                ],
            ],
            'v7' => [
                'intro' => [
                    'A version 7 UUID begins with a 48-bit Unix timestamp in milliseconds followed by random bits. Because the timestamp comes first, v7 UUIDs sort in the order they were created, which keeps database indexes compact and fast.',
                    'RFC 9562 (May 2024) recommends v7 over v1 and v6 for new systems that need time-ordered identifiers. Within the same millisecond our generator keeps IDs strictly increasing.',
                ],
                'layout' => 'unix_ts_ms (48 bits) | 7 | rand_a (12) | variant (2) + rand_b (62)',
                'when' => ['Database primary keys (PostgreSQL, MySQL, SQL Server), especially on large tables.', 'Event, log, message and job IDs where chronological order is useful.'],
                'avoid' => ['Identifiers that must not leak their creation time: v7 reveals when it was made to the millisecond. Use v4.'],
                'faq' => [
                    ['Is UUID v7 sortable?', 'Yes. The first 48 bits are a big-endian Unix millisecond timestamp, so comparing v7 UUIDs as strings or bytes sorts them by creation time.'],
                    ['Should I use UUID v7 or v4 for primary keys?', 'Use v7 when the database indexes the key: new rows are appended near the end of the index instead of landing in random places, which reduces page splits and cache misses. Use v4 when you do not want to expose creation time.'],
                ],
            ],
            'v1' => [
                'intro' => [
                    'A version 1 UUID combines a 60-bit timestamp counted in 100-nanosecond steps since 15 October 1582, a 14-bit clock sequence, and a 48-bit node ID that was traditionally the machine\'s MAC address.',
                    'To protect privacy, UUIDTools never embeds a real MAC address: the node is random with the multicast bit set, exactly as RFC 9562 section 6.10 recommends. Paste any v1 UUID into the decoder to see its timestamp.',
                ],
                'layout' => 'time_low (32) | time_mid (16) | 1 + time_high (16) | variant + clock_seq (16) | node (48)',
                'when' => ['Interoperating with existing systems that already use v1.'],
                'avoid' => ['New systems: the timestamp is scrambled across the string so v1 does not sort by time. Use v7 (or v6 to migrate v1 data).', 'Anywhere a real MAC address could leak.'],
                'faq' => [
                    ['Does a v1 UUID contain my MAC address?', 'It can: classic implementations put the machine\'s MAC address in the last 12 hex digits. Ours use a random node, which is flagged by the multicast bit.'],
                ],
            ],
            'v3' => [
                'intro' => [
                    'A version 3 UUID is a deterministic UUID: it is the MD5 hash of a namespace UUID followed by a name, with the version and variant bits overwritten. The same namespace and name always give the same UUID.',
                    'Names are hashed as UTF-8 bytes, which is what Python, Java, C# (with Encoding.UTF8), Go and the RFC examples do. The "Copy API call" link uses base64 so any character, including emoji, round-trips exactly.',
                ],
                'layout' => 'md5(namespace + name) with version = 3 and variant = 10',
                'when' => ['You need stable IDs derived from existing data (URLs, domain names, emails) and must stay compatible with older systems that chose MD5.'],
                'avoid' => ['New work: prefer v5 (SHA-1) or, for 2024+, consider v8 with SHA-256. Never rely on v3 for secrecy; it is easy to brute-force.'],
                'faq' => [
                    ['What namespaces are predefined?', 'RFC 9562 defines DNS (6ba7b810-9dad-11d1-80b4-00c04fd430c8), URL (…b811…), OID (…b812…) and X.500 (…b814…). You can also use any UUID of your own as a namespace.'],
                    ['Why does my UUID differ from another tool\'s?', 'Almost always text encoding. Some tools hash UTF-16, Latin-1 or ASCII bytes instead of UTF-8. UUIDTools always hashes UTF-8.'],
                ],
            ],
            'v5' => [
                'intro' => [
                    'A version 5 UUID is the SHA-1 hash of a namespace UUID and a name, truncated to 128 bits with the version and variant bits set. Like v3 it is deterministic: the same inputs always produce the same UUID.',
                    'Names are hashed as UTF-8 bytes, matching Python\'s uuid.uuid5, Java\'s libraries and most RFC 9562 implementations.',
                ],
                'layout' => 'sha1(namespace + name)[0..16] with version = 5 and variant = 10',
                'when' => ['Deriving stable IDs from URLs, domains or natural keys (idempotent imports, content addressing, deduplication).'],
                'avoid' => ['Anywhere unpredictability is required: anyone who knows the name can compute the UUID.'],
                'faq' => [
                    ['Is v5 better than v3?', 'Yes, SHA-1 is stronger than MD5 and RFC 9562 prefers it. Both are fine for identifiers, neither is suitable for security purposes.'],
                    ['How do I generate the same UUID in code?', 'Python: uuid.uuid5(uuid.NAMESPACE_DNS, "example.com"). PHP: Uuid::uuid5(Uuid::NAMESPACE_DNS, "example.com"). Java: Generate with a library such as java-uuid-generator.'],
                ],
            ],
            'v6' => [
                'intro' => [
                    'A version 6 UUID is a version 1 UUID with its timestamp fields reordered so the most significant bits come first. The result sorts by creation time while staying structurally compatible with v1.',
                    'It exists mainly so systems that already produce v1 can migrate without changing the timestamp source. For greenfield projects RFC 9562 recommends v7.',
                ],
                'layout' => 'time_high (32) | time_mid (16) | 6 + time_low (16) | variant + clock_seq (16) | node (48)',
                'when' => ['Migrating from v1 while gaining time-ordering.'],
                'avoid' => ['New systems without a v1 legacy: v7 is simpler and uses a Unix timestamp.'],
                'faq' => [
                    ['What is the difference between v1 and v6?', 'Same data, different byte order. v6 puts the high timestamp bits first so lexicographic order equals chronological order.'],
                ],
            ],
            'v8' => [
                'intro' => [
                    'Version 8 is the experimental/vendor-specific UUID. RFC 9562 fixes only the 4 version bits and 2 variant bits; the remaining 122 bits are yours to define, for example to embed a shard ID, a SHA-256 name hash, or a custom timestamp.',
                    'Our generator fills the free bits with random data to show the format. Build your own layout in code and use the decoder to check that the version and variant are correct.',
                ],
                'layout' => 'custom_a (48) | 8 | custom_b (12) | variant (2) + custom_c (62)',
                'when' => ['You need a UUID-shaped ID with application-specific structure and no collision with other versions.'],
                'avoid' => ['Interchange with other systems: nobody else can interpret your layout.'],
                'faq' => [
                    ['Can I use SHA-256 for name-based UUIDs?', 'RFC 9562 suggests v8 for this: hash with SHA-256, keep the first 128 bits and then overwrite the version and variant bits.'],
                ],
            ],
            'nil' => [
                'intro' => [
                    'The Nil UUID is the special value 00000000-0000-0000-0000-000000000000 with every bit set to zero (RFC 9562 section 5.9). It means "no UUID" and is the UUID equivalent of null.',
                    'In .NET it is Guid.Empty; in Python uuid.UUID(int=0); in PHP Ramsey\\Uuid\\Uuid::NIL.',
                ],
                'when' => ['A placeholder or default value where a UUID column must not be NULL.'],
                'faq' => [
                    ['Is the nil UUID valid?', 'Yes. It is explicitly defined by RFC 9562, though it has no version or variant.'],
                ],
            ],
            'max' => [
                'intro' => [
                    'The Max UUID is ffffffff-ffff-ffff-ffff-ffffffffffff with every bit set to one (RFC 9562 section 5.10). It was added in 2024 as the counterpart to the Nil UUID.',
                    'It sorts after every other UUID, which makes it handy as an upper bound in range queries or an end-of-range sentinel.',
                ],
                'when' => ['Range scans over UUID keys (WHERE id <= max) and sentinel values.'],
                'faq' => [
                    ['Why does the max UUID show a version of 15?', 'The 13th digit is "f", but because the variant bits are 111 it is not an RFC 9562 variant UUID, so the version digit has no meaning. It is a special case.'],
                ],
            ],
            'v2' => [
                'intro' => [
                    'Version 2 UUIDs ("DCE Security") replace part of a version 1 UUID with a local identifier such as a POSIX user or group ID, and a domain identifying what kind of ID it is. RFC 9562 reserves the version but does not define it; the layout comes from the DCE 1.1 specification.',
                    'They are rare and lose timestamp precision, so almost nobody needs them. We provide the generator for completeness.',
                ],
                'when' => ['Interoperating with DCE-era systems.'],
                'avoid' => ['Everything else. Use v7 (time-ordered) or v4 (random).'],
                'faq' => [],
            ],
            'guid' => [
                'intro' => [
                    'GUID (Globally Unique Identifier) is Microsoft\'s name for a UUID. They are the same 128-bit value. Windows, COM, .NET and SQL Server typically show GUIDs in uppercase and often wrap them in braces, for example {6F9619FF-8B86-D011-B42D-00C04FC964FF}.',
                    'The generator below is preset for that style. Switch the options to get lowercase, no hyphens, quotes, or comma-separated output for pasting straight into code or SQL.',
                ],
                'when' => ['C#: Guid.NewGuid(). SQL Server: NEWID() (random) or NEWSEQUENTIALID() (ordered). PowerShell: [guid]::NewGuid().'],
                'faq' => [
                    ['What is the difference between a GUID and a UUID?', 'Nothing in practice. GUID is the Microsoft term, UUID the standards term. One caveat: .NET\'s Guid.ToByteArray() stores the first three fields little-endian, so the raw bytes differ from the RFC byte order even though the text form is identical. The decoder shows both.'],
                    ['Does a GUID have braces?', 'The braces are only a text convention (registry format). They are not part of the value.'],
                ],
            ],
            'bulk' => [
                'intro' => [
                    'Need many UUIDs at once for test data, fixtures, spreadsheets or a database seed? Set the count (up to 1,000), pick a version, and copy everything or download a .txt file.',
                    'Use the format options for one per line, comma-separated, quoted, uppercase or GUID style. The same results are available from the API as plain text or JSON.',
                ],
                'faq' => [
                    ['How many UUIDs can I generate at once?', 'Up to 1,000 per request in the browser and the API. Make multiple requests for more, or generate them locally with your language\'s UUID library.'],
                ],
            ],
            'timestamp-first' => [
                'intro' => [
                    'A timestamp-first UUID (also called an ordered UUID or COMB) places a timestamp at the front of an otherwise random UUID, so values sort in creation order. This is the format produced by Laravel\'s Str::orderedUuid().',
                    'The same goal is now met by the standard version 7 UUID, which is the recommended choice for new projects.',
                ],
                'when' => ['Matching an existing Laravel/Eloquent ordered UUID column.'],
                'avoid' => ['New projects: use UUID v7, which is standardised and widely supported.'],
                'faq' => [],
            ],
            'minecraft' => [
                'intro' => [
                    'Minecraft identifies players, entities and other objects with UUIDs. Random IDs are version 4. Servers running in offline mode derive a player\'s UUID by MD5-hashing the text "OfflinePlayer:<username>" (Java\'s UUID.nameUUIDFromBytes), giving a version 3 UUID that stays the same on every offline server.',
                    'Use the generator for random Minecraft-compatible UUIDs. Offline-mode UUIDs hash the name without any namespace UUID, so the namespace-based v3 generator cannot reproduce them.',
                ],
                'faq' => [
                    ['Which UUID version does Minecraft use?', 'Online players and entities use random version 4 UUIDs. Offline-mode player UUIDs are version 3 (MD5 of "OfflinePlayer:" plus the name, with no namespace).'],
                ],
            ],
        ];
    }
}
