<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\WsExtensions\ReliableMessaging;

/**
 * WS-ReliableMessaging Policy Factory
 *
 * Provides convenient factory methods for creating reliable messaging
 * policy configurations compatible with the WS-Policy infrastructure.
 *
 * @see http://docs.oasis-open.org/ws-rx/wsrm/200702
 * @see http://docs.oasis-open.org/ws-rx/wsrmp/200702
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ReliableMessagingPolicy
{
    /**
     * Create a new RMAssertion with optional configuration
     *
     * @param array<string, mixed> $config Optional configuration array with keys:
     *                                    - inactivityTimeout: int (milliseconds)
     *                                    - baseRetransmissionInterval: int (milliseconds)
     *                                    - acknowledgementInterval: int (milliseconds)
     *                                    - exponentialBackoff: bool
     */
    public static function rmAssertion(array $config = []): RMAssertion
    {
        $assertion = new RMAssertion();

        if (isset($config['inactivityTimeout']) && is_int($config['inactivityTimeout'])) {
            $assertion->withInactivityTimeout($config['inactivityTimeout']);
        }

        if (isset($config['baseRetransmissionInterval']) && is_int($config['baseRetransmissionInterval'])) {
            $assertion->withBaseRetransmissionInterval($config['baseRetransmissionInterval']);
        }

        if (isset($config['acknowledgementInterval']) && is_int($config['acknowledgementInterval'])) {
            $assertion->withAcknowledgementInterval($config['acknowledgementInterval']);
        }

        if (isset($config['exponentialBackoff']) && is_bool($config['exponentialBackoff'])) {
            $assertion->withExponentialBackoff($config['exponentialBackoff']);
        }

        return $assertion;
    }

    /**
     * Create an ExactlyOnce delivery assurance policy
     *
     * @return array<string, mixed>
     */
    public static function exactlyOnce(): array
    {
        return DeliveryAssurance::exactlyOnce()->toArray();
    }

    /**
     * Create an AtLeastOnce delivery assurance policy
     *
     * @return array<string, mixed>
     */
    public static function atLeastOnce(): array
    {
        return DeliveryAssurance::atLeastOnce()->toArray();
    }

    /**
     * Create an AtMostOnce delivery assurance policy
     *
     * @return array<string, mixed>
     */
    public static function atMostOnce(): array
    {
        return DeliveryAssurance::atMostOnce()->toArray();
    }

    /**
     * Create an InOrder delivery assurance policy
     *
     * @return array<string, mixed>
     */
    public static function inOrder(): array
    {
        return DeliveryAssurance::inOrder()->toArray();
    }

    /**
     * Create an ExactlyOnce with InOrder delivery assurance policy
     *
     * @return array<string, mixed>
     */
    public static function exactlyOnceInOrder(): array
    {
        return DeliveryAssurance::exactlyOnceInOrder()->toArray();
    }

    /**
     * Create a complete reliable messaging policy with all components
     *
     * @param array<string, mixed> $config Configuration array with keys:
     *                                    - rmAssertion: array (RMAssertion config)
     *                                    - deliveryAssurance: string|array (assurance type(s))
     *                                    - acknowledgement: array (SequenceAcknowledgement config)
     * @return array<string, mixed>
     */
    public static function create(array $config = []): array
    {
        $policy = [
            'namespace' => RMAssertion::WSRMP_NS_URI,
            'policies' => [],
        ];

        // Add RMAssertion if configured
        if (isset($config['rmAssertion']) && is_array($config['rmAssertion'])) {
            /** @var array<string, mixed> $rmConfig */
            $rmConfig = $config['rmAssertion'];
            $assertion = self::rmAssertion($rmConfig);
            $policy['policies']['rmAssertion'] = $assertion->toArray();
        }

        // Add DeliveryAssurance if configured
        if (isset($config['deliveryAssurance'])) {
            $assurances = is_array($config['deliveryAssurance'])
                ? array_filter($config['deliveryAssurance'], 'is_string')
                : (is_string($config['deliveryAssurance']) ? [$config['deliveryAssurance']] : []);

            $deliveryAssurance = new DeliveryAssurance($assurances);
            $policy['policies']['deliveryAssurance'] = $deliveryAssurance->toArray();
        }

        // Add SequenceAcknowledgement if configured
        if (isset($config['acknowledgement']) && is_array($config['acknowledgement'])) {
            $ack = new SequenceAcknowledgement();

            if (isset($config['acknowledgement']['ackRequested']) && is_bool($config['acknowledgement']['ackRequested'])) {
                $ack->withAckRequested($config['acknowledgement']['ackRequested']);
            }

            if (isset($config['acknowledgement']['final']) && is_bool($config['acknowledgement']['final'])) {
                $ack->withFinal($config['acknowledgement']['final']);
            }

            $policy['policies']['acknowledgement'] = $ack->toArray();
        }

        return $policy;
    }
}
