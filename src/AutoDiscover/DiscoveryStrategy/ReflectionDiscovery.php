<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\AutoDiscover\DiscoveryStrategy;

use Cline\Soap\Reflection\AbstractFunction;
use Cline\Soap\Reflection\Prototype;
use Cline\Soap\Reflection\ReflectionParameter;

/**
 * Describes how types, return values and method details are detected during
 * AutoDiscovery of a WSDL.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ReflectionDiscovery implements DiscoveryStrategyInterface
{
    /**
     * Returns description from phpdoc block
     */
    public function getFunctionDocumentation(AbstractFunction $function): string
    {
        return $function->getDescription();
    }

    /**
     * Return parameter type
     */
    public function getFunctionParameterType(ReflectionParameter $param): string
    {
        return $param->getType();
    }

    /**
     * Return function return type
     */
    public function getFunctionReturnType(AbstractFunction $function, Prototype $prototype): string
    {
        return $prototype->getReturnType();
    }

    /**
     * Return true if function is one way (return nothing)
     */
    public function isFunctionOneWay(AbstractFunction $function, Prototype $prototype): bool
    {
        return $prototype->getReturnType() === 'void';
    }
}
