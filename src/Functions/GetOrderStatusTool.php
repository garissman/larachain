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
use Illuminate\Support\Facades\Log;

class GetOrderStatusTool extends FunctionContract
{
    use ChatHelperTrait, ToolsHelper;

    public string $name = 'get_order_status';
    public bool $showInUi = true;
    public array $toolTypes = [
        ToolTypes::Chat,
        ToolTypes::ChatCompletion,
        ToolTypes::ManualChoice,
        ToolTypes::Source,
        ToolTypes::Output,
    ];
    protected string $description = 'Trigger this intent if user ask to check his order status, and ask for email and order number if you do not have it.';

    public function handle(
        Message $toolMessage,
        Message $assistanceMessage,
                $arguments = []
    ): FunctionResponse
    {
        Log::info('[LaraChain] GetOrderStatusTool Function called');

        $args = $toolMessage->args;

        $email = data_get($args, 'email', null);
        $orderNumber = data_get($args, 'order_number', null);

        Log::info('[LaraChain] GetOrderStatusTool called', [
            'email' => $email,
            'order_number' => $orderNumber,
        ]);
        $assistanceMessage->role=RoleEnum::Tool;
        $assistanceMessage->body = 'Let me take a look';
        $assistanceMessage->is_been_whisper = false;
        $assistanceMessage->tool_name = $toolMessage->tool_name;
        $assistanceMessage->tool_id= $toolMessage->tool_id;
        $assistanceMessage->args= $toolMessage->args;
        $assistanceMessage->save();
//        $assistanceMessage->delete();

        // Do Some Query
        $toolMessage->body = 'Order Status is Completed';
        $toolMessage->save();

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
                name: 'email',
                description: 'Email of the user that use to make the purchase',
                type: 'string',
                required: true,
            ),
            new PropertyDto(
                name: 'order_number',
                description: 'Order Number of the purchase',
                type: 'string',
                required: true,
            ),
        ];
    }
}
