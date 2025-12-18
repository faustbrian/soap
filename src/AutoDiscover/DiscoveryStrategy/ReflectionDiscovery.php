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
 */
final class ReflectionDiscovery implements DiscoveryStrategyInterface
{
    /**
     * Returns description from phpdoc block
     *
     * @return string
     */
    public function getFunctionDocumentation(AbstractFunction $function)
    {
        return $function->getDescription();
    }

    /**
     * Return parameter type
     *
     * @return string
     */
    public function getFunctionParameterType(ReflectionParameter $param)
    {
        return $param->getType();
    }

    /**
     * Return function return type
     *
     * @return string
     */
    public function getFunctionReturnType(AbstractFunction $function, Prototype $prototype)
    {
        return $prototype->getReturnType();
    }

    /**
     * Return true if function is one way (return nothing)
     *
     * @return bool
     */
    public function isFunctionOneWay(AbstractFunction $function, Prototype $prototype)
    {
        return $prototype->getReturnType() === 'void';
    }
}
