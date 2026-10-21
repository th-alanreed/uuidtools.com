@extends('layouts.app')
@section('title', 'UUID Generator API: Free JSON & Plain-Text UUID/GUID API')
@section('description', 'Free UUID and GUID generator API. No key needed. Generate v1, v3, v4, v5, v6, v7, v8, nil and max UUIDs as JSON or plain text, in bulk, and decode or validate any UUID.')
@section('canonical', url('/docs'))

@php $base = rtrim(config('app.url'), '/'); @endphp

@section('content')
<section class="hero">
    <h1>UUID Generator API</h1>
    <p class="lead">Free, no authentication, CORS enabled. JSON by default, plain text on request. {{ config('uuidtools.api_rate_limit') }} requests per minute per IP.</p>
</section>

<div class="content-grid">
<article class="prose">
    <h2 id="quickstart">Quick start</h2>
<pre>curl {{ $base }}/api/generate/v4
curl {{ $base }}/api/generate/v7?count=5&amp;format=text
curl {{ $base }}/api/decode/b01eb720-171a-11ea-b949-73c91bba743d</pre>
    <p>All endpoints accept <code>GET</code> only. Generation endpoints return a JSON array of strings by default. Add <code>?format=text</code> (or send <code>Accept: text/plain</code>) to get one UUID per line, ready for shell scripts.</p>

    <h2 id="generate">Generate</h2>
    <p><code>GET /api/generate/{version}</code> where <code>version</code> is one of <code>v1 v2 v4 v6 v7 v8 nil max timestamp-first</code>. (<code>v3</code> and <code>v5</code> are <a href="#name-based">name-based</a>.)</p>
    <table class="data">
        <thead><tr><th>Parameter</th><th>Default</th><th>Description</th></tr></thead>
        <tbody>
            <tr><td><code>count</code></td><td>1</td><td>1 to {{ number_format(config('uuidtools.max_count')) }}. Also available as a path: <code>/api/generate/v4/count/10</code>. Ignored for nil and max.</td></tr>
            <tr><td><code>format</code></td><td>json</td><td><code>json</code> or <code>text</code>.</td></tr>
            <tr><td><code>upper</code></td><td>0</td><td><code>1</code> for uppercase.</td></tr>
            <tr><td><code>hyphens</code></td><td>1</td><td><code>0</code> removes the hyphens.</td></tr>
            <tr><td><code>braces</code></td><td>0</td><td><code>1</code> wraps in <code>{}</code> (Microsoft GUID registry style).</td></tr>
            <tr><td><code>urn</code></td><td>0</td><td><code>1</code> prefixes <code>urn:uuid:</code>.</td></tr>
            <tr><td><code>quotes</code></td><td>none</td><td><code>double</code> or <code>single</code>.</td></tr>
            <tr><td><code>separator</code></td><td>newline</td><td>Text format only: <code>newline</code>, <code>comma</code>, <code>comma-space</code>, <code>space</code>.</td></tr>
        </tbody>
    </table>
    <h3>Examples</h3>
<pre># 3 random UUIDs
curl "{{ $base }}/api/generate/v4?count=3"
# A GUID, Microsoft style
curl "{{ $base }}/api/generate/v4?upper=1&amp;braces=1&amp;format=text"
# 10 time-ordered UUIDs as a quoted, comma-separated list
curl "{{ $base }}/api/generate/v7?count=10&amp;format=text&amp;quotes=single&amp;separator=comma-space"</pre>
    <p>Every UUID version is documented on its own generator page: <a href="/generate/v4">v4</a>, <a href="/generate/v7">v7</a>, <a href="/generate/v1">v1</a>, <a href="/generate/v6">v6</a>, <a href="/generate/v8">v8</a>, <a href="/generate/nil">nil</a>, <a href="/generate/max">max</a>.</p>
    <p><strong>Privacy:</strong> v1, v2 and v6 UUIDs use a random node ID with the multicast bit set, never our server's MAC address.</p>

    <h2 id="name-based">Name-based UUIDs (v3 and v5)</h2>
    <p>The same namespace and name always return the same UUID, so <code>count</code> does not apply.</p>
<pre>{{ $base }}/api/generate/v5?namespace=ns:dns&amp;name=example.com
{{ $base }}/api/generate/v3/namespace/ns:url/name/https://www.example.com/</pre>
    <ul>
        <li><code>namespace</code>: a UUID, or <code>ns:DNS</code>, <code>ns:URL</code>, <code>ns:OID</code>, <code>ns:X500</code> (case-insensitive).</li>
        <li><code>name</code>: any text. <strong>The name is hashed as UTF-8 bytes</strong>, the same as Python, Java, Go and .NET (<code>Encoding.UTF8</code>). The same input produces the same UUID in all of them.</li>
        <li>Prefix the name with <code>base64:</code> to send it base64-encoded. Use this for names containing <code>/ ? # %</code> or non-ASCII text. Standard and URL-safe base64 are both accepted.</li>
    </ul>
<pre>{{ $base }}/api/generate/v5?namespace=ns:dns&amp;name=base64:RnJhbsOnYWlz</pre>
    <p>The example above is the UTF-8 encoding of <code>Français</code>. Earlier versions of this site hashed non-ASCII names using Latin-1 or failed on characters outside it (<a href="{{ config('uuidtools.github_url') }}/issues/55">issue #55</a>); that is fixed.</p>

    <h2 id="decode">Decode and validate</h2>
    <p><code>GET /api/decode/{uuid}</code> returns everything encoded in a UUID. It accepts hyphenated, bare 32-digit, <code>{braces}</code> and <code>urn:uuid:</code> forms in any case, and never errors on a well-formed UUID, including nil, max, unknown versions and legacy variants. Invalid input returns HTTP 400.</p>
<pre>{{ $base }}/api/decode/b01eb720-171a-11ea-b949-73c91bba743d</pre>
<pre>{
  "valid": true,
  "uuid": "b01eb720-171a-11ea-b949-73c91bba743d",
  "version": 1,
  "version_name": "Gregorian time-based",
  "variant": "RFC 9562 (formerly RFC 4122 / DCE 1.1)",
  "variant_bits": "10",
  "is_nil": false,
  "is_max": false,
  "time": {
    "iso8601": "2019-12-05T04:49:57.9612960Z",
    "unix_seconds": 1575521397,
    "precision": "Exact to 100 nanoseconds"
  },
  "clock_sequence": 14665,
  "node": { "mac": "73:c9:1b:ba:74:3d", "multicast_bit": true },
  "sortable_by_time": false,
  "formats": { "standard": "...", "uppercase": "...", "braces": "...", "urn": "...", "hex": "...", "base64": "...", "integer": "..." },
  "fields": [ { "name": "time_low", "hex": "b01eb720", "description": "..." } ],
  "notes": [ "..." ]
}</pre>
    <p>The <code>time</code> object is present for versions 1, 2, 6 and 7. The legacy <code>encode</code> and <code>decode</code> objects from the previous API are still included for backward compatibility.</p>
    <p><code>GET /api/validate/{uuid}</code> is a lightweight check that always returns HTTP 200: <code>{"valid": true, "version": 4, ...}</code> or <code>{"valid": false}</code>.</p>

    <h2 id="errors">Errors and limits</h2>
    <ul>
        <li><code>400</code> invalid parameters, with <code>{"message": "...", "errors": {"field": ["..."]}}</code>.</li>
        <li><code>404</code> unknown version.</li>
        <li><code>429</code> more than {{ config('uuidtools.api_rate_limit') }} requests per minute from one IP. Check the <code>Retry-After</code> header. Need many UUIDs? Ask for up to {{ number_format(config('uuidtools.max_count')) }} per request, or generate locally with your language's UUID library.</li>
    </ul>

    <h2 id="cors">CORS</h2>
    <p>The API sends <code>Access-Control-Allow-Origin: *</code>, so you can call it from any website. If you use it, please link back to us.</p>
<pre>const [uuid] = await (await fetch('{{ $base }}/api/generate/v7')).json();</pre>
</article>

<aside class="side">
    <div class="card">
        <h3>On this page</h3>
        <ul class="plain">
            <li><a href="#quickstart">Quick start</a></li>
            <li><a href="#generate">Generate</a></li>
            <li><a href="#name-based">Name-based (v3, v5)</a></li>
            <li><a href="#decode">Decode &amp; validate</a></li>
            <li><a href="#errors">Errors &amp; limits</a></li>
            <li><a href="#cors">CORS</a></li>
        </ul>
    </div>
</aside>
</div>
@endsection
