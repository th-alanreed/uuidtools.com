# UUIDTools.com

[UUIDTools.com](https://www.uuidtools.com) is a free, open-source UUID and GUID generator, validator/decoder and API.

- Every [RFC 9562](https://www.rfc-editor.org/rfc/rfc9562) version: v1, v3, v4, v5, v6, v7, v8, plus nil and max (and the legacy DCE v2).
- GUID formatting (uppercase, braces, URN, no hyphens, quotes, separators), bulk generation up to 1,000, copy and download buttons.
- Validate and decode any UUID: version, variant, embedded timestamp, clock sequence, node, every common format.
- Plain-text and JSON API, CORS enabled, no key required. See [/docs](https://www.uuidtools.com/docs).
- Works without JavaScript; JavaScript only adds live regeneration, formatting and copy buttons.

## Stack

Laravel 13 on PHP 8.4. No database, no queue, no scheduler, no sessions or cookies, no Node build step.
Deploying is `composer install` plus a handful of environment variables.

Why Laravel (and not something lighter)? The site is a handful of routes, but Laravel 13 is the current framework,
Forge sets up and deploys Laravel sites out of the box, the repo was already Laravel, and we stay on a supported
security-patched stack. The app disables everything it does not use (sessions, cookies, CSRF), so it stays small and fast.
UUID generation uses [ramsey/uuid](https://github.com/ramsey/uuid) (all RFC 9562 versions); decoding is pure PHP
(`app/Uuid/UuidInspector.php`), so there is no dependency on the `uuid` command-line tool any more.

## Requirements

- PHP 8.4 with the usual Laravel extensions (`mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`). Forge servers have all of them.
- Composer 2

## Local setup

```bash
composer setup        # install, create .env, generate APP_KEY
php artisan serve     # http://127.0.0.1:8000
composer test         # PHPUnit
```

## Configuration

Copy `.env.example` to `.env`. These are the only values that matter:

| Variable | Default | Purpose |
| --- | --- | --- |
| `APP_KEY` | none | Required. `php artisan key:generate --show` |
| `APP_ENV` | `production` | Ads and analytics are only rendered in `production` |
| `APP_DEBUG` | `false` | Keep `false` in production |
| `APP_URL` | `https://www.uuidtools.com` | Used for canonical URLs and the sitemap |
| `ADSENSE_CLIENT` | existing publisher id | Leave empty to disable Google AdSense |
| `GOOGLE_ANALYTICS_ID` | `G-2SCNGWDT9C` | GA4 measurement id. Unset or empty uses the default; `false` disables the tag |
| `CLOUDFLARE_ANALYTICS_TOKEN` | empty | Cloudflare Web Analytics beacon token (dashboard: Web Analytics, the site, Manage site, JS snippet). Empty disables the beacon |
| `API_RATE_LIMIT` | `60` | API requests per minute per IP |
| `CACHE_STORE` | `file` | Only used by the rate limiter |

## Deploying on Laravel Forge

1. **Create a site** on a Forge server running PHP 8.4. Domain `www.uuidtools.com` (and `uuidtools.com`), project type
   *Laravel*, web directory `/public`. Forge needs nothing else: no database, no queue worker, no scheduler.
2. **Install the repository**: `th-alanreed/uuidtools.com`, branch `master`, and *untick* "Install Composer dependencies"
   (the deploy script below does it) or leave it ticked, either works.
3. **Environment**: open the site's *Environment* tab and paste the contents of `.env.example`. Then set `APP_KEY`
   (run `php artisan key:generate --show` anywhere and paste the `base64:...` value).
4. **Deploy script** (Site, *Deployments*):

   ```bash
   cd $FORGE_SITE_PATH
   git pull origin $FORGE_SITE_BRANCH

   $FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

   ( flock -w 10 9 || exit 1
       echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

   $FORGE_PHP artisan optimize
   ```

   `artisan optimize` caches config, routes, views and events. There are no migrations to run.
5. **SSL**: issue a Let's Encrypt certificate in Forge (or put the site behind Cloudflare). Optionally enable
   *Quick Deploy* so every push to `master` deploys.
6. **Health check**: `https://www.uuidtools.com/up` returns 200 when the app is healthy; point your uptime monitor there.

Behind Cloudflare, the app already trusts Cloudflare's proxy ranges (`bootstrap/cloudflare-proxies.php`) so rate limiting
uses the visitor's real IP. If you proxy through something else, edit that file.

## API

```text
GET /api/generate/{version}                       -> ["uuid"]            (v1 v2 v3 v4 v5 v6 v7 v8 nil max timestamp-first)
GET /api/generate/{version}/count/{n}             -> n UUIDs (max 1000)
GET /api/generate/{version}?format=text           -> plain text, one per line (or send Accept: text/plain)
GET /api/generate/v5?namespace=ns:DNS&name=x      -> name-based (v3 and v5)
GET /api/decode/{uuid}                            -> version, variant, timestamp, node, formats
GET /api/validate/{uuid}                          -> {"valid": true}
```

Formatting parameters: `upper=1`, `hyphens=0`, `braces=1`, `urn=1`, `quotes=single|double`, `separator=newline|comma|comma-space|space`.
Names for v3 and v5 are hashed as UTF-8. To send arbitrary bytes or avoid URL-encoding issues, prefix the name with
`base64:`. Full documentation with live examples is at [/docs](https://www.uuidtools.com/docs).

## Tests

```bash
composer test
```

The suite covers the decoder (including the regression for #57), every generator version, UTF-8 name hashing (#55)
against reference vectors, the HTTP API and the pages.

## MIT License

Copyright 2020 Alan Reed

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
