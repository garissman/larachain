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
        Message $toolMessage,
        Message $assistanceMessage,
                $arguments = []
    ): FunctionResponse
    {
        $args = $toolMessage->args;

        $name = data_get($args, 'name', '');

        // Do SomeThing
        $assistanceMessage->role=RoleEnum::Tool;
        // Do some RAG with this message
        $assistanceMessage->body = 'Let me take a look';
        $assistanceMessage->is_been_whisper = false;
        $assistanceMessage->is_chat_ignored=true;
        $assistanceMessage->tool_name = $toolMessage->tool_name;
        $assistanceMessage->tool_id= $toolMessage->tool_id;
        $assistanceMessage->args= $toolMessage->args;
        $assistanceMessage->save();

        $toolMessage->body = 'Here is your Email ' . $name;
        $toolMessage->save();
        $assistanceMessage->save();

        return FunctionResponse::from([
            'content' => $toolMessage->body,
            'prompt' => $toolMessage->body,
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
