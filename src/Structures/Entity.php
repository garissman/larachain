<?php

namespace Garissman\Clerk\Structures;

use AllowDynamicProperties;

#[AllowDynamicProperties] class Entity
{

    public function __construct(...$attributes){
        $this->attributes = $attributes[0];
    }

    public function get($name)
    {
        return $this->attributes[$name];
    }


}
