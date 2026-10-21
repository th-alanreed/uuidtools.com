<?php

namespace App\Uuid;

/**
 * Presentation options shared by the web UI and the API. public/js/uuidtools.js
 * implements the same rules client-side so toggling options never needs a request.
 */
final readonly class UuidFormatter
{
    public const array SEPARATORS = [
        'newline' => "\n",
        'comma' => ',',
        'comma-space' => ', ',
        'space' => ' ',
    ];

    public const array QUOTES = ['none' => '', 'double' => '"', 'single' => "'"];

    public function __construct(
        public bool $uppercase = false,
        public bool $hyphens = true,
        public bool $braces = false,
        public bool $urn = false,
        public string $quotes = 'none',
        public string $separator = 'newline',
    ) {}

    /**
     * @param  array<string, mixed>  $input  typically request query parameters
     */
    public static function fromArray(array $input): self
    {
        $flag = fn (string $key, bool $default) => array_key_exists($key, $input)
            ? filter_var($input[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default
            : $default;

        $quotes = is_string($input['quotes'] ?? null) && isset(self::QUOTES[$input['quotes']]) ? $input['quotes'] : 'none';
        $separator = is_string($input['separator'] ?? null) && isset(self::SEPARATORS[$input['separator']]) ? $input['separator'] : 'newline';

        return new self(
            uppercase: $flag('upper', false),
            hyphens: $flag('hyphens', true),
            braces: $flag('braces', false),
            urn: $flag('urn', false),
            quotes: $quotes,
            separator: $separator,
        );
    }

    public function format(string $uuid): string
    {
        $value = $this->hyphens ? $uuid : str_replace('-', '', $uuid);
        $value = $this->uppercase ? strtoupper($value) : strtolower($value);

        if ($this->urn) {
            $value = 'urn:uuid:'.$value;
        }

        if ($this->braces) {
            $value = '{'.$value.'}';
        }

        $quote = self::QUOTES[$this->quotes];

        return $quote.$value.$quote;
    }

    /**
     * @param  list<string>  $uuids
     * @return list<string>
     */
    public function formatAll(array $uuids): array
    {
        return array_map($this->format(...), $uuids);
    }

    /**
     * @param  list<string>  $uuids
     */
    public function join(array $uuids): string
    {
        return implode(self::SEPARATORS[$this->separator], $this->formatAll($uuids));
    }

    /**
     * Query parameters that differ from the defaults, for building shareable API URLs.
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'upper' => $this->uppercase ? '1' : null,
            'hyphens' => $this->hyphens ? null : '0',
            'braces' => $this->braces ? '1' : null,
            'urn' => $this->urn ? '1' : null,
            'quotes' => $this->quotes !== 'none' ? $this->quotes : null,
            'separator' => $this->separator !== 'newline' ? $this->separator : null,
        ], fn (?string $value) => $value !== null);
    }
}
