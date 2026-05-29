<?php

return [
    'internal_api_key' => env('INTERNAL_API_KEY'),

    'vmserver' => [
        'base_url' => env('VMSERVER_BASE_URL'),
        'token' => env('VMSERVER_INTERNAL_TOKEN'),
        'timeout' => env('VMSERVER_TIMEOUT', 10),
        'enabled' => env('VMSERVER_ENABLED', true),
    ],

    'creditos' => [
        'base_url' => env('VM_CREDITOS_API_BASE_URL'),
        'token' => env('VM_CREDITOS_INTERNAL_TOKEN'),
        'timeout' => env('VM_CREDITOS_TIMEOUT', 10),
        'enabled' => env('VM_CREDITOS_ENABLED', true),
    ],

    'piletas' => [
        'base_url' => env('VM_PILETAS_API_BASE_URL'),
        'token' => env('VM_PILETAS_INTERNAL_TOKEN'),
        'timeout' => env('VM_PILETAS_TIMEOUT', 10),
        'enabled' => env('VM_PILETAS_ENABLED', true),
    ],

    'gym' => [
        'base_url' => env('VM_GYM_API_BASE_URL'),
        'token' => env('VM_GYM_INTERNAL_TOKEN'),
        'timeout' => env('VM_GYM_TIMEOUT', 10),
        'enabled' => env('VM_GYM_ENABLED', true),
    ],
];
