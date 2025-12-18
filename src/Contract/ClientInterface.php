<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Contract;

/**
 * @author Brian Faust <brian@cline.sh>
 */
interface ClientInterface
{
    /**
     * Execute remote call.
     *
     * @param array<mixed> $params Call parameters.
     *
     * @return mixed Remote call results.
     */
    public function call(string $method, array $params = []): mixed;
}
