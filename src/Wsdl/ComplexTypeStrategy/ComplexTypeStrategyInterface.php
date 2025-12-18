<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Wsdl\ComplexTypeStrategy;

use Cline\Soap\Wsdl;

/**
 * Interface strategies that generate an XSD-Schema for complex data types in WSDL files.
 */
interface ComplexTypeStrategyInterface
{
    /**
     * Method accepts the current WSDL context file.
     */
    public function setContext(Wsdl $context);

    /**
     * Create a complex type based on a strategy
     *
     * @param  string $type
     * @return string XSD type
     */
    public function addComplexType($type);
}
