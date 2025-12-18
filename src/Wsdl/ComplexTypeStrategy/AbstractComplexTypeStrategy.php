<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Wsdl\ComplexTypeStrategy;

use Cline\Soap\Wsdl;
use Cline\Soap\Wsdl\DocumentationStrategy\DocumentationStrategyInterface;

use function array_key_exists;

/**
 * Abstract class for Cline\Soap\Wsdl\Strategy.
 */
abstract class AbstractComplexTypeStrategy implements ComplexTypeStrategyInterface
{
    /**
     * Context object
     *
     * @var Wsdl
     */
    protected $context;

    /** @var DocumentationStrategyInterface */
    protected $documentationStrategy;

    /**
     * Set the WSDL Context object this strategy resides in.
     */
    public function setContext(Wsdl $context): void
    {
        $this->context = $context;
    }

    /**
     * Return the current WSDL context object
     *
     * @return Wsdl
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Look through registered types
     *
     * @param  string      $phpType
     * @return null|string
     */
    public function scanRegisteredTypes($phpType)
    {
        if (array_key_exists($phpType, $this->getContext()->getTypes())) {
            $soapTypes = $this->getContext()->getTypes();

            return $soapTypes[$phpType];
        }

        return null;
    }

    /**
     * Sets the strategy for generating complex type documentation
     */
    public function setDocumentationStrategy(DocumentationStrategyInterface $documentationStrategy): void
    {
        $this->documentationStrategy = $documentationStrategy;
    }
}
