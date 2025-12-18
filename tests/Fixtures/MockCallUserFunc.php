<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

/**
 * Allows mocking of call_user_func.
 * @author Brian Faust <brian@cline.sh>
 */
final class MockCallUserFunc
{
    /**
     * Whether to mock the call_user_func function.
     *
     * @var bool
     */
    public static $mock = false;

    /**
     * Passed parameters.
     *
     * @var array
     */
    public static $params = [];
}
