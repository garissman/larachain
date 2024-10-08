<?php

namespace Garissman\LaraChain\Functions;


use Garissman\LaraChain\Facades\LaraChain;
use Garissman\LaraChain\Models\DocumentChunk;
use Garissman\LaraChain\Models\Message;
use Garissman\LaraChain\Structures\Classes\DistanceQuery\DistanceQueryFacade;
use Garissman\LaraChain\Structures\Classes\FunctionContract;
use Garissman\LaraChain\Structures\Classes\Prompts\SummarizePrompt;
use Garissman\LaraChain\Structures\Classes\PropertyDto;
use Garissman\LaraChain\Structures\Classes\Responses\FunctionResponse;
use Garissman\LaraChain\Structures\Enums\ToolTypes;
use Garissman\LaraChain\Structures\Traits\ChatHelperTrait;
use Garissman\LaraChain\Structures\Traits\ToolsHelper;

class LookIntoDocuments extends FunctionContract
{
    use ChatHelperTrait, ToolsHelper;

    public string $name = 'look_into_documents';
    public bool $showInUi = true;
    public array $toolTypes = [
        ToolTypes::Chat,
        ToolTypes::ChatCompletion,
        ToolTypes::ManualChoice,
        ToolTypes::Source,
        ToolTypes::Output,
    ];
    protected string $description = 'Trigger this intent if user has problems with connections or need some guidance.';

    public function handle(
        Message $toolMessage,
        Message $assistanceMessage,
                $arguments = []
    ): FunctionResponse
    {
        $args = $toolMessage->args;
        $embedding = LaraChain::engine($toolMessage->getEmbeddingDriver())->embedData($args['context']);
        $embeddingSize = LaraChain::engine($toolMessage->getEmbeddingDriver())->getEmbeddingSize();

        $documentChunkResults = DistanceQueryFacade::cosineDistance(
            $embedding->embedding,
            $embeddingSize
        );
        $content = [];

        /** @var DocumentChunk $result */
        foreach ($documentChunkResults as $result) {
            $contentString = LaraChain::removeAscii($result->content);
            $content[] = $contentString; //reduce_text_size seem to mess up Claude?
        }
        $context = implode(' ', $content);
        $contentFlattened = SummarizePrompt::prompt(
            originalPrompt: $args['context'],
            context: $context
        );
        $response = LaraChain::engine($toolMessage->getDriver())
            ->completion($contentFlattened);
        $toolMessage->body = $response->content;
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
                name: 'context',
                description: 'The user specific question or guidance',
                type: 'string',
                required: true,
            )
        ];
    }
}
