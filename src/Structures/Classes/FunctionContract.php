<?php

namespace Garissman\LaraChain\Structures\Classes;

use Garissman\LaraChain\Models\Message;
use Garissman\LaraChain\Structures\Classes\Responses\FunctionResponse;
use Garissman\LaraChain\Structures\Enums\ToolTypes;
use Illuminate\Bus\Batch;

abstract class FunctionContract
{
    protected string $name;

    public bool $showInUi = true;

    public array $toolTypes = [
        ToolTypes::Chat,
        ToolTypes::ChatCompletion,
        ToolTypes::Source,
        ToolTypes::Output,
        ToolTypes::NoFunction,
    ];

    protected string $description;

    protected string $type = 'object';

    public Batch $batch;

    public function setBatch(Batch $batch): self
    {
        $this->batch = $batch;

        return $this;
    }
    public function getDescription(): string
    {
        return $this->description;
    }

    abstract public function handle(
        Message $toolMessage,
        Message $assistanceMessage,
        $arguments = [],
    ): FunctionResponse;

    public function getFunction(): FunctionDto|array
    {
        return FunctionDto::from(
            [
                'name' => $this->getName(),
                'description' => $this->getDescription(),
                'parameters' => [
                    'type' => $this->type,
                    'properties' => $this->getProperties(),
                ],
            ]
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getKey(): string
    {
        return $this->name;
    }

    public function runAsBatch(): bool
    {
        return false;
    }

    public function getParameters(): array
    {
        return $this->getProperties();
    }

    /**
     * @return PropertyDto[]
     */
    abstract protected function getProperties(): array;
}
