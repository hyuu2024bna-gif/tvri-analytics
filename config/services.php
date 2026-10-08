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

    'youtube' => [
        'key' => env('YOUTUBE_API_KEY'),
        'channel_id' => env('YOUTUBE_CHANNEL_ID'),
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'redirect_uri' => env('YOUTUBE_REDIRECT_URI'),
        'refresh_token' => env('YOUTUBE_REFRESH_TOKEN'),
    ],

    'facebook' => [
        'app_id' => env('FACEBOOK_APP_ID'),
        'app_secret' => env('FACEBOOK_APP_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
        'graph_api_version' => env('META_GRAPH_API_VERSION', 'v19.0'),
        'page_id' => env('META_FACEBOOK_PAGE_ID'),
    ],

    'tiktok' => [
        'client_key' => env('TIKTOK_CLIENT_KEY'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
        'redirect_uri' => env('TIKTOK_REDIRECT_URI'),
        'max_pages' => (int) env('TIKTOK_MAX_PAGES', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Sync Schedule Flags (Production Mode Management)
    |--------------------------------------------------------------------------
    |
    | Production Tahap 1 mengaktifkan YouTube & TikTok.
    | Platform lain (Instagram, Facebook) dinonaktifkan sementara dan dapat
    | diaktifkan kemudian via environment variables tanpa perlu mengubah codebase.
    |
    */
    'sync' => [
        'youtube' => filter_var(env('YOUTUBE_SYNC_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'tiktok' => filter_var(env('TIKTOK_SYNC_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'instagram' => filter_var(env('INSTAGRAM_SYNC_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'facebook' => filter_var(env('FACEBOOK_SYNC_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    ],

];
