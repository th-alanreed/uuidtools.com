<?php

namespace App\Uuid;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Ramsey\Uuid\Provider\Node\RandomNodeProvider;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer as IntegerObject;
use Ramsey\Uuid\Uuid;

final class UuidGenerator
{
    /**
     * Well-known namespaces from RFC 9562 section 6.6, keyed by their "ns:" identifier.
     */
    public const array NAMESPACES = [
        'ns:dns' => Uuid::NAMESPACE_DNS,
        'ns:url' => Uuid::NAMESPACE_URL,
        'ns:oid' => Uuid::NAMESPACE_OID,
        'ns:x500' => Uuid::NAMESPACE_X500,
    ];

    /**
     * @return list<string> lowercase, hyphenated UUID strings
     */
    public function generate(UuidVersion $version, int $count = 1): array
    {
        if ($version->isNameBased()) {
            throw new InvalidArgumentException("{$version->longLabel()} requires a namespace and a name.");
        }

        if ($count < 1) {
            throw new InvalidArgumentException('Count must be at least 1.');
        }

        $count = $version->isConstant() ? 1 : $count;

        $uuids = [];
        for ($i = 0; $i < $count; $i++) {
            $uuids[] = $this->one($version);
        }

        return $uuids;
    }

    /**
     * Generate a name-based (v3 or v5) UUID. The name is hashed as the exact bytes
     * given, so callers must pass UTF-8 text to match other RFC 9562 implementations.
     */
    public function nameBased(UuidVersion $version, string $namespace, string $name): string
    {
        $ns = self::resolveNamespace($namespace)
            ?? throw new InvalidArgumentException('Namespace must be a UUID or one of ns:DNS, ns:URL, ns:OID, ns:X500.');

        return match ($version) {
            UuidVersion::V3 => Uuid::uuid3($ns, $name)->toString(),
            UuidVersion::V5 => Uuid::uuid5($ns, $name)->toString(),
            default => throw new InvalidArgumentException("{$version->longLabel()} is not name-based."),
        };
    }

    /**
     * Resolve "ns:DNS"-style identifiers (or bare "dns") and UUIDs in any accepted
     * notation to a canonical namespace UUID string.
     */
    public static function resolveNamespace(string $namespace): ?string
    {
        $key = strtolower(trim($namespace));

        if (isset(self::NAMESPACES[$key])) {
            return self::NAMESPACES[$key];
        }

        if (isset(self::NAMESPACES['ns:'.$key])) {
            return self::NAMESPACES['ns:'.$key];
        }

        $hex = UuidInspector::normalize($namespace);

        return $hex === null ? null : UuidInspector::hyphenate($hex);
    }

    private function one(UuidVersion $version): string
    {
        return match ($version) {
            UuidVersion::V1 => Uuid::uuid1($this->randomNode())->toString(),
            UuidVersion::V2 => Uuid::uuid2(
                Uuid::DCE_DOMAIN_PERSON,
                new IntegerObject((string) random_int(0, 0xFFFFFFFF)),
                $this->randomNode(),
            )->toString(),
            UuidVersion::V4 => Uuid::uuid4()->toString(),
            UuidVersion::V6 => Uuid::uuid6($this->randomNode())->toString(),
            UuidVersion::V7 => Uuid::uuid7()->toString(),
            UuidVersion::V8 => Uuid::uuid8(random_bytes(16))->toString(),
            UuidVersion::Nil => Uuid::NIL,
            UuidVersion::Max => Uuid::MAX,
            UuidVersion::TimestampFirst => strtolower((string) Str::orderedUuid()),
            UuidVersion::V3, UuidVersion::V5 => throw new InvalidArgumentException('Name-based UUIDs need a namespace and name.'),
        };
    }

    /**
     * A random 48-bit node with the multicast bit set (RFC 9562 section 6.10), so the
     * server's real MAC address is never published inside v1/v2/v6 UUIDs.
     */
    private function randomNode(): Hexadecimal
    {
        return (new RandomNodeProvider)->getNode();
    }
}
