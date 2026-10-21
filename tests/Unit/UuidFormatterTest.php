<?php

namespace Tests\Unit;

use App\Uuid\UuidFormatter;
use PHPUnit\Framework\TestCase;

class UuidFormatterTest extends TestCase
{
    private const UUID = '123e4567-e89b-12d3-a456-426614174000';

    public function test_defaults_leave_the_uuid_untouched(): void
    {
        $this->assertSame(self::UUID, (new UuidFormatter)->format(self::UUID));
    }

    public function test_guid_style(): void
    {
        $formatter = new UuidFormatter(uppercase: true, braces: true);

        $this->assertSame('{123E4567-E89B-12D3-A456-426614174000}', $formatter->format(self::UUID));
    }

    public function test_no_hyphens_urn_and_quotes_combine(): void
    {
        $formatter = new UuidFormatter(hyphens: false, urn: true, quotes: 'single');

        $this->assertSame("'urn:uuid:123e4567e89b12d3a456426614174000'", $formatter->format(self::UUID));
    }

    public function test_join_uses_the_separator(): void
    {
        $formatter = new UuidFormatter(quotes: 'double', separator: 'comma-space');

        $this->assertSame('"a", "b"', $formatter->join(['a', 'b']));
    }

    public function test_from_array_parses_flags_and_ignores_garbage(): void
    {
        $formatter = UuidFormatter::fromArray(['upper' => '1', 'hyphens' => '0', 'braces' => 'true', 'quotes' => 'bogus', 'separator' => ['x']]);

        $this->assertTrue($formatter->uppercase);
        $this->assertFalse($formatter->hyphens);
        $this->assertTrue($formatter->braces);
        $this->assertSame('none', $formatter->quotes);
        $this->assertSame('newline', $formatter->separator);
    }

    public function test_to_query_only_contains_non_default_options(): void
    {
        $this->assertSame([], (new UuidFormatter)->toQuery());
        $this->assertSame(['upper' => '1', 'hyphens' => '0'], (new UuidFormatter(uppercase: true, hyphens: false))->toQuery());
    }
}
