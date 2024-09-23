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
                'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-large'),
                'completion_model' => env('OPENAI_COMPLETION_MODEL', 'gpt-4o'),
                'chat_model' => env('OPENAICHAT_MODEL', 'gpt-4o'),
            ],
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
                'chat_output_model' => env('OLLAMA_COMPLETION_MODEL', 'mistral-nemo'), //this is good to use other systems for better repsonses to people in chat
            ],
        ],
    ],
    'path' => 'larachain',
    'middleware' => ['web'],
    'tools' => [
        ExampleTool::class
    ],

];
