<?php

return [
    'driver' => 'wit',
    'drivers'=>[
        'wit' => [
            'api_token'=>env('WIT_API_TOKEN'),
            'api_version'=>env('WIT_API_VERSION','20240905'),
        ]
    ],
    'intents'=>[
        \Garissman\Clerk\Intents\ResetPasswordIntent::class,
        \Garissman\Clerk\Intents\GettingEmailIntent::class,
        \Garissman\Clerk\Intents\GreetingIntent::class,
        \Garissman\Clerk\Intents\UnknownIntent::class,
    ],
    'conversation'=>[
        'driver' => 'redis',
        'ttl'=>[
            'unit'=>'day',
            'value'=>1
        ]
    ]
];
