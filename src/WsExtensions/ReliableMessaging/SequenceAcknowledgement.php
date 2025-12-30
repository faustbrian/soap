<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\WsExtensions\ReliableMessaging;

/**
 * WS-ReliableMessaging Sequence Acknowledgement Configuration
 *
 * Configures acknowledgement behavior for reliable messaging sequences,
 * including whether acknowledgements are requested and sequence finalization.
 *
 * @see http://docs.oasis-open.org/ws-rx/wsrm/200702
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class SequenceAcknowledgement
{
    /**
     * Whether acknowledgements should be requested
     */
    private bool $ackRequested = false;

    /**
     * Whether this is the final message in the sequence
     */
    private bool $final = false;

    /**
     * Create a new SequenceAcknowledgement instance
     */
    public function __construct()
    {
    }

    /**
     * Request acknowledgements for sequence messages
     */
    public function withAckRequested(bool $requested = true): self
    {
        $this->ackRequested = $requested;

        return $this;
    }

    /**
     * Mark this as the final message in the sequence
     */
    public function withFinal(bool $final = true): self
    {
        $this->final = $final;

        return $this;
    }

    /**
     * Check if acknowledgements are requested
     */
    public function isAckRequested(): bool
    {
        return $this->ackRequested;
    }

    /**
     * Check if this is the final message
     */
    public function isFinal(): bool
    {
        return $this->final;
    }

    /**
     * Convert to policy configuration array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $config = [
            'namespace' => RMAssertion::WSRM_NS_URI,
            'acknowledgement' => [],
        ];

        if ($this->ackRequested) {
            $config['acknowledgement']['AckRequested'] = [
                'namespace' => RMAssertion::WSRM_NS_URI,
            ];
        }

        if ($this->final) {
            $config['acknowledgement']['Final'] = [
                'namespace' => RMAssertion::WSRM_NS_URI,
            ];
        }

        return $config;
    }
}
