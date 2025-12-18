<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Reflection;

use ReflectionClass as NativeReflectionClass;
use ReflectionFunction as NativeReflectionFunction;
use ReflectionObject;

use function is_object;

final class Reflection
{
    /**
     * Perform class reflection to create dispatch signatures.
     *
     * @param object|string $class     Class name or object
     * @param string        $namespace Optional namespace prefix
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
     * @param string $function  Function name
     * @param string $namespace Optional namespace prefix
     */
    public function reflectFunction(string $function, string $namespace = ''): ReflectionFunction
    {
        return new ReflectionFunction(
            new NativeReflectionFunction($function),
            $namespace,
        );
    }
}
