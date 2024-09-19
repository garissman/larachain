<?php

namespace Garissman\Clerk\Engines;


use Garissman\Clerk\Engines\WitTraits\UtterancePayload;
use Garissman\Clerk\Intents\UnknownIntent;
use Garissman\Clerk\Structures\Intent;
use Garissman\Clerk\Structures\Prediction;
use Garissman\Wit\Wit;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WitEngine extends Engine
{
    use UtterancePayload;

    /**
     * Create a new engine instance.
     *
     * @param Wit $wit
     */
    public function __construct(public Wit $wit)
    {
    }

    /**
     * Train Model from Clerk Intent.
     *
     * @param Intent $intent
     * @return mixed
     */
    public function train(Intent $intent): mixed
    {
        try {
            $this->wit->intent()->get($intent->getIntent());
        }catch (ClientException $exception){
            $content=json_decode($exception->getResponse()->getBody()->getContents(),true);
            if ($content['code']=='not-found') {
                $this->wit->intent()->create(['name'=>$intent->getIntent()]);
            }
        }
        if ($intent->getUtterances()->count()) {
            return  $this->wit->utterance()->create($this->toUtterancePayload($intent));
        }else{
            return true;
        }
    }

    public function startConversation(string|null $conversationId=null, Intent $intent=null): string
    {
        if (is_null($conversationId)) {
            $conversationId=Str::ulid()->toString();
        }
        if (!$intent) {
            $intent=new UnknownIntent();
        }
        $this->setCurrentIntent($conversationId,$intent);
        $this->setCurrentState($conversationId);
        return $conversationId;
    }

    public function getCurrentIntent($conversation_id){
        return Cache::remember(
            'clerk:intent:'.$conversation_id,
            now()->add(
                config('clerk.conversation.ttl.unit'),
                config('clerk.conversation.ttl.value')
            ),
            function () {
            return UnknownIntent::class;
        });
    }
    public function setCurrentIntent($conversation_id,Intent $intent){
        return Cache::put(
            'clerk:intent:'.$conversation_id,
            get_class($intent),
            now()->add(
                config('clerk.conversation.ttl.unit'),
                config('clerk.conversation.ttl.value')
            ));
    }
    public function getCurrentState($conversation_id){
        return Cache::remember(
            'clerk:state:'.$conversation_id,
            now()->add(
                config('clerk.conversation.ttl.unit'),
                config('clerk.conversation.ttl.value')
            ),
            function () {
                return 'CONTINUE';
            });
    }
    public function setCurrentState($conversation_id,$state='CONTINUE'){
        return Cache::put(
            'clerk:state:'.$conversation_id,
            $state,
            now()->add(
                config('clerk.conversation.ttl.unit'),
                config('clerk.conversation.ttl.value')
            ));
    }

    public function message(string $message,string|null $conversationId=null): array
    {
        if (!$conversationId) {
            $conversationId=$this->startConversation($conversationId);
        }
        if ($message!='') {
            $response=$this->processMessage(new Prediction($this->wit->message()->getIntent($message),$this),$conversationId);
        }else{
            $response=(new UnknownIntent())->sendStatement();;
        }
        return ['message'=>$response,'conversationId'=>$conversationId];
    }

    /**
     * Will hande over the predicted intent from the model
     *
     * @param Prediction $prediction
     * @param string $conversationId
     * @return string
     */
    private function processMessage(Prediction $prediction, string $conversationId): string
    {
        return $prediction->response($conversationId);
    }
}
