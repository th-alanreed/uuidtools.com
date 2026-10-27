<?php

return [
    'max_count' => 1000,

    'api_rate_limit' => (int) env('API_RATE_LIMIT', 60),

    'adsense_client' => env('ADSENSE_CLIENT'),

    // GA4 measurement id. Unset or empty uses the site's id; "false" disables the tag.
    'google_analytics_id' => match ($id = env('GOOGLE_ANALYTICS_ID')) {
        null, '' => 'G-2SCNGWDT9C',
        false => null,
        default => $id,
    },

    // Cloudflare Web Analytics beacon token (the "token" in the dashboard's JS snippet).
    'cloudflare_analytics_token' => env('CLOUDFLARE_ANALYTICS_TOKEN', ''),

    'github_url' => 'https://github.com/th-alanreed/uuidtools.com',

    'status_url' => 'https://status.uuidtools.com',

    'contact_form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLScxoeA8XMJE-ybji8lT1_RselJp8xhd6ZynvUdruxr06f_Img/viewform?embedded=true',
];
