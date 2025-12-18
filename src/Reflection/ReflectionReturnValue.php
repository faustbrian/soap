<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Reflection;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class ReflectionReturnValue
{
    public function __construct(
        protected readonly string $type = 'mixed',
        protected readonly string $description = '',
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
