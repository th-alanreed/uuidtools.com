@extends('layouts.app')
@section('title', 'Sitemap')
@section('description', 'All pages on UUIDTools.com: UUID generators for every version, decoder, API docs and guides.')
@section('content')
<section class="hero"><h1>Sitemap</h1></section>
<div class="prose">
    <h2>Generators</h2>
    <ul>
        <li><a href="/">UUID Generator (home)</a></li>
        @foreach ($pages as $p)
            @if ($p->slug !== 'v4')<li><a href="{{ $p->path() }}">{{ $p->heading }}</a></li>@endif
        @endforeach
    </ul>
    <h2>Tools &amp; guides</h2>
    <ul>
        <li><a href="/decode">UUID validator &amp; decoder</a></li>
        <li><a href="/docs">API documentation</a></li>
        <li><a href="/what-is-uuid">What is a UUID?</a></li>
        <li><a href="/uuid-versions-explained">UUID versions explained</a></li>
        <li><a href="/terms">Terms of service &amp; privacy policy</a></li>
        <li><a href="/contact">Contact</a></li>
        <li><a href="{{ config('uuidtools.status_url') }}">Status</a></li>
    </ul>
</div>
@endsection
