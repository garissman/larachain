<?php


use Garissman\LaraChain\Functions\ExampleTool;

return [
    'driver' => 'openai',
    'embedding_driver' => 'openai',
    'drivers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'api_url' => env('OPENAI_API_URL', 'https://api.openai.com/v1'),
            'stream'=> env('OPENAI_STREAM', false),
            'models' => [
                'completion_model' => env('OPENAI_COMPLETION_MODEL', 'gpt-4o'),
                'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-large'),
                'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4o'),
            ],
            'async'=>env('OPENAI_ASYNC', false),
        ],
        'ollama' => [
            'feature_flags' => [
                'functions' => env('OLLAMA_FUNCTIONS', false),
            ],
            'api_key' => 'ollama',
            'api_url' => env('OLLAMA_API_URL', 'http://localhost:11434/api/'),
            'stream'=> env('OLLAMA_STREAM', false),
            'models' => [
                //@see https://github.com/ollama/ollama/blob/main/docs/openai.md
                'completion_model' => env('OLLAMA_COMPLETION_MODEL', 'mistral-nemo'),
                'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'mxbai-embed-large'),
                'chat_model' => env('OLLAMA_CHAT_MODEL', 'mistral-nemo'),
            ],
            'async'=>env('OLLAMA_ASYNC', false),
        ],
    ],
    'path' => 'larachain',
    'middleware' => ['web'],
    'tools' => [
        ExampleTool::class
    ],

];
