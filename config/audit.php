<?php

return [
    'enabled' => env('AUDITING_ENABLED', true),

    'implementation' => OwenIt\Auditing\Models\Audit::class,

    'user' => [
        'morph_prefix' => 'user',
        'guards' => [
            'web',
            'api',
        ],
        'resolver' => OwenIt\Auditing\Resolvers\UserResolver::class,
        'model' => App\Models\User::class, // Added
    ],

    'resolvers' => [
        'ip_address' => OwenIt\Auditing\Resolvers\IpAddressResolver::class,
        'user_agent' => OwenIt\Auditing\Resolvers\UserAgentResolver::class,
        'url' => OwenIt\Auditing\Resolvers\UrlResolver::class,
    ],

    'events' => [
        'created',
        'updated',
        'deleted',
        'restored',
    ],

    'strict' => false,

    'exclude' => [
        'worked_days', // Exclude large JSON field
        'created_at',
        'updated_at',
    ],

    'empty_values' => true,
    'allowed_empty_values' => [
        'retrieved',
    ],

    'allowed_array_values' => false,

    'timestamps' => false,

    'threshold' => 100, // Limit to 100 audit records per model

    'driver' => 'database',

    'drivers' => [
        'database' => [
            'table' => 'audits',
            'connection' => null,
        ],
    ],

    'queue' => [
        'enable' => true,
        'connection' => 'database', // Or 'redis' if configured
        'queue' => 'audits',
        'delay' => 0,
    ],

    'console' => false,
];