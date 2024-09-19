<?php

namespace Garissman\LaraChain\Structures\Enums;

enum RoleEnum: string
{
    case User = 'user';
    case System = 'system';
    case Assistant = 'assistant';
    case Tool = 'tool';
}
