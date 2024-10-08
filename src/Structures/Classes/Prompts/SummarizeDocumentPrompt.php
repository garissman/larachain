<?php

namespace Garissman\LaraChain\Structures\Classes\Prompts;

class SummarizeDocumentPrompt
{
    public static function prompt(string $documentContent): string
    {
        return <<<PROMPT
**Role**
You are an assistant in a RAG (Retrieval augmented generation system) this prompt is used for Documents and to summarize
their content so the user can see a quick summary in the UI

**Task**
Take the context below and write a summary of the content in markdown format. Make sure it is small enought to fit in
the UI about 3 paragraphs or less. Use Bullets points if needed.

**Format**
Title:
Summary:

**Content**
$documentContent

PROMPT;
    }
}
