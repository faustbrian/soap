<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Reflection;

final class Prototype
{
    /**
     * @param array<ReflectionParameter> $params
     */
    public function __construct(
        protected readonly ReflectionReturnValue $return,
        /** @var array<ReflectionParameter> */
        protected readonly array $params = [],
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
