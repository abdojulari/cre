<?php

return [   
    /* ILS Configuration */
    'base_url_prod' => env('ILS_PROD_URL', 'ils_prod_url'),
    'base_url_dev' => env('ILS_DEV_URL', 'ils_dev_url'),
    'symws_user' => env('SYMWS_USER', 'symws_user'),
    'symws_pass' => env('SYMWS_PASS', 'symws_pass'),
    'symws_client_id' => env('SYMWSCLIENTID', 'symws_client_id'),
    'apps_id' => env('APPS_ID', 'apps_id'),
    'endpoint' => env('ENDPOINT', 'endpoint'),
    'patron_endpoint' => env('PATRON_ENDPOINT', 'patron_endpoint'),
    'last_barcode' => env('LAST_BARCODE', 'last_barcode'),
];