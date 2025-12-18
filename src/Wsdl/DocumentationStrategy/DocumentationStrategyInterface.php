<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Wsdl\DocumentationStrategy;

use ReflectionClass;
use ReflectionProperty;

/**
 * Implement this interface to provide contents for <xsd:documentation> elements on complex types
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface DocumentationStrategyInterface
{
    /**
     * Returns documentation for complex type property
     */
    public function getPropertyDocumentation(ReflectionProperty $property): string;

    /**
     * Returns documentation for complex type
     */
    public function getComplexTypeDocumentation(ReflectionClass $class): string;
}
