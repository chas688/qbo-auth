<?php

return [
    'environment' => env('QBO_ENVIRONMENT', 'sandbox'),
    'client_id' => env('QBO_CLIENT_ID'),
    'client_secret' => env('QBO_CLIENT_SECRET'),
    'redirect_uri' => env('QBO_REDIRECT_URI', 'http://localhost:8000/oauth/quickbooks/callback'),
    'scope' => 'com.intuit.quickbooks.accounting',
    'logging' => env('QBO_LOGGING', false),
    'register_routes' => true,
    'route_prefix' => 'oauth/quickbooks',
    'route_middleware' => ['web'],
];
