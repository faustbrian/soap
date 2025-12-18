<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Contract;

interface ServerInterface
{
    /**
     * Attach a function as a server method.
     * @param mixed $function
     * @param mixed $namespace
     */
    public function addFunction($function, $namespace = '');

    /**
     * Attach a class to a server.
     * @param mixed      $class
     * @param mixed      $namespace
     * @param null|mixed $argv
     */
    public function setClass($class, $namespace = '', $argv = null);

    /**
     * Generate a server fault.
     * @param null|mixed $fault
     * @param mixed      $code
     */
    public function fault($fault = null, $code = 404);

    /**
     * Handle a request.
     * @param mixed $request
     */
    public function handle($request = false);

    /**
     * Return a server definition array.
     */
    public function getFunctions();

    /**
     * Load server definition.
     * @param mixed $definition
     */
    public function loadFunctions($definition);

    /**
     * Set server persistence.
     * @param mixed $mode
     */
    public function setPersistence($mode);

    /**
     * Set auto-response flag for the server.
     * @param mixed $flag
     */
    public function setReturnResponse($flag = true);

    /**
     * Return auto-response flag of the server.
     */
    public function getReturnResponse();

    /**
     * Return last produced response.
     */
    public function getResponse();
}
