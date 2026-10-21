@extends('layouts.app')
@section('title', 'Page not found')
@section('content')
    <section class="hero">
        <h1>404: that page does not exist</h1>
        <p class="lead">Here is a UUID for your trouble: <code>{{ \Ramsey\Uuid\Uuid::uuid7()->toString() }}</code></p>
        <p><a class="btn" href="/">Go to the UUID generator</a></p>
    </section>
@endsection
