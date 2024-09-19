<?php

namespace Garissman\Clerk\Intents;

use Garissman\Clerk\Structures\Intent;

class ResetPasswordIntent extends Intent
{
    // TODO Made Utterances save them in DB same for Entities, link them automatically base on Entity name
    protected array $utterances=[
        'I want to reset my password',
        'I forgot my password',
        'I do not know my password',
        'I want to reset my password, this is my email email@email.com'=>[
            'entities'=>[
                'email@email.com'=> [
                    'entity'=>'wit$email:email',
                    'name'=>'wit$email',
                    'roles'=>['email']
                ]
            ]
        ],
    ];

    protected array $questions=[
        'What is your email address?',
    ];

    protected array $statements=[
        'I need to know your email address',
    ];

    public function response($prediction,$conversationId): string
    {
        $state=$this->engine->getCurrentState($conversationId);
        if ($state==="CONTINUE") {
            $this->engine->setCurrentState($conversationId,"ASKING_FOR_EMAIL");
            return $this->askQuestion();
        }elseif ($state==="ASKING_FOR_EMAIL") {
            $this->engine->setCurrentState($conversationId,"ASKING_FOR_EMAIL");
            return $this->askQuestion();
        }
        return $this->sendStatement();
    }
}
