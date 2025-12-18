<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Contract;

interface ClientInterface
{
    /**
     * Execute remote call.
     *
     * @param  string $method Remote call name.
     * @param  array  $params Call parameters.
     * @return mixed  Remote call results.
     */
    public function call($method, $params = []);
}
