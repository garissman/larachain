<?php

namespace Garissman\Clerk\Intents;

use Garissman\Clerk\Structures\Intent;
use Illuminate\Support\Facades\Password;
use function Pest\Laravel\get;

class GettingEmailIntent extends Intent
{
    protected array $utterances=[
        'this is my email email@email.com'=>[
            'entities'=>[
                'email@email.com'=> [
                    'entity'=>'wit$email:email',
                    'name'=>'wit$email',
                    'roles'=>['email']
                ]
            ]
        ],
        'email@email.com'=>[
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
    protected array $entities=[
        'email'=> [
            'entity'=>'wit$email:email',
            'name'=>'wit$email',
            'roles'=>['email']
        ]
    ];

    public function response($prediction,$conversationId): string
    {
        $email=$this->getPredictedEntity('wit$email:email')->get('value');
        $state=$this->engine->getCurrentState($conversationId);
        if ($email) {
            $status = Password::sendResetLink(
                ["email"=>$email]
            );
            return 'Check your inbox';
        }
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
