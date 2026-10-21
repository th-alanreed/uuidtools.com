@extends('layouts.app')

@php
    $version = $page->version;
    $nameBased = $version->isNameBased();
    $canonical = $home || $page->slug === 'v4' ? url('/') : url($page->path());
    $apiBase = url('/api/generate/'.($version === \App\Uuid\UuidVersion::TimestampFirst ? 'timestamp-first' : $version->value));
    $title = $home ? 'UUID Generator: Online UUID & GUID Generator (v4, v7, v1, v5)' : $page->title;
    $h1 = $home ? 'Online UUID Generator' : $page->heading;
    $faq = $content['faq'];
@endphp

@section('title', $title)
@section('description', $home ? 'Free online UUID & GUID generator. Create v4, v7, v1, v3, v5, v6, v8, nil and max UUIDs instantly. Bulk generate up to 1,000, copy with one click, validate and decode UUIDs, or use the free API.' : $page->description)
@section('canonical', $canonical)

@push('head')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => array_values(array_filter([
        ['@type' => 'WebApplication', 'name' => $page->heading, 'url' => $canonical, 'description' => $page->description, 'applicationCategory' => 'DeveloperApplication', 'operatingSystem' => 'Any', 'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD']],
        $faq ? ['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faq)] : null,
    ])),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<section class="hero">
    <h1>{{ $h1 }}</h1>
    <p class="lead">{{ $version->summary() }}</p>
</section>

<nav class="tabs" aria-label="UUID versions">
    @foreach (\App\Uuid\UuidVersion::tabs() as $tab)
        <a href="{{ $tab->path() }}" @class(['active' => $tab === $version && ! in_array($page->slug, ['guid', 'bulk', 'minecraft'])]) @if ($tab === $version && ! in_array($page->slug, ['guid', 'bulk', 'minecraft'])) aria-current="page" @endif title="{{ $tab->longLabel() }}">{{ $tab->label() }}</a>
    @endforeach
    <a href="/generate/guid" @class(['active' => $page->slug === 'guid'])>GUID</a>
    <a href="/generate/bulk" @class(['active' => $page->slug === 'bulk'])>Bulk</a>
</nav>

<section class="card generator"
    id="generator"
    data-api="{{ $apiBase }}"
    data-version="{{ $version->value }}"
    data-name-based="{{ $nameBased ? '1' : '0' }}"
    data-supports-count="{{ $version->supportsCount() ? '1' : '0' }}"
    data-max="{{ config('uuidtools.max_count') }}"
    data-raw="{{ json_encode($initial) }}"
    data-preset="{{ json_encode($page->preset) }}"
    data-storage="uuidtools:{{ $page->slug }}">

    @if ($nameBased)
        <form id="name-form" class="name-form" autocomplete="off">
            <label>Namespace
                <select id="namespace">
                    <option value="ns:dns">DNS (domain names)</option>
                    <option value="ns:url">URL</option>
                    <option value="ns:oid">OID (ISO object identifiers)</option>
                    <option value="ns:x500">X.500 (distinguished names)</option>
                    <option value="custom">Custom UUID…</option>
                </select>
            </label>
            <label id="custom-namespace-label" hidden>Custom namespace UUID
                <input type="text" id="custom-namespace" placeholder="00000000-0000-0000-0000-000000000000" spellcheck="false">
            </label>
            <label>Name
                <input type="text" id="name" value="example.com" required spellcheck="false">
            </label>
            <p class="hint">The name is hashed as <strong>UTF-8</strong>, so non-ASCII text (é, 日本語, emoji) matches Python, Java, Go and .NET. The same namespace + name always gives the same UUID.</p>
            <button class="btn primary" type="submit">Generate {{ $version->label() }} UUID</button>
        </form>
    @endif

    <div class="output-wrap">
        <textarea id="output" class="output" readonly rows="{{ min(max(count($initial), 1), 12) }}" aria-label="Generated UUIDs" spellcheck="false">{{ $initialText }}</textarea>
        <p id="status" class="status" role="status" aria-live="polite"></p>
    </div>

    <div class="actions">
        @unless ($nameBased)
            <button class="btn primary" id="generate" type="button">Generate new</button>
        @endunless
        <button class="btn" id="copy" type="button">Copy</button>
        <button class="btn" id="download" type="button">Download .txt</button>
        <button class="btn" id="copy-json" type="button">Copy as JSON</button>
        @unless ($version->isConstant())
            <a class="btn" id="decode-link" href="/decode?uuid={{ $initial[0] }}">Decode</a>
        @endunless
    </div>

    <details class="options" @if ($page->slug === 'bulk' || $page->slug === 'guid') open @endif>
        <summary>Options &amp; format</summary>
        <div class="option-grid">
            @if ($version->supportsCount())
                <label>How many (1 - {{ number_format(config('uuidtools.max_count')) }})
                    <input type="number" id="count" min="1" max="{{ config('uuidtools.max_count') }}" value="{{ $page->count }}">
                </label>
            @endif
            <label class="check"><input type="checkbox" id="opt-upper" @checked($formatter->uppercase)> Uppercase</label>
            <label class="check"><input type="checkbox" id="opt-hyphens" @checked($formatter->hyphens)> Hyphens</label>
            <label class="check"><input type="checkbox" id="opt-braces" @checked($formatter->braces)> Braces {&nbsp;}</label>
            <label class="check"><input type="checkbox" id="opt-urn"> urn:uuid:</label>
            <label>Quotes
                <select id="opt-quotes">
                    <option value="none">None</option>
                    <option value="double">"double"</option>
                    <option value="single">'single'</option>
                </select>
            </label>
            <label>Separator
                <select id="opt-separator">
                    <option value="newline">New line</option>
                    <option value="comma">Comma</option>
                    <option value="comma-space">Comma + space</option>
                    <option value="space">Space</option>
                </select>
            </label>
        </div>
        <p class="hint">Formatting is applied instantly in your browser. A GUID is a UUID: tick Uppercase + Braces for the Microsoft registry style.</p>
    </details>

    <div class="api-box">
        <div class="api-head"><strong>API</strong> <button class="btn small" id="copy-api" type="button">Copy URL</button> <button class="btn small" id="copy-curl" type="button">Copy curl</button></div>
        <code id="api-url" class="api-url">{{ $nameBased ? $apiBase.'?namespace=ns:dns&name=base64:ZXhhbXBsZS5jb20' : $apiBase }}</code>
        <p class="hint"><a href="/docs">Plain-text and JSON API docs</a>: no key required.</p>
    </div>
</section>

<div class="content-grid">
    <article class="prose">
        <h2>About {{ $version->isConstant() ? 'the '.$version->longLabel() : $version->longLabel().'s' }}</h2>
        @foreach ($content['intro'] as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach

        @if ($content['layout'])
            <h3>Structure</h3>
            <pre class="layout">{{ $content['layout'] }}</pre>
        @endif

        @if ($content['when'])
            <h3>When to use it</h3>
            <ul>@foreach ($content['when'] as $item)<li>{{ $item }}</li>@endforeach</ul>
        @endif

        @if ($content['avoid'])
            <h3>When to avoid it</h3>
            <ul>@foreach ($content['avoid'] as $item)<li>{{ $item }}</li>@endforeach</ul>
        @endif

        @if ($faq)
            <h3>Frequently asked questions</h3>
            @foreach ($faq as [$question, $answer])
                <details class="faq"><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>
            @endforeach
        @endif

        @if ($home)
            <h2>Why use an online UUID generator?</h2>
            <p>Every language can generate UUIDs, but sometimes you just need one right now for a test fixture, a config file, a SQL insert or a bug report, without opening a REPL. UUIDTools gives you any RFC 9562 version in one click, in the exact format you need, and the same thing is available from the <a href="/docs">API</a> for scripts.</p>
            <p>Not sure which version to pick? Use <strong>v4</strong> for plain random IDs and <strong>v7</strong> for database keys. See <a href="/uuid-versions-explained">UUID versions explained</a>.</p>
        @endif
    </article>

    <aside class="side">
        <div class="card">
            <h3>All generators</h3>
            <ul class="plain">
                @foreach (\App\Uuid\GeneratorPage::all() as $p)
                    @if ($p->slug !== $page->slug)
                        <li><a href="{{ $p->path() }}">{{ $p->heading }}</a></li>
                    @endif
                @endforeach
            </ul>
        </div>
        <div class="card">
            <h3>Tools</h3>
            <ul class="plain">
                <li><a href="/decode">Validate &amp; decode a UUID</a></li>
                <li><a href="/docs">API documentation</a></li>
                <li><a href="/uuid-versions-explained">UUID versions explained</a></li>
                <li><a href="/what-is-uuid">What is a UUID?</a></li>
            </ul>
        </div>
    </aside>
</div>
@endsection
