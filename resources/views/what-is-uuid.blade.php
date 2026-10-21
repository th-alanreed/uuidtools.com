@extends('layouts.app')
@section('title', 'What is a UUID? UUID & GUID Explained')
@section('description', 'What is a UUID (GUID)? Learn the format, how unique UUIDs really are, UUID vs GUID, collision odds, and how to use UUIDs as database keys. Updated for RFC 9562.')
@section('canonical', url('/what-is-uuid'))

@php
$faq = [
    ['What does UUID stand for?', 'Universally Unique Identifier. Microsoft calls the same thing a GUID, a Globally Unique Identifier.'],
    ['Are UUIDs really unique?', 'Not guaranteed, but collisions are astronomically unlikely. A random v4 UUID has 122 random bits, about 5.3 x 10^36 possible values. You would need to generate about 1 billion UUIDs per second for roughly 85 years to reach a 50% chance of a single duplicate.'],
    ['What is the difference between a UUID and a GUID?', 'None in practice: they are the same 128-bit identifier. GUID is the Microsoft term and is often written in uppercase with braces.'],
    ['Which UUID version should I use?', 'Use v4 for random identifiers and v7 for database primary keys. Use v5 when you need the same input to always give the same UUID.'],
];
@endphp

@push('head')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faq)], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<section class="hero">
    <h1>What is a UUID?</h1>
    <p class="lead">Universally Unique Identifiers, also called GUIDs, and how they work.</p>
</section>
<div class="content-grid">
<article class="prose">
    <h2>Overview</h2>
    <p>A UUID is a 128-bit label used to identify things in computer systems without a central authority handing out numbers. It is written as 32 hexadecimal digits in five groups separated by hyphens (8-4-4-4-12), 36 characters in all:</p>
    <pre class="layout">123e4567-e89b-12d3-a456-426614174000</pre>
    <p>UUIDs are defined by <a href="https://www.rfc-editor.org/rfc/rfc9562">RFC 9562</a> (May 2024), which replaced RFC 4122 and added versions 6, 7 and 8 plus the max UUID. Because any machine can create one independently, UUIDs are ideal for distributed systems, offline clients, and merging data from separate databases.</p>

    <h2>The same number, many formats</h2>
    <table class="data">
        <thead><tr><th>Format</th><th>Example</th></tr></thead>
        <tbody>
            <tr><td>Standard</td><td><code>123e4567-e89b-12d3-a456-426614174000</code></td></tr>
            <tr><td>GUID / registry</td><td><code>{123E4567-E89B-12D3-A456-426614174000}</code></td></tr>
            <tr><td>URN</td><td><code>urn:uuid:123e4567-e89b-12d3-a456-426614174000</code></td></tr>
            <tr><td>No hyphens</td><td><code>123e4567e89b12d3a456426614174000</code></td></tr>
            <tr><td>Decimal</td><td><code>24249434048109030647017182301789831168</code></td></tr>
        </tbody>
    </table>
    <p>Paste any of them into the <a href="/decode">decoder</a> to convert between formats.</p>

    <h2>How unique is unique?</h2>
    <p>Uniqueness is probabilistic. In a version 4 UUID, 6 of the 128 bits are fixed (version and variant), leaving 122 random bits: roughly 5.3 &times; 10<sup>36</sup> values. Generating a billion UUIDs per second for about 85 years gives only a 50% chance of one duplicate. For real applications, accidental collisions are not a practical concern as long as the random source is cryptographically secure.</p>

    <h2>UUID vs GUID</h2>
    <p>They are the same thing. "GUID" is Microsoft's name (<code>Guid.NewGuid()</code> in .NET, <code>NEWID()</code> in SQL Server) and tends to be written uppercase. One subtlety: .NET stores the first three fields little-endian in memory, so the raw bytes differ from the RFC byte order even though the text is identical. Use the <a href="/generate/guid">GUID generator</a> for Microsoft-style output.</p>

    <h2>UUIDs as database keys</h2>
    <p>UUIDs let applications create IDs before inserting a row, which simplifies distributed writes, offline sync and merging data. The cost is size (16 bytes vs 4 or 8 for integers) and, for random v4, poor index locality. <a href="/generate/v7">Version 7</a> fixes the locality problem because it starts with a timestamp. Store UUIDs in a native <code>uuid</code> column (PostgreSQL) or <code>BINARY(16)</code> (MySQL) rather than as 36-character strings.</p>

    <h2>Which version should I use?</h2>
    <ul>
        <li><a href="/generate/v4">v4</a>: random, general purpose.</li>
        <li><a href="/generate/v7">v7</a>: time-ordered, best for database keys.</li>
        <li><a href="/generate/v5">v5</a>: deterministic IDs from a name.</li>
        <li><a href="/generate/v1">v1</a> / <a href="/generate/v6">v6</a>: legacy time-based.</li>
    </ul>
    <p>Details for every version: <a href="/uuid-versions-explained">UUID versions explained</a>.</p>

    <h2>Frequently asked questions</h2>
    @foreach ($faq as [$q, $a])
        <details class="faq"><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
    @endforeach
</article>
<aside class="side"><div class="card"><h3>Try it</h3>
    <ul class="plain"><li><a href="/">Generate a UUID</a></li><li><a href="/decode">Decode a UUID</a></li><li><a href="/docs">Use the API</a></li></ul></div></aside>
</div>
@endsection
