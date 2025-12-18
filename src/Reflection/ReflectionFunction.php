<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Reflection;

use ReflectionFunction as NativeReflectionFunction;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class ReflectionFunction extends AbstractFunction
{
    public function __construct(NativeReflectionFunction $r, string $namespace = '')
    {
        parent::__construct($r, $namespace);
    }
}
