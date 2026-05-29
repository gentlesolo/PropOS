<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'phone_number_id'      => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token'         => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token'         => env('WHATSAPP_VERIFY_TOKEN', 'propos-whatsapp-verify'),
        'api_version'          => env('WHATSAPP_API_VERSION', 'v19.0'),
        'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '234'),
    ],

    'property24' => [
        'api_key'  => env('PROPERTY24_API_KEY'),
        'base_url' => env('PROPERTY24_BASE_URL', 'https://api.property24.com/v1'),
    ],

    'propertypro' => [
        'api_key'  => env('PROPERTYPRO_API_KEY'),
        'base_url' => env('PROPERTYPRO_BASE_URL', 'https://api.propertypro.ng/v2'),
    ],

];
