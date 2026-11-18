<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Instance URL
    |--------------------------------------------------------------------------
    |
    | The URL of your Feedback Pilgrim instance (its APP_URL), for example
    | "https://feedback.example.com". Each user runs an own instance, so
    | this value is required.
    |
    */

    'url' => env('FEEDBACKPILGRIM_URL'),

    /*
    |--------------------------------------------------------------------------
    | API token
    |--------------------------------------------------------------------------
    |
    | A personal access token of the client. Make one in Settings, API
    | Tokens. The SDK sends it as a bearer token. Keep it on the server.
    |
    */

    'token' => env('FEEDBACKPILGRIM_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Identity secret
    |--------------------------------------------------------------------------
    |
    | The identity secret of the client. The SDK uses it to make the
    | "user_hash" of the widget. Never put this value in browser code.
    |
    */

    'identity_secret' => env('FEEDBACKPILGRIM_IDENTITY_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    |
    | The signing secret of your webhook, and the maximum difference in
    | seconds between the timestamp of a delivery and the current time.
    |
    */

    'webhook' => [
        'secret' => env('FEEDBACKPILGRIM_WEBHOOK_SECRET'),
        'tolerance' => (int) env('FEEDBACKPILGRIM_WEBHOOK_TOLERANCE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout and retries
    |--------------------------------------------------------------------------
    |
    | The request timeout in seconds, and the number of retries after a 429,
    | a 5xx or a connection error.
    |
    */

    'timeout' => (float) env('FEEDBACKPILGRIM_TIMEOUT', 30),

    'retries' => (int) env('FEEDBACKPILGRIM_RETRIES', 2),

];
