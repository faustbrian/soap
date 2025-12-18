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
interface DiscoveryStrategyInterface
{
    /**
     * Get the function parameters php type.
     *
     * Default implementation assumes the default param doc-block tag.
     */
    public function getFunctionParameterType(ReflectionParameter $param): string;

    /**
     * Get the functions return php type.
     *
     * Default implementation assumes the value of the return doc-block tag.
     */
    public function getFunctionReturnType(AbstractFunction $function, Prototype $prototype): string;

    /**
     * Detect if the function is a one-way or two-way operation.
     *
     * Default implementation assumes one-way, when return value is "void".
     */
    public function isFunctionOneWay(AbstractFunction $function, Prototype $prototype): bool;

    /**
     * Detect the functions documentation.
     *
     * Default implementation uses docblock description.
     */
    public function getFunctionDocumentation(AbstractFunction $function): string;
}
