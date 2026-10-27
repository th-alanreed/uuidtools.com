@php
    $siteName = config('app.name');
    $pageTitle = trim(html_entity_decode($__env->yieldContent('title'), ENT_QUOTES)) ?: $siteName;
    $fullTitle = str_contains($pageTitle, $siteName) ? $pageTitle : $pageTitle.' | '.$siteName;
    $description = trim(html_entity_decode($__env->yieldContent('description'), ENT_QUOTES)) ?: 'Free online UUID and GUID generator, decoder and API. All RFC 9562 versions: v1, v3, v4, v5, v6, v7, v8, nil and max.';
    $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
    $isProd = app()->environment('production');
    $gaId = config('uuidtools.google_analytics_id');
    $cfToken = config('uuidtools.cloudflare_analytics_token');
    $asset = fn (string $path) => asset($path).'?v='.(@filemtime(public_path($path)) ?: 1);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ asset('img/logo-lg.png') }}">
    <meta name="twitter:card" content="summary">
    <meta name="theme-color" content="#4f46e5">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-icon-180x180.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="{{ $asset('css/app.css') }}">
    @stack('head')
    @if ($isProd && $gaId)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($gaId) }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config',@json($gaId));</script>
    @endif
    @if ($isProd && config('uuidtools.adsense_client'))
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ config('uuidtools.adsense_client') }}" crossorigin="anonymous"></script>
    @endif
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
    <div class="wrap header-inner">
        <a class="brand" href="/"><img src="/img/logo.png" alt="" width="28" height="28"> {{ $siteName }}</a>
        <nav aria-label="Main">
            <a href="/" @class(['active' => request()->is('/')])>Generator</a>
            <a href="/generate/bulk" @class(['active' => request()->is('generate/bulk')])>Bulk</a>
            <a href="/generate/guid" @class(['active' => request()->is('generate/guid')])>GUID</a>
            <a href="/decode" @class(['active' => request()->is('decode*')])>Decode</a>
            <a href="/docs" @class(['active' => request()->is('docs')])>API</a>
            <a href="/uuid-versions-explained" @class(['active' => request()->is('uuid-versions-explained')])>Versions</a>
            <a href="/what-is-uuid" @class(['active' => request()->is('what-is-uuid')])>What is a UUID?</a>
        </nav>
    </div>
</header>

<main id="main" class="wrap">
    @yield('content')
</main>

<footer class="site-footer">
    <div class="wrap">
        <p class="footer-links">
            @foreach (\App\Uuid\UuidVersion::tabs() as $v)
                <a href="{{ $v->path() }}">{{ $v->label() }}</a>
            @endforeach
        </p>
        <p class="footer-links">
            <a href="/generate/bulk">Bulk UUID generator</a>
            <a href="/generate/guid">GUID generator</a>
            <a href="/decode">UUID decoder</a>
            <a href="/docs">API docs</a>
            <a href="/terms">Terms &amp; privacy</a>
            <a href="/sitemap">Sitemap</a>
            <a href="/contact">Contact</a>
            <a href="{{ config('uuidtools.github_url') }}">GitHub</a>
        </p>
        <p class="muted">&copy; {{ date('Y') }} {{ $siteName }}. Free and open source (MIT). UUIDs are generated on our servers and never stored.</p>
    </div>
</footer>

<script src="{{ $asset('js/uuidtools.js') }}" defer></script>
@stack('scripts')
@if ($isProd && $cfToken)
    <script defer src="https://static.cloudflareinsights.com/beacon.min.js" data-cf-beacon="{{ json_encode(['token' => $cfToken]) }}"></script>
@endif
</body>
</html>
