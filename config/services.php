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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
   // Faraz SMS Edge (IPPANEL)
    // Compatible with both new (FARAZ_EDGE_*) and legacy (FARAZSMS_*) environment keys.
    'faraz_edge' => [
        'token' => env('FARAZ_EDGE_TOKEN')
            ?: env('FARAZSMS_API_KEY'),

        'from' => env('FARAZ_EDGE_FROM')
            ?: env('FARAZSMS_FROM'),

        'base_url' => rtrim(
            env('FARAZ_EDGE_BASE_URL')
                ?: env('FARAZSMS_BASE_URL', 'https://edge.ippanel.com/v1'),
            '/'
        ),
    ],

    'navatel' => [
        'webhook_token' => env('NAVATEL_WEBHOOK_TOKEN'),
    ],

    'novatel' => [
        'base_url' => rtrim(env('NOVATEL_BASE_URL', 'https://navaphone.com'), '/'),
        'tenant' => env('NOVATEL_TENANT'),
        'token' => env('NOVATEL_TOKEN'),
        'api_key' => env('NOVATEL_API_KEY'),
        'queue_id' => env('NOVATEL_QUEUE_ID'),
        'destination' => env('NOVATEL_DESTINATION'),
        'call_type' => env('NOVATEL_CALL_TYPE'),
        'cdr_default_minutes' => env('NOVATEL_CDR_DEFAULT_MINUTES', 10),
        'use_full_logs' => env('NOVATEL_CDR_FULL_LOGS', false),
    ],

];
