<?php

namespace Garissman\LaraChain\Structures\Enums;

enum DriversEnum: string
{
    case Mock = 'mock';
    case OpenAi = 'openai';
    case OpenAiAzure = 'openai_azure';
    case Ollama = 'ollama';
    case Gemini = 'gemini';
    case Claude = 'claude';
    case Groq = 'groq';
    case AnythingLln = 'anything_llm';
}
