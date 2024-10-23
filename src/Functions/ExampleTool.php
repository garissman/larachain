<?php

namespace Garissman\LaraChain\Functions;


use Garissman\LaraChain\Models\Message;
use Garissman\LaraChain\Structures\Classes\FunctionContract;
use Garissman\LaraChain\Structures\Classes\PropertyDto;
use Garissman\LaraChain\Structures\Classes\Responses\FunctionResponse;
use Garissman\LaraChain\Structures\Enums\RoleEnum;
use Garissman\LaraChain\Structures\Enums\ToolTypes;
use Garissman\LaraChain\Structures\Traits\ChatHelperTrait;
use Garissman\LaraChain\Structures\Traits\ToolsHelper;

class ExampleTool extends FunctionContract
{
    use ChatHelperTrait, ToolsHelper;

    public string $name = 'example_tool';
    public bool $showInUi = true;
    public array $toolTypes = [
        ToolTypes::Chat,
        ToolTypes::ChatCompletion,
        ToolTypes::ManualChoice,
        ToolTypes::Source,
        ToolTypes::Output,
    ];
    protected string $description = 'Trigger this intent if user ask to user the example tool.';

    public function handle(
        Message $message
    ): FunctionResponse
    {
        $args = $message->args;
        $name = data_get($args, 'name', null);
        if (!$name) {
            $message->body="Ask for name";
            $message->save();
            return FunctionResponse::from([
                'content' => $message->body,
                'prompt' => $message->body,
                'requires_followup' => false,
                'documentChunks' => collect([]),
                'save_to_message' => false,
            ]);
        }
       // Do your magic

        return FunctionResponse::from([
            'content' => $message->body,
            'prompt' => $message->body,
            'requires_followup' => false,
            'documentChunks' => collect([]),
            'save_to_message' => false,
        ]);
    }

    /**
     * @return PropertyDto[]
     */
    protected function getProperties(): array
    {
        return [
            new PropertyDto(
                name: 'name',
                description: 'User Name',
                type: 'string',
                required: true,
            ),
        ];
    }
}
