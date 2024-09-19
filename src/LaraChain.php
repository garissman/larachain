<?php

namespace Garissman\LaraChain;

use Illuminate\Contracts\Container\Container;


class LaraChain
{


    public function __construct(private Container $container)
    {

    }

}
