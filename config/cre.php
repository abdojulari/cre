<?php

return [   
    /* ILS Configuration */
    'base_url_prod' => env('ILS_PROD_URL', 'ils_prod_url'),
    'ils_base_url' => env('ILS_BASE_URL', 'ils_base_url'),
    'symws_user' => env('SYMWS_USER', 'symws_user'),
    'symws_pass' => env('SYMWS_PASS', 'symws_pass'),
    'symws_client_id' => env('SYMWSCLIENTID', 'symws_client_id'),
    'apps_id' => env('APPS_ID', 'apps_id'),
    'endpoint' => env('ENDPOINT', 'endpoint'),
    'patron_endpoint' => env('PATRON_ENDPOINT', 'patron_endpoint'),
    'start_barcode' => env('START_BARCODE', 'start_barcode'),
    'barcode_prefix' => env('BARCODE_PREFIX', 'barcode_prefix'),
    'start_digital_barcode' => env('START_DIGITAL_BARCODE', 'start_digital_barcode'),
    'digital_barcode_prefix' => env('DIGITAL_BARCODE_PREFIX', 'digital_barcode_prefix'),
    'barcode_url' => env('BARCODE_URL', 'barcode_url'),
    'user_auth' => env('USER_AUTH', 'user_auth'),
    'rate_limit' => env('RATE_LIMIT', 'rate_limit'),
    'custom_security_token' => env('CUSTOM_SECURITY_TOKEN', 'custom_security_token'),
    'email' => env('EMAIL_ADDRESS', 'abdul.ojulari@epl.ca'),
];