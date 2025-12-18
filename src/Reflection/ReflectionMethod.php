<?php

namespace Cline\Soap\Reflection;

use ReflectionMethod as NativeReflectionMethod;

class ReflectionMethod extends AbstractFunction
{
    public function __construct(NativeReflectionMethod $r, string $namespace = '')
    {
        parent::__construct($r, $namespace);
    }
}
