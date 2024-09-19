<?php

namespace Garissman\Clerk\Structures;

use Garissman\Clerk\Engines\Engine;
use Garissman\Clerk\Engines\WitEngine;
use Illuminate\Support\Collection;

class Prediction
{
    public Collection $intents;

    public function __construct(public $prediction,public Engine $engine)
    {
        $intents = config('clerk.intents');
        $this->intents = collect();
        if (count($prediction['intents'])>0) {
            foreach ($intents as $intent) {
                $intent=new $intent($engine);
                foreach ($prediction['intents'] as $predictionIntent) {
                    if ($intent->getIntent()===$predictionIntent['name']) {
                        $intent->setPredictedEntities($prediction['entities']);
                        $this->intents->add($intent);
                    }
                }
            }
        }
    }
     public function response($conversationId){
         foreach ($this->intents as $intent) {
             return $intent->response($this,$conversationId);
         }
     }
}
