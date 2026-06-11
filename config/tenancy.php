<?php

return [
    'mode' => env('TENANCY_MODE', 'saas'), // 'saas' or 'self_hosted'
    'central_domain' => env('CENTRAL_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?? 'villacrm.app'),
];
