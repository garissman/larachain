<?php

namespace Garissman\LaraChain\Structures\Interfaces;


use Garissman\LaraChain\Structures\Enums\DriversEnum;

interface HasDrivers
{
    function getDriver(): DriversEnum;
    function getEmbeddingDriver(): DriversEnum;
}
