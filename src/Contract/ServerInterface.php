<?php

namespace Cline\Soap\Contract;

interface ServerInterface
{
    /**
     * Attach a function as a server method.
     */
    public function addFunction($function, $namespace = '');

    /**
     * Attach a class to a server.
     */
    public function setClass($class, $namespace = '', $argv = null);

    /**
     * Generate a server fault.
     */
    public function fault($fault = null, $code = 404);

    /**
     * Handle a request.
     */
    public function handle($request = false);

    /**
     * Return a server definition array.
     */
    public function getFunctions();

    /**
     * Load server definition.
     */
    public function loadFunctions($definition);

    /**
     * Set server persistence.
     */
    public function setPersistence($mode);

    /**
     * Set auto-response flag for the server.
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
