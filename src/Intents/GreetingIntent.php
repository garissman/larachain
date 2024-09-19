<?php

namespace Garissman\Clerk\Intents;

use Garissman\Clerk\Structures\Intent;
use function Pest\Laravel\get;

class GreetingIntent extends Intent
{
    protected array $utterances=[
        'hello',
        'Hello',
        'Hello World!',
        'Hi'
    ];
    protected array $questions=[
        'How can I help you Today?'
    ];
    protected array $statements=[
        "Hello, there!",
        "Hi, there!"
    ];

    protected array $entities=[];


}
