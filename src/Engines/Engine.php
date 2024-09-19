<?php

namespace Garissman\Clerk\Engines;

use Garissman\Clerk\Structures\Intent;

abstract class Engine
{
    /**
     * Train Model from Clerk Intent.
     *
     * @param Intent $intent
     * @return mixed
     */
    abstract public function train(Intent $intent):mixed ;

    /**
     * Start Conversation.
     *
     * @param string|null $conversationId
     * @param Intent|null $intent
     * @return mixed
     */
    abstract public function startConversation(string $conversationId=null, Intent $intent=null): string;

    /**
     * Send to model user Message and predict Intent.
     *
     * @param string $message
     * @param string|null $conversationId
     * @return mixed
     */
    abstract public function message(string $message,string $conversationId=null):mixed ;

}
