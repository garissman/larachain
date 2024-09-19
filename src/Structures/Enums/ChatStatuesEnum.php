<?php

namespace Garissman\LaraChain\Structures\Enums;

enum ChatStatuesEnum: string
{
    case Complete = 'complete';
    case InProgress = 'in_progress';
    case NotStarted = 'not_started';
}
