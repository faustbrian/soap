<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

/**
 * MyCalculatorService
 *
 * Class used in DocumentLiteralWrapperTest
 * @author Brian Faust <brian@cline.sh>
 */
final class MyCalculatorService
{
    /**
     * @param  int $x
     * @param  int $y
     * @return int
     */
    public function add($x, $y)
    {
        return $x + $y;
    }
}
