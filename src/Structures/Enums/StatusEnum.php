<?php

namespace Garissman\LaraChain\Structures\Enums;


use Garissman\LaraChain\Structures\Traits\EnumHelperTrait;

enum StatusEnum: string
{
    use EnumHelperTrait;

    case Pending = 'pending';
    case Running = 'running';
    case SummaryBuilding = 'summary_building';
    case Complete = 'complete';
    case Cancelled = 'Cancelled';
    case Failed = 'failed';
    case SummaryComplete = 'summary_complete';
}
