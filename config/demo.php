<?php

return [

    'enabled' => env('DEMO_MODE', false),

    // Prefer IDs in deployed environments. The fallbacks support existing seeds.
    'user_id' => env('DEMO_USER_ID'),
    'tenant_id' => env('DEMO_TENANT_ID'),
    'tenant_slug' => env('DEMO_TENANT_SLUG', 'barbearia-demo'),

    'email' => env(
        'DEMO_EMAIL',
        'demo@barbearia.app'
    ),

];
