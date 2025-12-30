<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\WsExtensions\ReliableMessaging;

/**
 * WS-ReliableMessaging Assertion Builder
 *
 * Represents a WS-ReliableMessaging policy assertion with configurable
 * timeout and retransmission settings.
 *
 * @see http://docs.oasis-open.org/ws-rx/wsrm/200702
 * @see http://docs.oasis-open.org/ws-rx/wsrmp/200702
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class RMAssertion
{
    /**
     * WS-ReliableMessaging namespace URI
     */
    public const string WSRM_NS_URI = 'http://docs.oasis-open.org/ws-rx/wsrm/200702';

    /**
     * WS-ReliableMessaging Policy namespace URI
     */
    public const string WSRMP_NS_URI = 'http://docs.oasis-open.org/ws-rx/wsrmp/200702';

    /**
     * Inactivity timeout in milliseconds
     */
    private ?int $inactivityTimeout = null;

    /**
     * Base retransmission interval in milliseconds
     */
    private ?int $baseRetransmissionInterval = null;

    /**
     * Acknowledgement interval in milliseconds
     */
    private ?int $acknowledgementInterval = null;

    /**
     * Whether to use exponential backoff for retransmissions
     */
    private bool $exponentialBackoff = false;

    /**
     * Create a new RMAssertion instance
     */
    public function __construct()
    {
    }

    /**
     * Set the inactivity timeout
     *
     * This defines the maximum time a sequence can remain inactive before being terminated.
     *
     * @param int $milliseconds Timeout in milliseconds
     */
    public function withInactivityTimeout(int $milliseconds): self
    {
        $this->inactivityTimeout = $milliseconds;

        return $this;
    }

    /**
     * Set the base retransmission interval
     *
     * This defines the initial interval for message retransmissions.
     *
     * @param int $milliseconds Interval in milliseconds
     */
    public function withBaseRetransmissionInterval(int $milliseconds): self
    {
        $this->baseRetransmissionInterval = $milliseconds;

        return $this;
    }

    /**
     * Set the acknowledgement interval
     *
     * This defines how frequently acknowledgements should be sent.
     *
     * @param int $milliseconds Interval in milliseconds
     */
    public function withAcknowledgementInterval(int $milliseconds): self
    {
        $this->acknowledgementInterval = $milliseconds;

        return $this;
    }

    /**
     * Enable exponential backoff for retransmissions
     *
     * When enabled, the retransmission interval increases exponentially
     * with each retry attempt.
     */
    public function withExponentialBackoff(bool $enabled = true): self
    {
        $this->exponentialBackoff = $enabled;

        return $this;
    }

    /**
     * Get the inactivity timeout
     */
    public function getInactivityTimeout(): ?int
    {
        return $this->inactivityTimeout;
    }

    /**
     * Get the base retransmission interval
     */
    public function getBaseRetransmissionInterval(): ?int
    {
        return $this->baseRetransmissionInterval;
    }

    /**
     * Get the acknowledgement interval
     */
    public function getAcknowledgementInterval(): ?int
    {
        return $this->acknowledgementInterval;
    }

    /**
     * Check if exponential backoff is enabled
     */
    public function hasExponentialBackoff(): bool
    {
        return $this->exponentialBackoff;
    }

    /**
     * Convert the assertion to a policy configuration array
     *
     * Returns an array suitable for integration with WS-Policy infrastructure.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $config = [
            'namespace' => self::WSRMP_NS_URI,
            'assertion' => 'RMAssertion',
        ];

        if ($this->inactivityTimeout !== null) {
            $config['InactivityTimeout'] = [
                'namespace' => self::WSRMP_NS_URI,
                'value' => $this->inactivityTimeout,
            ];
        }

        if ($this->baseRetransmissionInterval !== null) {
            $config['BaseRetransmissionInterval'] = [
                'namespace' => self::WSRMP_NS_URI,
                'value' => $this->baseRetransmissionInterval,
            ];
        }

        if ($this->acknowledgementInterval !== null) {
            $config['AcknowledgementInterval'] = [
                'namespace' => self::WSRMP_NS_URI,
                'value' => $this->acknowledgementInterval,
            ];
        }

        if ($this->exponentialBackoff) {
            $config['ExponentialBackoff'] = [
                'namespace' => self::WSRMP_NS_URI,
            ];
        }

        return $config;
    }
}
