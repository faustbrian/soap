<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Reflection;

use ReflectionParameter as NativeReflectionParameter;

final class ReflectionParameter
{
    protected int $position = 0;

    public function __construct(
        protected readonly NativeReflectionParameter $reflection,
        protected string $type = 'mixed',
        protected readonly string $description = '',
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getName(): string
    {
        return $this->reflection->getName();
    }

    public function isOptional(): bool
    {
        return $this->reflection->isOptional();
    }

    public function isDefaultValueAvailable(): bool
    {
        return $this->reflection->isDefaultValueAvailable();
    }

    public function getDefaultValue(): mixed
    {
        return $this->reflection->getDefaultValue();
    }

    public function setPosition(int $index): void
    {
        $this->position = $index;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
