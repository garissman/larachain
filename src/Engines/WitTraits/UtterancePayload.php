<?php

namespace Garissman\Clerk\Engines\WitTraits;

use Garissman\Clerk\Structures\Intent;
use GuzzleHttp\Exception\ClientException;

trait UtterancePayload
{
    /**
     * Create the Utterances Payload
     * @param Intent $intent
     * @return array
     */
    private function toUtterancePayload($intent): array
    {
        $data=[];
        foreach ($intent->getUtterances() as $utterance=>$utteranceData) {
            if (!is_array($utteranceData)) {
                $utterance=$utteranceData;
            }
            $item=[
                "text"=> $utterance,
                "intent"=> $intent->getIntent(),
                "entities"=>[],
                "traits"=>[]
            ];
            if (is_array($utteranceData)) {
                if (isset($utteranceData['entities'])) {
                    $item['entities']=$this->getUtteranceEntityPayload($utterance,$utteranceData['entities']);
                }
                if (isset($utteranceData['traits'])) {
                    $item['traits']=$this->getUtteranceTraitPayload($utteranceData['traits']);
                }
            }

            $data[]=$item;
        }
        return $data;
    }
    /**
     * Create the Utterances Entity Payload
     * @param string $utterance
     * @param array $entities
     * @return array
     */
    private function getUtteranceEntityPayload(string $utterance, array $entities): array
    {
        $entitiesPayload=[];
        foreach ($entities as $body=>$entity) {
            try {
                $this->wit->entity()->get($entity['name']);
            }catch (ClientException $exception){
                $content=json_decode($exception->getResponse()->getBody()->getContents(),true);
                if ($content['code']=='not-found') {
                    $this->wit->entity()->create($entity['name'],$entity['roles']);
                }
            }
            $entityLength=strlen($body);
            $start=strpos($utterance,$body);
            $end=$start+$entityLength-1;
            $item=[
                'start'=>$start,
                'end'=>$end,
            ];
            $item['entity']=$entity['entity'];
            $item['body']=substr($utterance,$item['start'],$entityLength);
            $item['entities']=[];
            if (isset($entity['entities'])) {
                $item['entities']=$this->getUtteranceEntityPayload($utterance,$entity['entities']);
            }
            $entitiesPayload[]=$item;
        }
        return $entitiesPayload;
    }

    /**
     * Create the Utterances Trait Payload
     * @param $traits
     * @return array
     */
    private function getUtteranceTraitPayload( $traits): array
    {
        $traitsPayload=[];
        foreach ($traits as $trait) {
            $item=[
                'trait'=>$trait['trait'],
                'value'=>$trait['value'],
            ];
            $traitsPayload[]=$item;
        }
        return $traitsPayload;
    }

}
