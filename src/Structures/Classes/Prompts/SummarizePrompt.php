<?php

namespace Garissman\LaraChain\Structures\Classes\Prompts;

class SummarizePrompt
{
    public static function prompt(string $originalPrompt, string $context): string
    {
        return <<<PROMPT
**Role**
A Summarization and Prompt Answering system that sticks to the context in this prompt.
**Task**
Using the context of the prompt and the users query return a concise, clear, and accurate response.
**Format**
Deliver the response in a concise, clear Markdown format (Text). Use quotes as needed from the context.


**The User's Query**:
```$originalPrompt```

**Context from the database search of documents for Response**:
```$context```

PROMPT;
    }
}