<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    |
    | Server Key & Client Key bisa didapat dari:
    | https://dashboard.sandbox.midtrans.com → Settings → Access Keys
    |
    */

    'server_key'    => trim((string) env('MIDTRANS_SERVER_KEY', '')),
    'client_key'    => trim((string) env('MIDTRANS_CLIENT_KEY', '')),
    'is_production' => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
    'is_sanitized'  => true,
    'is_3ds'        => true,
];

