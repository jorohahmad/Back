<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_merge([
        'https://musical-instruments-dzbd.onrender.com',
        'http://localhost:3000', // منفذ React أو Vue مثلاً
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        'http://localhost:5173',                         // رابط سيرفرك المحلي (Vite)
    ], array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '')))))), // رابط الداشبورد على Cloudflare مثلاً
    // روابط المعاينة الخاصة بـ Cloudflare Pages مثل https://abc123.my-dashboard.pages.dev
    'allowed_origins_patterns' => array_values(array_filter([env('CORS_ALLOWED_ORIGINS_PATTERN')])),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
