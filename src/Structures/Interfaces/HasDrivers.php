<?php

namespace Garissman\LaraChain\Structures\Interfaces;


interface HasDrivers
{
    function getDriver(): string;
    function getEmbeddingDriver(): string;
}
