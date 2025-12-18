<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Wsdl\ComplexTypeStrategy;

use Cline\Soap\Exception;
use Cline\Soap\Exception\InvalidArgumentException;
use Cline\Soap\Wsdl;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ComplexTypeStrategyInterface as ComplexTypeStrategy;
use Cline\Soap\Wsdl\ComplexTypeStrategy\DefaultComplexType;

use function class_exists;
use function is_string;
use function sprintf;

final class Composite implements ComplexTypeStrategy
{
    /**
     * Typemap of Complex Type => Strategy pairs.
     *
     * @var array
     */
    protected $typeMap = [];

    /**
     * Default Strategy of this composite
     *
     * @var ComplexTypeStrategy|string
     */
    protected $defaultStrategy;

    /**
     * Context WSDL file that this composite serves
     *
     * @var null|Wsdl
     */
    protected $context;

    /**
     * Construct Composite WSDL Strategy.
     *
     * @param ComplexTypeStrategy|string $defaultStrategy
     */
    public function __construct(
        array $typeMap = [],
        $defaultStrategy = DefaultComplexType::class,
    ) {
        foreach ($typeMap as $type => $strategy) {
            $this->connectTypeToStrategy($type, $strategy);
        }

        $this->defaultStrategy = $defaultStrategy;
    }

    /**
     * Connect a complex type to a given strategy.
     *
     * @param  string                     $type
     * @param  ComplexTypeStrategy|string $strategy
     * @throws InvalidArgumentException
     * @return self
     */
    public function connectTypeToStrategy($type, $strategy)
    {
        if (!is_string($type)) {
            throw new InvalidArgumentException('Invalid type given to Composite Type Map.');
        }
        $this->typeMap[$type] = $strategy;

        return $this;
    }

    /**
     * Return default strategy of this composite
     *
     * @throws InvalidArgumentException
     * @return ComplexTypeStrategy
     */
    public function getDefaultStrategy()
    {
        $strategy = $this->defaultStrategy;

        if (is_string($strategy) && class_exists($strategy)) {
            $strategy = new $strategy();
        }

        if (!$strategy instanceof ComplexTypeStrategy) {
            throw new InvalidArgumentException(
                'Default Strategy for Complex Types is not a valid strategy object.',
            );
        }
        $this->defaultStrategy = $strategy;

        return $strategy;
    }

    /**
     * Return specific strategy or the default strategy of this type.
     *
     * @param  string                   $type
     * @throws InvalidArgumentException
     * @return ComplexTypeStrategy
     */
    public function getStrategyOfType($type)
    {
        if (isset($this->typeMap[$type])) {
            $strategy = $this->typeMap[$type];

            if (is_string($strategy) && class_exists($strategy)) {
                $strategy = new $strategy();
            }

            if (!$strategy instanceof ComplexTypeStrategy) {
                throw new InvalidArgumentException(sprintf(
                    'Strategy for Complex Type "%s" is not a valid strategy object.',
                    $type,
                ));
            }
            $this->typeMap[$type] = $strategy;
        } else {
            $strategy = $this->getDefaultStrategy();
        }

        return $strategy;
    }

    /**
     * Method accepts the current WSDL context file.
     *
     * @return self
     */
    public function setContext(Wsdl $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Create a complex type based on a strategy
     *
     * @param  string                   $type
     * @throws InvalidArgumentException
     * @return string                   XSD type
     */
    public function addComplexType($type)
    {
        if (!$this->context instanceof Wsdl) {
            throw new InvalidArgumentException(sprintf(
                'Cannot add complex type "%s", no context is set for this composite strategy.',
                $type,
            ));
        }

        $strategy = $this->getStrategyOfType($type);
        $strategy->setContext($this->context);

        return $strategy->addComplexType($type);
    }
}
