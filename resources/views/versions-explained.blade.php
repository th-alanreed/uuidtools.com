@extends('layouts.app')
@section('title', 'UUID Versions Explained: v1, v3, v4, v5, v6, v7, v8, Nil & Max')
@section('description', 'Compare every UUID version in RFC 9562: v1, v2, v3, v4, v5, v6, v7, v8, nil and max. Structure diagrams, sorting, privacy and when to use each. v4 vs v7 explained.')
@section('canonical', url('/uuid-versions-explained'))

@section('content')
<section class="hero">
    <h1>UUID Versions Explained</h1>
    <p class="lead">What each version of RFC 9562 does and which one to choose.</p>
</section>

<div class="content-grid">
<article class="prose">
    <p>The version is the 13th hex digit of a UUID (the <code>M</code> in <code>xxxxxxxx-xxxx-Mxxx-Nxxx-xxxxxxxxxxxx</code>). RFC 9562 defines versions 1 through 8 plus two special values. Newer is not automatically better: each version solves a different problem.</p>

    <h2>Comparison</h2>
    <table class="data">
        <thead><tr><th>Version</th><th>Based on</th><th>Sortable</th><th>Leaks time?</th><th>Best for</th></tr></thead>
        <tbody>
            <tr><td><a href="/generate/v1">v1</a></td><td>Gregorian time + node</td><td>No</td><td>Yes (+ node)</td><td>Legacy systems</td></tr>
            <tr><td>v2</td><td>DCE Security</td><td>No</td><td>Partly</td><td>Almost nothing</td></tr>
            <tr><td><a href="/generate/v3">v3</a></td><td>MD5(namespace + name)</td><td>No</td><td>No</td><td>Legacy deterministic IDs</td></tr>
            <tr><td><a href="/generate/v4">v4</a></td><td>Random</td><td>No</td><td>No</td><td>General-purpose IDs</td></tr>
            <tr><td><a href="/generate/v5">v5</a></td><td>SHA-1(namespace + name)</td><td>No</td><td>No</td><td>Deterministic IDs</td></tr>
            <tr><td><a href="/generate/v6">v6</a></td><td>Reordered v1 time + node</td><td>Yes</td><td>Yes</td><td>Migrating from v1</td></tr>
            <tr><td><a href="/generate/v7">v7</a></td><td>Unix ms time + random</td><td>Yes</td><td>Yes</td><td>Database primary keys</td></tr>
            <tr><td><a href="/generate/v8">v8</a></td><td>Your own layout</td><td>Your choice</td><td>Your choice</td><td>Custom formats</td></tr>
            <tr><td><a href="/generate/nil">Nil</a></td><td>All zeros</td><td>n/a</td><td>No</td><td>"No UUID" placeholder</td></tr>
            <tr><td><a href="/generate/max">Max</a></td><td>All ones</td><td>n/a</td><td>No</td><td>Sentinel / upper bound</td></tr>
        </tbody>
    </table>

    <h2 id="v4-vs-v7">UUID v4 vs v7</h2>
    <p><strong>Choose v4</strong> when you need an unpredictable identifier that reveals nothing. <strong>Choose v7</strong> when UUIDs are database keys or event IDs and chronological order helps: v7 starts with a 48-bit millisecond timestamp, so inserts append to the end of an index instead of landing at random positions. v4 has 122 random bits; v7 has 74 random bits plus the timestamp.</p>

    <h2 id="version-1">Version 1: time and node</h2>
    <p>60-bit timestamp in 100-nanosecond intervals since 15 October 1582, a 14-bit clock sequence, and a 48-bit node (traditionally the MAC address). The timestamp bits are stored low-to-high, so v1 does not sort by time. Because it can expose a MAC address, our generator uses a random multicast node. <a href="/generate/v1">Generate v1</a>.</p>
    <pre class="layout">time_low (32) | time_mid (16) | 0001 + time_high (16) | variant + clock_seq (16) | node (48)</pre>

    <h2 id="version-2">Version 2: DCE Security</h2>
    <p>Version 1 with the low timestamp bits replaced by a local identifier (such as a POSIX UID) and a domain. Defined by DCE 1.1, only reserved by RFC 9562, and very rarely used. <a href="/generate/v2">Generate v2</a>.</p>

    <h2 id="version-3">Versions 3 and 5: names and hashes</h2>
    <p>Hash a namespace UUID plus a name (MD5 for v3, SHA-1 for v5) and stamp in the version and variant bits. The same input always yields the same UUID, which makes them ideal for stable IDs derived from URLs, domains or natural keys. Always hash the name as UTF-8 for cross-language compatibility. Prefer v5 over v3. <a href="/generate/v3">v3</a> &middot; <a href="/generate/v5">v5</a>.</p>

    <h2 id="version-4">Version 4: random</h2>
    <p>122 random bits. No structure, no information. <a href="/generate/v4">Generate v4</a>.</p>
    <pre class="layout">random (48) | 0100 + random (12) | variant + random (62)</pre>

    <h2 id="version-6">Version 6: sortable v1</h2>
    <p>The same timestamp, clock sequence and node as v1 with the timestamp bits reordered most-significant first so UUIDs sort by creation time. Intended as a bridge for existing v1 users. <a href="/generate/v6">Generate v6</a>.</p>
    <pre class="layout">time_high (32) | time_mid (16) | 0110 + time_low (16) | variant + clock_seq (16) | node (48)</pre>

    <h2 id="version-7">Version 7: Unix time and random</h2>
    <p>The recommended time-ordered UUID. 48-bit Unix timestamp in milliseconds, then random bits. Implementations keep UUIDs generated in the same millisecond monotonic. <a href="/generate/v7">Generate v7</a>.</p>
    <pre class="layout">unix_ts_ms (48) | 0111 + rand_a (12) | variant + rand_b (62)</pre>

    <h2 id="version-8">Version 8: custom</h2>
    <p>Only the version and variant bits are fixed; the other 122 bits are yours. Useful for embedding shard IDs or for hashing names with SHA-256. Do not expect other systems to understand your layout. <a href="/generate/v8">Generate v8</a>.</p>

    <h2 id="nil-max">Nil and Max</h2>
    <p>The Nil UUID <code>00000000-0000-0000-0000-000000000000</code> and the Max UUID <code>ffffffff-ffff-ffff-ffff-ffffffffffff</code> are special constants. <a href="/generate/nil">Nil</a> &middot; <a href="/generate/max">Max</a>.</p>

    <h2>The variant</h2>
    <p>The top bits of the 17th hex digit say which UUID family the value belongs to. Every UUID described here uses the <code>10xx</code> variant (digit 8, 9, a or b). Other variants are reserved for legacy NCS and Microsoft GUIDs. See the <a href="/decode">decoder</a> to inspect any UUID.</p>
</article>
<aside class="side"><div class="card"><h3>Quick pick</h3>
    <ul class="plain"><li>Random ID: <a href="/generate/v4">v4</a></li><li>Database key: <a href="/generate/v7">v7</a></li><li>Same name = same ID: <a href="/generate/v5">v5</a></li><li>Microsoft style: <a href="/generate/guid">GUID</a></li></ul></div></aside>
</div>
@endsection
