<?php

namespace Cline\Soap\Reflection;

use ReflectionClass as NativeReflectionClass;
use ReflectionFunction as NativeReflectionFunction;
use ReflectionObject;

class Reflection
{
    /**
     * Perform class reflection to create dispatch signatures.
     *
     * @param string|object $class Class name or object
     * @param string $namespace Optional namespace prefix
     * @return ReflectionClass
     */
    public function reflectClass(string|object $class, string $namespace = ''): ReflectionClass
    {
        if (is_object($class)) {
            $reflection = new ReflectionObject($class);
        } else {
            $reflection = new NativeReflectionClass($class);
        }

        return new ReflectionClass($reflection, $namespace);
    }

    /**
     * Perform function reflection to create dispatch signatures.
     *
     * @param string $function Function name
     * @param string $namespace Optional namespace prefix
     * @return ReflectionFunction
     */
    public function reflectFunction(string $function, string $namespace = ''): ReflectionFunction
    {
        return new ReflectionFunction(new NativeReflectionFunction($function), $namespace);
    }
}
