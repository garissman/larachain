<?php
namespace Garissman\Clerk\Structures;

use Illuminate\Support\Collection;

interface IntentInterface
{
    /**
     * return Intent key
     * @return string
     */
    public function getIntent() :string;

    /**
     * Set Intent key
     * @param string $intent
     * @return string
     */
    public function setIntent(string $intent): string;

    /**
     * Get the Utterances of the Intent
     * @return array<string>
     */
    public function getUtterances() :\Illuminate\Support\Collection|array;

    /**
     * Set the Utterances of the Intent
     * @param array<string> $utterances
     * @return array<string>
     */
    public function setUtterances(array $utterances): \Illuminate\Support\Collection|array;


    /**
     * Get the Entities of the Intent
     * @return array<string>
     */
    public function getEntities() :\Illuminate\Support\Collection|array;

    /**
     * Set the Entities of the Intent
     * @param array<string> $entities
     * @return array<string>
     */
    public function setEntities(array $entities): \Illuminate\Support\Collection|array;

    /**
     *
     * @return Collection|array
     */
    public function getQuestions() :\Illuminate\Support\Collection|array;

    /**
     * @param array<string> $questions
     * @return Collection|array
     */
    public function setQuestions(array $questions): \Illuminate\Support\Collection|array;

    /**
     * Will ask a question from Question Array
     * @param int|string|null $question
     * @return string
     */
    public function askQuestion(int|string|null $question=null): string;

    /**
     * Will send a statement from Intent Statements Array
     * @param int|string|null $statement
     * @return string
     */
    public function sendStatement(int|string|null $statement=null): string;

    /**
     * Will send a response base on the model prediction
     * @param mixed $prediction
     * @param string $conversationId
     * @return string
     */
    public function response(mixed $prediction,string $conversationId): string;

}
