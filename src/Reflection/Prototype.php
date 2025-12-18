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
 * @psalm-immutable
 */
final readonly class Prototype
{
    /**
     * @param array<ReflectionParameter> $params
     */
    public function __construct(
        private ReflectionReturnValue $return,
        /** @var array<ReflectionParameter> */
        private array $params = [],
    ) {}

    public function getReturnType(): string
    {
        return $this->return->getType();
    }

    public function getReturnValue(): ReflectionReturnValue
    {
        return $this->return;
    }

    /**
     * @return array<ReflectionParameter>
     */
    public function getParameters(): array
    {
        return $this->params;
    }
}
