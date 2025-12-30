<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\Exception\InvalidArgumentException;
use Cline\Soap\WsExtensions\ReliableMessaging\DeliveryAssurance;
use Cline\Soap\WsExtensions\ReliableMessaging\ReliableMessagingPolicy;
use Cline\Soap\WsExtensions\ReliableMessaging\RMAssertion;
use Cline\Soap\WsExtensions\ReliableMessaging\SequenceAcknowledgement;

describe('WS-ReliableMessaging', function (): void {
    describe('RMAssertion', function (): void {
        describe('Happy Paths', function (): void {
            test('creates RMAssertion with default values', function (): void {
                // Act
                $assertion = new RMAssertion();

                // Assert
                expect($assertion->getInactivityTimeout())->toBeNull()
                    ->and($assertion->getBaseRetransmissionInterval())->toBeNull()
                    ->and($assertion->getAcknowledgementInterval())->toBeNull()
                    ->and($assertion->hasExponentialBackoff())->toBeFalse();
            });

            test('sets inactivity timeout', function (): void {
                // Arrange
                $timeout = 60000;

                // Act
                $assertion = (new RMAssertion())
                    ->withInactivityTimeout($timeout);

                // Assert
                expect($assertion->getInactivityTimeout())->toBe($timeout);
            });

            test('sets base retransmission interval', function (): void {
                // Arrange
                $interval = 5000;

                // Act
                $assertion = (new RMAssertion())
                    ->withBaseRetransmissionInterval($interval);

                // Assert
                expect($assertion->getBaseRetransmissionInterval())->toBe($interval);
            });

            test('sets acknowledgement interval', function (): void {
                // Arrange
                $interval = 2000;

                // Act
                $assertion = (new RMAssertion())
                    ->withAcknowledgementInterval($interval);

                // Assert
                expect($assertion->getAcknowledgementInterval())->toBe($interval);
            });

            test('enables exponential backoff', function (): void {
                // Act
                $assertion = (new RMAssertion())
                    ->withExponentialBackoff(true);

                // Assert
                expect($assertion->hasExponentialBackoff())->toBeTrue();
            });

            test('supports fluent interface for chaining', function (): void {
                // Act
                $assertion = (new RMAssertion())
                    ->withInactivityTimeout(60000)
                    ->withBaseRetransmissionInterval(5000)
                    ->withAcknowledgementInterval(2000)
                    ->withExponentialBackoff(true);

                // Assert
                expect($assertion->getInactivityTimeout())->toBe(60000)
                    ->and($assertion->getBaseRetransmissionInterval())->toBe(5000)
                    ->and($assertion->getAcknowledgementInterval())->toBe(2000)
                    ->and($assertion->hasExponentialBackoff())->toBeTrue();
            });

            test('converts to array with all properties', function (): void {
                // Arrange
                $assertion = (new RMAssertion())
                    ->withInactivityTimeout(60000)
                    ->withBaseRetransmissionInterval(5000)
                    ->withAcknowledgementInterval(2000)
                    ->withExponentialBackoff(true);

                // Act
                $array = $assertion->toArray();

                // Assert
                expect($array)->toBeArray()
                    ->and($array['namespace'])->toBe(RMAssertion::WSRMP_NS_URI)
                    ->and($array['assertion'])->toBe('RMAssertion')
                    ->and($array['InactivityTimeout']['value'])->toBe(60000)
                    ->and($array['BaseRetransmissionInterval']['value'])->toBe(5000)
                    ->and($array['AcknowledgementInterval']['value'])->toBe(2000)
                    ->and($array)->toHaveKey('ExponentialBackoff');
            });

            test('converts to array with partial properties', function (): void {
                // Arrange
                $assertion = (new RMAssertion())
                    ->withInactivityTimeout(60000);

                // Act
                $array = $assertion->toArray();

                // Assert
                expect($array)->toBeArray()
                    ->and($array)->toHaveKey('InactivityTimeout')
                    ->and($array)->not->toHaveKey('BaseRetransmissionInterval')
                    ->and($array)->not->toHaveKey('AcknowledgementInterval')
                    ->and($array)->not->toHaveKey('ExponentialBackoff');
            });
        });
    });

    describe('DeliveryAssurance', function (): void {
        describe('Happy Paths', function (): void {
            test('creates DeliveryAssurance with AtLeastOnce', function (): void {
                // Act
                $assurance = DeliveryAssurance::atLeastOnce();

                // Assert
                expect($assurance->hasAssurance(DeliveryAssurance::AT_LEAST_ONCE))->toBeTrue()
                    ->and($assurance->getAssurances())->toHaveCount(1)
                    ->and($assurance->getAssurances())->toContain(DeliveryAssurance::AT_LEAST_ONCE);
            });

            test('creates DeliveryAssurance with AtMostOnce', function (): void {
                // Act
                $assurance = DeliveryAssurance::atMostOnce();

                // Assert
                expect($assurance->hasAssurance(DeliveryAssurance::AT_MOST_ONCE))->toBeTrue();
            });

            test('creates DeliveryAssurance with ExactlyOnce', function (): void {
                // Act
                $assurance = DeliveryAssurance::exactlyOnce();

                // Assert
                expect($assurance->hasAssurance(DeliveryAssurance::EXACTLY_ONCE))->toBeTrue();
            });

            test('creates DeliveryAssurance with InOrder', function (): void {
                // Act
                $assurance = DeliveryAssurance::inOrder();

                // Assert
                expect($assurance->hasAssurance(DeliveryAssurance::IN_ORDER))->toBeTrue();
            });

            test('creates DeliveryAssurance with ExactlyOnce and InOrder', function (): void {
                // Act
                $assurance = DeliveryAssurance::exactlyOnceInOrder();

                // Assert
                expect($assurance->hasAssurance(DeliveryAssurance::EXACTLY_ONCE))->toBeTrue()
                    ->and($assurance->hasAssurance(DeliveryAssurance::IN_ORDER))->toBeTrue()
                    ->and($assurance->getAssurances())->toHaveCount(2);
            });

            test('adds multiple assurances via fluent interface', function (): void {
                // Act
                $assurance = new DeliveryAssurance();
                $assurance->addAssurance(DeliveryAssurance::EXACTLY_ONCE)
                    ->addAssurance(DeliveryAssurance::IN_ORDER);

                // Assert
                expect($assurance->getAssurances())->toHaveCount(2)
                    ->and($assurance->hasAssurance(DeliveryAssurance::EXACTLY_ONCE))->toBeTrue()
                    ->and($assurance->hasAssurance(DeliveryAssurance::IN_ORDER))->toBeTrue();
            });

            test('prevents duplicate assurances', function (): void {
                // Act
                $assurance = new DeliveryAssurance();
                $assurance->addAssurance(DeliveryAssurance::EXACTLY_ONCE)
                    ->addAssurance(DeliveryAssurance::EXACTLY_ONCE);

                // Assert
                expect($assurance->getAssurances())->toHaveCount(1);
            });

            test('converts to array with correct structure', function (): void {
                // Arrange
                $assurance = DeliveryAssurance::exactlyOnceInOrder();

                // Act
                $array = $assurance->toArray();

                // Assert
                expect($array)->toBeArray()
                    ->and($array['namespace'])->toBe(RMAssertion::WSRMP_NS_URI)
                    ->and($array['assertion'])->toBe('DeliveryAssurance')
                    ->and($array['assurances'])->toBeArray()
                    ->and($array['assurances'])->toHaveCount(2);
            });
        });

        describe('Sad Paths', function (): void {
            test('throws exception for invalid assurance', function (): void {
                // Arrange
                $assurance = new DeliveryAssurance();

                // Act & Assert
                expect(fn () => $assurance->addAssurance('InvalidAssurance'))
                    ->toThrow(InvalidArgumentException::class);
            });
        });
    });

    describe('SequenceAcknowledgement', function (): void {
        describe('Happy Paths', function (): void {
            test('creates SequenceAcknowledgement with default values', function (): void {
                // Act
                $ack = new SequenceAcknowledgement();

                // Assert
                expect($ack->isAckRequested())->toBeFalse()
                    ->and($ack->isFinal())->toBeFalse();
            });

            test('sets ack requested', function (): void {
                // Act
                $ack = (new SequenceAcknowledgement())
                    ->withAckRequested(true);

                // Assert
                expect($ack->isAckRequested())->toBeTrue();
            });

            test('sets final flag', function (): void {
                // Act
                $ack = (new SequenceAcknowledgement())
                    ->withFinal(true);

                // Assert
                expect($ack->isFinal())->toBeTrue();
            });

            test('supports fluent interface', function (): void {
                // Act
                $ack = (new SequenceAcknowledgement())
                    ->withAckRequested(true)
                    ->withFinal(true);

                // Assert
                expect($ack->isAckRequested())->toBeTrue()
                    ->and($ack->isFinal())->toBeTrue();
            });

            test('converts to array with correct structure', function (): void {
                // Arrange
                $ack = (new SequenceAcknowledgement())
                    ->withAckRequested(true)
                    ->withFinal(true);

                // Act
                $array = $ack->toArray();

                // Assert
                expect($array)->toBeArray()
                    ->and($array['namespace'])->toBe(RMAssertion::WSRM_NS_URI)
                    ->and($array['acknowledgement'])->toBeArray()
                    ->and($array['acknowledgement'])->toHaveKey('AckRequested')
                    ->and($array['acknowledgement'])->toHaveKey('Final');
            });
        });
    });

    describe('ReliableMessagingPolicy', function (): void {
        describe('Happy Paths', function (): void {
            test('creates RMAssertion via factory with configuration', function (): void {
                // Arrange
                $config = [
                    'inactivityTimeout' => 60000,
                    'baseRetransmissionInterval' => 5000,
                    'acknowledgementInterval' => 2000,
                    'exponentialBackoff' => true,
                ];

                // Act
                $assertion = ReliableMessagingPolicy::rmAssertion($config);

                // Assert
                expect($assertion)->toBeInstanceOf(RMAssertion::class)
                    ->and($assertion->getInactivityTimeout())->toBe(60000)
                    ->and($assertion->getBaseRetransmissionInterval())->toBe(5000)
                    ->and($assertion->getAcknowledgementInterval())->toBe(2000)
                    ->and($assertion->hasExponentialBackoff())->toBeTrue();
            });

            test('creates exactlyOnce policy array', function (): void {
                // Act
                $policy = ReliableMessagingPolicy::exactlyOnce();

                // Assert
                expect($policy)->toBeArray()
                    ->and($policy['namespace'])->toBe(RMAssertion::WSRMP_NS_URI)
                    ->and($policy['assurances'])->toBeArray();
            });

            test('creates atLeastOnce policy array', function (): void {
                // Act
                $policy = ReliableMessagingPolicy::atLeastOnce();

                // Assert
                expect($policy)->toBeArray()
                    ->and($policy['namespace'])->toBe(RMAssertion::WSRMP_NS_URI);
            });

            test('creates complete policy with all components', function (): void {
                // Arrange
                $config = [
                    'rmAssertion' => [
                        'inactivityTimeout' => 60000,
                        'baseRetransmissionInterval' => 5000,
                    ],
                    'deliveryAssurance' => [DeliveryAssurance::EXACTLY_ONCE, DeliveryAssurance::IN_ORDER],
                    'acknowledgement' => [
                        'ackRequested' => true,
                        'final' => false,
                    ],
                ];

                // Act
                $policy = ReliableMessagingPolicy::create($config);

                // Assert
                expect($policy)->toBeArray()
                    ->and($policy['namespace'])->toBe(RMAssertion::WSRMP_NS_URI)
                    ->and($policy['policies'])->toHaveKey('rmAssertion')
                    ->and($policy['policies'])->toHaveKey('deliveryAssurance')
                    ->and($policy['policies'])->toHaveKey('acknowledgement');
            });

            test('creates partial policy with only RMAssertion', function (): void {
                // Arrange
                $config = [
                    'rmAssertion' => [
                        'inactivityTimeout' => 60000,
                    ],
                ];

                // Act
                $policy = ReliableMessagingPolicy::create($config);

                // Assert
                expect($policy)->toBeArray()
                    ->and($policy['policies'])->toHaveKey('rmAssertion')
                    ->and($policy['policies'])->not->toHaveKey('deliveryAssurance')
                    ->and($policy['policies'])->not->toHaveKey('acknowledgement');
            });

            test('supports string-based delivery assurance in create method', function (): void {
                // Arrange
                $config = [
                    'deliveryAssurance' => DeliveryAssurance::EXACTLY_ONCE,
                ];

                // Act
                $policy = ReliableMessagingPolicy::create($config);

                // Assert
                expect($policy['policies']['deliveryAssurance'])->toBeArray()
                    ->and($policy['policies']['deliveryAssurance']['assurances'])->toHaveCount(1);
            });
        });
    });

    describe('Integration', function (): void {
        describe('Happy Paths', function (): void {
            test('integrates with WS-Policy infrastructure via array format', function (): void {
                // Arrange
                $assertion = (new RMAssertion())
                    ->withInactivityTimeout(60000)
                    ->withExponentialBackoff(true);

                $deliveryAssurance = DeliveryAssurance::exactlyOnceInOrder();

                // Act
                $assertionArray = $assertion->toArray();
                $deliveryArray = $deliveryAssurance->toArray();

                // Assert - verify structure is suitable for WS-Policy
                expect($assertionArray)->toHaveKey('namespace')
                    ->and($assertionArray)->toHaveKey('assertion')
                    ->and($deliveryArray)->toHaveKey('namespace')
                    ->and($deliveryArray)->toHaveKey('assertion');
            });

            test('creates complete reliable messaging configuration', function (): void {
                // Arrange & Act
                $policy = ReliableMessagingPolicy::create([
                    'rmAssertion' => [
                        'inactivityTimeout' => 120000,
                        'baseRetransmissionInterval' => 3000,
                        'acknowledgementInterval' => 1000,
                        'exponentialBackoff' => true,
                    ],
                    'deliveryAssurance' => [
                        DeliveryAssurance::EXACTLY_ONCE,
                        DeliveryAssurance::IN_ORDER,
                    ],
                    'acknowledgement' => [
                        'ackRequested' => true,
                        'final' => true,
                    ],
                ]);

                // Assert - verify complete policy structure
                expect($policy)->toBeArray()
                    ->and($policy['namespace'])->toBe(RMAssertion::WSRMP_NS_URI)
                    ->and($policy['policies']['rmAssertion']['InactivityTimeout']['value'])->toBe(120000)
                    ->and($policy['policies']['rmAssertion']['BaseRetransmissionInterval']['value'])->toBe(3000)
                    ->and($policy['policies']['rmAssertion']['AcknowledgementInterval']['value'])->toBe(1000)
                    ->and($policy['policies']['rmAssertion'])->toHaveKey('ExponentialBackoff')
                    ->and($policy['policies']['deliveryAssurance']['assurances'])->toHaveCount(2)
                    ->and($policy['policies']['acknowledgement']['acknowledgement'])->toHaveKey('AckRequested')
                    ->and($policy['policies']['acknowledgement']['acknowledgement'])->toHaveKey('Final');
            });
        });
    });
});
