<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Reflection;

use ReflectionMethod as NativeReflectionMethod;

final class ReflectionMethod extends AbstractFunction
{
    public function __construct(NativeReflectionMethod $r, string $namespace = '')
    {
        parent::__construct($r, $namespace);
    }
}
