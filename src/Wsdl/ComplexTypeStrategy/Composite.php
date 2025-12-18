<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Wsdl\ComplexTypeStrategy;

use Cline\Soap\Exception\InvalidArgumentException;
use Cline\Soap\Wsdl;

use function class_exists;
use function is_string;
use function sprintf;
use function throw_unless;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class Composite implements ComplexTypeStrategyInterface
{
    /**
     * Typemap of Complex Type => Strategy pairs.
     *
     * @var array<string, ComplexTypeStrategyInterface|string>
     */
    private array $typeMap = [];

    /**
     * Context WSDL file that this composite serves
     */
    private ?Wsdl $context = null;

    /**
     * Construct Composite WSDL Strategy.
     *
     * @param array<string, ComplexTypeStrategyInterface|string> $typeMap
     */
    public function __construct(
        array $typeMap = [],
        /**
         * Default Strategy of this composite
         */
        private ComplexTypeStrategyInterface|string $defaultStrategy = DefaultComplexType::class,
    ) {
        foreach ($typeMap as $type => $strategy) {
            $this->connectTypeToStrategy($type, $strategy);
        }
    }

    /**
     * Connect a complex type to a given strategy.
     *
     * @throws InvalidArgumentException
     */
    public function connectTypeToStrategy(string $type, ComplexTypeStrategyInterface|string $strategy): static
    {
        $this->typeMap[$type] = $strategy;

        return $this;
    }

    /**
     * Return default strategy of this composite
     *
     * @throws InvalidArgumentException
     */
    public function getDefaultStrategy(): ComplexTypeStrategyInterface
    {
        $strategy = $this->defaultStrategy;

        if (is_string($strategy) && class_exists($strategy)) {
            $strategy = new $strategy();
        }

        throw_unless($strategy instanceof ComplexTypeStrategyInterface, InvalidArgumentException::class, 'Default Strategy for Complex Types is not a valid strategy object.');

        $this->defaultStrategy = $strategy;

        return $strategy;
    }

    /**
     * Return specific strategy or the default strategy of this type.
     *
     * @throws InvalidArgumentException
     */
    public function getStrategyOfType(string $type): ComplexTypeStrategyInterface
    {
        if (isset($this->typeMap[$type])) {
            $strategy = $this->typeMap[$type];

            if (is_string($strategy) && class_exists($strategy)) {
                $strategy = new $strategy();
            }

            if (!$strategy instanceof ComplexTypeStrategyInterface) {
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
     */
    public function setContext(Wsdl $context): void
    {
        $this->context = $context;
    }

    /**
     * Create a complex type based on a strategy
     *
     * @throws InvalidArgumentException
     *
     * @return string XSD type
     */
    public function addComplexType(string $type): string
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
