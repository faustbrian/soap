<?php

namespace Cline\Soap\Reflection;

use ReflectionParameter as NativeReflectionParameter;

class ReflectionParameter
{
    protected NativeReflectionParameter $reflection;
    protected string $type;
    protected string $description;
    protected int $position = 0;

    public function __construct(NativeReflectionParameter $r, string $type = 'mixed', string $description = '')
    {
        $this->reflection = $r;
        $this->type = $type;
        $this->description = $description;
    }

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
