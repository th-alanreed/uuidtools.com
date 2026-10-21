<?php

namespace App\Uuid;

enum UuidVersion: string
{
    case V1 = 'v1';
    case V2 = 'v2';
    case V3 = 'v3';
    case V4 = 'v4';
    case V5 = 'v5';
    case V6 = 'v6';
    case V7 = 'v7';
    case V8 = 'v8';
    case Nil = 'nil';
    case Max = 'max';
    case TimestampFirst = 'timestamp-first';

    /**
     * Versions shown as tabs in the generator, in display order.
     *
     * @return list<self>
     */
    public static function tabs(): array
    {
        return [self::V4, self::V7, self::V1, self::V3, self::V5, self::V6, self::V8, self::Nil, self::Max];
    }

    public function label(): string
    {
        return match ($this) {
            self::Nil => 'Nil',
            self::Max => 'Max',
            self::TimestampFirst => 'Timestamp-first',
            default => 'v'.$this->number(),
        };
    }

    public function longLabel(): string
    {
        return match ($this) {
            self::Nil => 'Nil UUID',
            self::Max => 'Max UUID',
            self::TimestampFirst => 'Timestamp-first UUID',
            default => 'Version '.$this->number().' UUID',
        };
    }

    public function number(): ?int
    {
        return match ($this) {
            self::V1 => 1,
            self::V2 => 2,
            self::V3 => 3,
            self::V4, self::TimestampFirst => 4,
            self::V5 => 5,
            self::V6 => 6,
            self::V7 => 7,
            self::V8 => 8,
            self::Nil, self::Max => null,
        };
    }

    public function summary(): string
    {
        return match ($this) {
            self::V1 => 'Gregorian timestamp + clock sequence + node. Legacy time-based.',
            self::V2 => 'DCE Security. Legacy, rarely used; embeds a local user/group id.',
            self::V3 => 'Deterministic: MD5 hash of a namespace + name.',
            self::V4 => 'Random. 122 random bits. The default choice for most apps.',
            self::V5 => 'Deterministic: SHA-1 hash of a namespace + name. Prefer over v3.',
            self::V6 => 'Like v1, but reordered so it sorts by time. For v1 migrations.',
            self::V7 => 'Unix millisecond timestamp + random. Time-sortable; ideal database key.',
            self::V8 => 'Custom/vendor-specific layout; only version and variant bits are fixed.',
            self::Nil => 'All 128 bits set to zero. A "no value" placeholder.',
            self::Max => 'All 128 bits set to one. A sentinel/upper bound.',
            self::TimestampFirst => 'Laravel\'s ordered UUID ("COMB"): a v4 with a timestamp prefix.',
        };
    }

    public function isNameBased(): bool
    {
        return $this === self::V3 || $this === self::V5;
    }

    public function isConstant(): bool
    {
        return $this === self::Nil || $this === self::Max;
    }

    public function supportsCount(): bool
    {
        return ! $this->isNameBased() && ! $this->isConstant();
    }

    public function path(): string
    {
        return '/generate/'.$this->value;
    }
}
