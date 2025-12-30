<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\WsExtensions\ReliableMessaging;

use Cline\Soap\Exception\InvalidArgumentException;

/**
 * WS-ReliableMessaging Delivery Assurance
 *
 * Defines delivery guarantee semantics for reliable messaging sequences.
 * Multiple assurances can be combined (e.g., ExactlyOnce + InOrder).
 *
 * @see http://docs.oasis-open.org/ws-rx/wsrm/200702
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class DeliveryAssurance
{
    /**
     * Messages are delivered at least once (may be duplicated)
     */
    public const string AT_LEAST_ONCE = 'AtLeastOnce';

    /**
     * Messages are delivered at most once (no duplicates, but may be lost)
     */
    public const string AT_MOST_ONCE = 'AtMostOnce';

    /**
     * Messages are delivered exactly once (no duplicates, no loss)
     */
    public const string EXACTLY_ONCE = 'ExactlyOnce';

    /**
     * Messages are delivered in order
     */
    public const string IN_ORDER = 'InOrder';

    /**
     * Available delivery assurance options
     */
    private const array VALID_ASSURANCES = [
        self::AT_LEAST_ONCE,
        self::AT_MOST_ONCE,
        self::EXACTLY_ONCE,
        self::IN_ORDER,
    ];

    /**
     * Active delivery assurances
     *
     * @var array<string>
     */
    private array $assurances = [];

    /**
     * Create a new DeliveryAssurance instance
     *
     * @param array<string> $assurances Initial assurance options
     */
    public function __construct(array $assurances = [])
    {
        foreach ($assurances as $assurance) {
            $this->addAssurance($assurance);
        }
    }

    /**
     * Create an AtLeastOnce delivery assurance
     */
    public static function atLeastOnce(): self
    {
        return new self([self::AT_LEAST_ONCE]);
    }

    /**
     * Create an AtMostOnce delivery assurance
     */
    public static function atMostOnce(): self
    {
        return new self([self::AT_MOST_ONCE]);
    }

    /**
     * Create an ExactlyOnce delivery assurance
     */
    public static function exactlyOnce(): self
    {
        return new self([self::EXACTLY_ONCE]);
    }

    /**
     * Create an InOrder delivery assurance
     */
    public static function inOrder(): self
    {
        return new self([self::IN_ORDER]);
    }

    /**
     * Create an ExactlyOnce with InOrder delivery assurance
     */
    public static function exactlyOnceInOrder(): self
    {
        return new self([self::EXACTLY_ONCE, self::IN_ORDER]);
    }

    /**
     * Add a delivery assurance option
     *
     * @param string $assurance Assurance option (use class constants)
     * @throws InvalidArgumentException If assurance is invalid
     */
    public function addAssurance(string $assurance): self
    {
        if (!in_array($assurance, self::VALID_ASSURANCES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid delivery assurance "%s". Valid options are: %s',
                $assurance,
                implode(', ', self::VALID_ASSURANCES),
            ));
        }

        if (!in_array($assurance, $this->assurances, true)) {
            $this->assurances[] = $assurance;
        }

        return $this;
    }

    /**
     * Check if an assurance option is enabled
     */
    public function hasAssurance(string $assurance): bool
    {
        return in_array($assurance, $this->assurances, true);
    }

    /**
     * Get all enabled assurances
     *
     * @return array<string>
     */
    public function getAssurances(): array
    {
        return $this->assurances;
    }

    /**
     * Convert to policy configuration array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $config = [
            'namespace' => RMAssertion::WSRMP_NS_URI,
            'assertion' => 'DeliveryAssurance',
            'assurances' => [],
        ];

        foreach ($this->assurances as $assurance) {
            $config['assurances'][] = [
                'namespace' => RMAssertion::WSRMP_NS_URI,
                'assertion' => $assurance,
            ];
        }

        return $config;
    }
}
