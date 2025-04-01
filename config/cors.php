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

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'oauth/token'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['https://cam-dev.epl.ca','https://cam.epl.ca','https://epl-cam.epl.ca' ,'http://localhost:4500', 'https://cam-staging.epl.ca'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [ 'Accept', 'Authorization', 'Content-Type', 'X-Requested-With', 'X-CRE-Token', 'X-Sanctum-Token'],

    'exposed_headers' => [],

    'hosts' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];