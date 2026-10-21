@extends('layouts.app')

@section('title', 'UUID Validator & Decoder: Version, Variant, Timestamp')
@section('description', 'Validate and decode any UUID or GUID. See its version (v1 to v8), variant, embedded timestamp, node, clock sequence and every common format. Works for nil, max and Microsoft GUIDs.')
@section('canonical', url('/decode'))

@section('content')
<section class="hero">
    <h1>UUID Validator &amp; Decoder</h1>
    <p class="lead">Paste a UUID or GUID to check it and see what is inside: version, variant, timestamp and more.</p>
</section>

<section class="card">
    <form method="get" action="/decode" class="decode-form">
        <label for="uuid">UUID / GUID</label>
        <div class="row">
            <input type="text" id="uuid" name="uuid" value="{{ $input }}" placeholder="{{ $sampleUuid }}" spellcheck="false" autocomplete="off" autofocus>
            <button class="btn primary" type="submit">Decode</button>
        </div>
        <p class="hint">Accepts hyphenated, 32 hex digits, <code>{braces}</code>, <code>urn:uuid:</code>, any case. <a href="/decode?uuid={{ $sampleUuid }}">Try a fresh v7</a>.</p>
    </form>
</section>

@if ($input !== '' && $result === null)
    <section class="card result invalid" role="alert">
        <h2><span class="badge bad">Invalid</span> Not a valid UUID</h2>
        <p>A UUID has 32 hexadecimal digits (0-9, a-f), usually written in groups of 8-4-4-4-12 separated by hyphens: <code>123e4567-e89b-12d3-a456-426614174000</code>.</p>
        <p class="muted">You entered {{ strlen($input) }} character{{ strlen($input) === 1 ? '' : 's' }}: <code>{{ \Illuminate\Support\Str::limit($input, 80) }}</code></p>
    </section>
@elseif ($result)
    <section class="card result">
        <h2><span class="badge good">Valid</span> <code>{{ $result['uuid'] }}</code>
            <button class="btn small" data-copy="{{ $result['uuid'] }}" type="button">Copy</button></h2>
        <div class="badges">
            @if ($result['is_nil'])
                <span class="badge">Nil UUID</span>
            @elseif ($result['is_max'])
                <span class="badge">Max UUID</span>
            @elseif ($result['version'] !== null)
                <span class="badge">Version {{ $result['version'] }}</span>
            @endif
            <span class="badge">{{ $result['version_name'] }}</span>
            @if ($result['sortable_by_time'])<span class="badge">Sortable by time</span>@endif
        </div>

        @if ($result['fields'])
            <div class="segments" aria-label="UUID structure">
                @foreach ($result['fields'] as $field)
                    <div class="segment" style="flex: {{ max(strlen($field['hex']), 3) }}" title="{{ $field['description'] }}">
                        <code>{{ $field['hex'] }}</code>
                        <small>{{ $field['name'] }}</small>
                    </div>
                @endforeach
            </div>
        @endif

        <table class="kv">
            <tbody>
                <tr><th>Version</th><td>{{ $result['version'] ?? 'n/a' }} <span class="muted">{{ $result['version_name'] }}</span></td></tr>
                <tr><th>Variant</th><td>{{ $result['variant'] }} <span class="muted">(bits <code>{{ $result['variant_bits'] }}</code>)</span></td></tr>
                @if ($result['time'])
                    <tr><th>Timestamp (UTC)</th><td><code>{{ $result['time']['iso8601'] }}</code> <button class="btn small" data-copy="{{ $result['time']['iso8601'] }}" type="button">Copy</button><br><span class="muted">Unix: {{ $result['time']['unix_ms'] ?? $result['time']['unix_seconds'] }}{{ isset($result['time']['unix_ms']) ? ' ms' : ' s' }}. {{ $result['time']['precision'] }}.</span></td></tr>
                @endif
                @if ($result['clock_sequence'] !== null)
                    <tr><th>Clock sequence</th><td>{{ $result['clock_sequence'] }}</td></tr>
                @endif
                @if ($result['node'])
                    <tr><th>Node</th><td><code>{{ $result['node']['mac'] }}</code> <span class="muted">{{ $result['node']['source'] }}</span></td></tr>
                @endif
                @isset($result['local_domain'])
                    <tr><th>Local domain</th><td>{{ $result['local_domain']['name'] }} ({{ $result['local_domain']['value'] }})</td></tr>
                    <tr><th>Local identifier</th><td>{{ $result['local_identifier'] }}</td></tr>
                @endisset
            </tbody>
        </table>

        @foreach ($result['notes'] as $note)
            <p class="note">{{ $note }}</p>
        @endforeach

        <h3>Formats</h3>
        <table class="kv">
            <tbody>
                @foreach ([
                    'standard' => 'Standard (lowercase)',
                    'uppercase' => 'Uppercase',
                    'braces' => 'Microsoft GUID (braces)',
                    'urn' => 'URN',
                    'hex' => '32 hex digits',
                    'base64' => 'Base64 (RFC byte order)',
                    'integer' => '128-bit integer (decimal)',
                    'guid_bytes_le' => '.NET Guid.ToByteArray() (hex)',
                    'guid_base64_le' => '.NET Guid bytes (Base64)',
                ] as $key => $label)
                    <tr>
                        <th>{{ $label }}</th>
                        <td><code>{{ $result['formats'][$key] }}</code></td>
                        <td class="copy-cell"><button class="btn small" data-copy="{{ $result['formats'][$key] }}" type="button">Copy</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="hint">API: <a href="/api/decode/{{ $result['uuid'] }}"><code>/api/decode/{{ $result['uuid'] }}</code></a></p>
    </section>
@endif

<div class="content-grid">
    <article class="prose">
        <h2>How to decode a UUID</h2>
        <p>Every RFC 9562 UUID stores its <strong>version</strong> in the 13th hex digit and its <strong>variant</strong> in the top bits of the 17th digit:</p>
        <pre class="layout">xxxxxxxx-xxxx-<b>M</b>xxx-<b>N</b>xxx-xxxxxxxxxxxx
             M = version (1-8)   N = variant (8, 9, a, b = RFC 9562)</pre>
        <table class="data">
            <thead><tr><th>Version digit</th><th>Meaning</th><th>Contains a timestamp?</th></tr></thead>
            <tbody>
                <tr><td>1</td><td>Gregorian time-based</td><td>Yes (100 ns since 1582)</td></tr>
                <tr><td>2</td><td>DCE Security</td><td>Partial</td></tr>
                <tr><td>3</td><td>Name-based, MD5</td><td>No</td></tr>
                <tr><td>4</td><td>Random</td><td>No</td></tr>
                <tr><td>5</td><td>Name-based, SHA-1</td><td>No</td></tr>
                <tr><td>6</td><td>Reordered Gregorian time</td><td>Yes (100 ns since 1582)</td></tr>
                <tr><td>7</td><td>Unix Epoch time</td><td>Yes (ms since 1970)</td></tr>
                <tr><td>8</td><td>Custom</td><td>Only if your format has one</td></tr>
            </tbody>
        </table>
        <h3>Variant digit</h3>
        <table class="data">
            <thead><tr><th>Binary</th><th>Hex digit</th><th>Variant</th></tr></thead>
            <tbody>
                <tr><td><code>0xxx</code></td><td>0 - 7</td><td>Reserved, NCS backward compatibility</td></tr>
                <tr><td><code>10xx</code></td><td>8 - b</td><td>RFC 9562 (the standard variant)</td></tr>
                <tr><td><code>110x</code></td><td>c - d</td><td>Reserved, Microsoft backward compatibility</td></tr>
                <tr><td><code>111x</code></td><td>e - f</td><td>Reserved for future definition</td></tr>
            </tbody>
        </table>
        <p>The decoder never throws on odd input: unknown versions, legacy variants and the special nil (<code>00000000-…</code>) and max (<code>ffffffff-…</code>) UUIDs all produce a readable result.</p>
        <p>Learn more in <a href="/uuid-versions-explained">UUID versions explained</a>.</p>
    </article>
    <aside class="side">
        <div class="card"><h3>Generate</h3>
            <ul class="plain">
                @foreach (\App\Uuid\UuidVersion::tabs() as $v)<li><a href="{{ $v->path() }}">{{ $v->longLabel() }} generator</a></li>@endforeach
            </ul>
        </div>
    </aside>
</div>
@endsection
