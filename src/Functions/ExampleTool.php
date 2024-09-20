<?php

namespace Garissman\LaraChain\Functions;


use Garissman\LaraChain\Models\Message;
use Garissman\LaraChain\Structures\Classes\FunctionContract;
use Garissman\LaraChain\Structures\Classes\PropertyDto;
use Garissman\LaraChain\Structures\Classes\Responses\FunctionResponse;
use Garissman\LaraChain\Structures\Enums\ToolTypes;
use Garissman\LaraChain\Structures\Traits\ChatHelperTrait;
use Garissman\LaraChain\Structures\Traits\ToolsHelper;
use Illuminate\Support\Facades\Log;

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
    protected string $description = 'Trigger this intent if user ask a his email.';

    public function handle(Message $message, $arguments = []): FunctionResponse
    {
        $args = $message->args;

        $email = data_get($args, 'email', null);

        // Do SomeThing
        $message->body = 'Here is your Email '.$email;
        $message->save();

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
                name: 'email',
                description: 'Email of the user',
                type: 'string',
                required: true,
            ),
        ];
    }
}
