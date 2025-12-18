<?php

namespace Cline\Soap\Reflection;

use ReflectionFunction as NativeReflectionFunction;

class ReflectionFunction extends AbstractFunction
{
    public function __construct(NativeReflectionFunction $r, string $namespace = '')
    {
        parent::__construct($r, $namespace);
    }
}
