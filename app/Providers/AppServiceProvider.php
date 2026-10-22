<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(config('uuidtools.api_rate_limit'))->by($request->ip()));

        // Behind Cloudflare Flexible SSL the origin only sees plain HTTP, so take the scheme from APP_URL.
        URL::forceHttps(str_starts_with((string) config('app.url'), 'https://'));
    }
}
