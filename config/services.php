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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'mastodon' => [
        'client_id' => env('MASTODON_CLIENT_ID'),
        'client_secret' => env('MASTODON_CLIENT_SECRET'),
        'redirect' => env('MASTODON_REDIRECT_URI', 'http://localhost:8000/auth/mastodon/callback'),
    ],

    'reddit' => [
        'client_id' => env('REDDIT_CLIENT_ID'),
        'client_secret' => env('REDDIT_CLIENT_SECRET'),
        'redirect' => env('REDDIT_REDIRECT_URI', 'http://localhost:8000/auth/reddit/callback'),
    ],

    'twitter' => [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect' => env('TWITTER_REDIRECT_URI', 'http://localhost:8000/auth/twitter/callback'),
    ],

];
