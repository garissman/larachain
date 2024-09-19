<?php

namespace Garissman\Clerk\Intents;

use Garissman\Clerk\Structures\Intent;
use function Pest\Laravel\get;

class UnknownIntent extends Intent
{
    protected array $utterances=[];
    protected array $questions=[
        'How can I help you Today?'
    ];
    protected array $statements=[
        "Sorry I didn't get that"
    ];

    protected array $entities=[];


}
