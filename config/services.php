<?php

return [

    /*
    | Medimatch BD matching service (ai-service/, Python FastAPI).
    | Laravel sends it a search phrase and listings that already passed the safety
    | rules; it returns them ranked. If it is down or slow, keyword search is used.
    */
    'matching' => [
        'enabled' => (bool) env('MATCHING_SERVICE_ENABLED', true),
        'url' => rtrim(env('MATCHING_SERVICE_URL', 'http://127.0.0.1:8001'), '/'),
        'key' => env('MATCHING_SERVICE_KEY'),
        'timeout' => (float) env('MATCHING_SERVICE_TIMEOUT', 3),
        // Most listings sent per search (the service accepts up to 200)
        'max_candidates' => 200,
        // After a failure, skip the service for this many seconds instead of waiting for it on every search
        'retry_after' => 60,
    ],


    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

];
