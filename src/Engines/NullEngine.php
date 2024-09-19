<?php

namespace Garissman\Clerk\Engines;

use Garissman\Clerk\Structures\Intent;

class NullEngine extends Engine
{

    public function train(Intent $intent)
    {
        // TODO: Implement train() method.
    }

    public function startConversation(string $conversationId = null, Intent $intent = null): string
    {
        // TODO: Implement startConversation() method.
    }

    public function message(string $message, string $conversationId = null): mixed
    {
        // TODO: Implement message() method.
    }
}
