<?php

return [
    'max_count' => 1000,

    'api_rate_limit' => (int) env('API_RATE_LIMIT', 60),

    'adsense_client' => env('ADSENSE_CLIENT'),

    'google_analytics_id' => env('GOOGLE_ANALYTICS_ID'),

    'github_url' => 'https://github.com/th-alanreed/uuidtools.com',

    'status_url' => 'https://status.uuidtools.com',

    'contact_form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLScxoeA8XMJE-ybji8lT1_RselJp8xhd6ZynvUdruxr06f_Img/viewform?embedded=true',
];
