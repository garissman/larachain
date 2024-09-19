<?php
namespace Garissman\Clerk\Structures;
use Garissman\Clerk\Engines\WitEngine;
use Illuminate\Support\Str;

abstract class Intent implements IntentInterface
{
    private string|null $intent=null;

    protected array $utterances=[];
    protected array $questions=[];

    protected array $statements=[];
    protected array $entities=[];
    protected array $predictedEntities=[];

    public function __construct(protected WitEngine|null $engine=null){
        $array=explode("\\",get_class($this));
        $this->intent=$array[count($array)-1];
    }
    public function getIntent(): string
    {
        return Str::snake($this->intent);
    }

    public function setIntent(string $intent): string
    {
        $this->intent=$intent;
        return $this->intent;
    }
    public function getUtterances(): \Illuminate\Support\Collection|array
    {
        return collect($this->utterances);
    }

    public function setUtterances(array $utterances): array
    {
        $this->utterances=$utterances;
        return $this->utterances;
    }
    public function getEntities(): array
    {
        return $this->entities;
    }

    public function setEntities(array $entities): array
    {
        $this->entities=$entities;
        return $this->entities;
    }
    public function getPredictedEntities(): array
    {
        return $this->predictedEntities;
    }

    public function setPredictedEntities(array $entities): array
    {
        $this->predictedEntities=$entities;
        return $this->predictedEntities;
    }
    public function getPredictedEntity(string $entity,mixed $default=null): Entity|null
    {
        if (isset($this->predictedEntities[$entity])) {
            $attributes=$this->predictedEntities[$entity][0];
            return new Entity($attributes);
        }else{
            return $default;
        }
    }
    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function setQuestions(array $questions): array
    {
        $this->questions = $questions;
        return $this->questions;
    }
    public function askQuestion(int|string|null $question=null): string
    {
        if (is_numeric($question) && isset($this->questions[$question]) ) {
            return $this->questions[$question];
        }elseif (is_string($question)) {
            return $question;
        }else {
            return $this->questions[rand(0, count($this->questions)-1)];
        }
    }

    public function sendStatement(int|string|null $statement=null): string
    {
        if (is_numeric($statement) && isset($this->statements[$statement]) ) {
            return $this->statements[$statement];
        }elseif (is_string($statement)) {
            return $statement;
        }else {
            return $this->statements[rand(0, count($this->statements)-1)];
        }
    }
    public function getEntity(string $entity): string
    {
        return $this->sendStatement();
    }
    public function response(mixed $prediction,string $conversationId): string
    {
        return $this->sendStatement();
    }
}
