@extends('layouts.app')
@section('title', 'Contact Us')
@section('description', 'Contact UUIDTools.com with feedback, corrections or feature requests.')
@section('content')
<section class="hero"><h1>Contact us</h1>
    <p class="lead">Feedback, corrections and feature requests are welcome. You can also <a href="{{ config('uuidtools.github_url') }}/issues">open an issue on GitHub</a>.</p></section>
<iframe class="contact-frame" src="{{ config('uuidtools.contact_form_url') }}" title="Contact form" loading="lazy">Loading…</iframe>
@endsection
