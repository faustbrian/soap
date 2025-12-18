<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Contract;

use Exception;
use SoapFault;

/**
 * @author Brian Faust <brian@cline.sh>
 */
interface ServerInterface
{
    /**
     * Attach a function as a server method.
     *
     * @param array<string>|int|string $function
     */
    public function addFunction(array|int|string $function, string $namespace = ''): static;

    /**
     * Attach a class to a server.
     */
    public function setClass(object|string $class, string $namespace = '', mixed $argv = null): static;

    /**
     * Generate a server fault.
     */
    public function fault(Exception|string|null $fault = null, string $code = 'Receiver'): SoapFault;

    /**
     * Handle a request.
     */
    public function handle(mixed $request = null): mixed;

    /**
     * Return a server definition array.
     *
     * @return array<int|string, mixed>
     */
    public function getFunctions(): array;

    /**
     * Load server definition.
     *
     * @param array<int|string, mixed> $definition
     */
    public function loadFunctions(array $definition): void;

    /**
     * Set server persistence.
     */
    public function setPersistence(int $mode): static;

    /**
     * Set auto-response flag for the server.
     */
    public function setReturnResponse(bool $flag = true): static;

    /**
     * Return auto-response flag of the server.
     */
    public function getReturnResponse(): bool;

    /**
     * Return last produced response.
     */
    public function getResponse(): string;
}
