<?php

return [

    /*
     * Paths the CORS middleware applies to.
     * Public API v1 is open to all origins so widgets can call it from any
     * third-party site. All other API paths keep the default '*' but can be
     * tightened per environment.
     */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    /*
     * Production: set APP_CORS_ORIGINS in .env to a comma-separated list of
     * allowed domains, e.g. "https://agency.com,https://www.agency.com".
     * Leave unset (or '*') during local development.
     */
    'allowed_origins' => array_filter(
        explode(',', env('APP_CORS_ORIGINS', '*'))
    ) ?: ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 86400, // 24 h preflight cache

    'supports_credentials' => false,

];
