<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

/**
 * A class with documentation for testing.
 * @author Brian Faust <brian@cline.sh>
 */
final class DocumentedClass
{
    /**
     * This property has documentation.
     */
    public string $documented = '';

    public string $undocumented = '';

    /**
     * First line of documentation.
     * Second line continues.
     */
    public string $multiline = '';

    /**
     * Description before annotation.
     *
     * @deprecated Use $documented instead
     */
    public string $withAnnotation = '';
}
