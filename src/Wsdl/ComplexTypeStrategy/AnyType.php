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
 * @author Brian Faust <brian@cline.sh>
 */
final class AnyType implements ComplexTypeStrategyInterface
{
    /**
     * Not needed in this strategy.
     */
    public function setContext(Wsdl $context): void {}

    /**
     * Returns xsd:anyType regardless of the input.
     */
    public function addComplexType(string $type): string
    {
        return Wsdl::XSD_NS.':anyType';
    }
}
