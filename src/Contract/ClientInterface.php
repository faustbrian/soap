<?php

namespace Cline\Soap\Contract;

interface ClientInterface
{
    /**
     * Execute remote call.
     *
     * @param string $method Remote call name.
     * @param array $params Call parameters.
     * @return mixed Remote call results.
     */
    public function call($method, $params = []);
}
