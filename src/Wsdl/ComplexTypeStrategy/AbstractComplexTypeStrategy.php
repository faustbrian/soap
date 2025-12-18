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
use RuntimeException;

use function array_key_exists;
use function throw_if;

/**
 * Abstract class for Cline\Soap\Wsdl\Strategy.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class AbstractComplexTypeStrategy implements ComplexTypeStrategyInterface
{
    /**
     * Context object
     */
    protected ?Wsdl $context = null;

    protected ?DocumentationStrategyInterface $documentationStrategy = null;

    /**
     * Set the WSDL Context object this strategy resides in.
     */
    public function setContext(Wsdl $context): void
    {
        $this->context = $context;
    }

    /**
     * Return the current WSDL context object
     */
    public function getContext(): ?Wsdl
    {
        return $this->context;
    }

    /**
     * Look through registered types
     */
    public function scanRegisteredTypes(string $phpType): ?string
    {
        $context = $this->requireContext();

        if (array_key_exists($phpType, $context->getTypes())) {
            return $context->getTypes()[$phpType];
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

    /**
     * Get context or throw if not set.
     *
     * @throws RuntimeException When context is not set
     */
    protected function requireContext(): Wsdl
    {
        throw_if(!$this->context instanceof Wsdl, RuntimeException::class, 'WSDL context must be set before this operation');

        return $this->context;
    }
}
