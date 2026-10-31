<?php

return [
    'accounts' => [
        'base_url' => env('ACCOUNTS_BASE_URL', 'https://dictr2.cloud'),
        'client_id' => env('ACCOUNTS_CLIENT_ID'),
        'client_secret' => env('ACCOUNTS_CLIENT_SECRET'),
        'scope' => env('ACCOUNTS_SCOPE', 'profile email'),
        'redirect' => env('ACCOUNTS_REDIRECT_URI', 'http://localhost:8000/auth/callback'),
        'authorize_endpoint' => '/oauth/authorize',
        'token_endpoint' => '/oauth/token',
        'user_endpoint' => '/api/user',
        'revoke_endpoint' => '/oauth/tokens',
    ],
];
