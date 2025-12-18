<?php

namespace Cline\Soap\Reflection;

class ReflectionReturnValue
{
    protected string $type;
    protected string $description;

    public function __construct(string $type = 'mixed', string $description = '')
    {
        $this->type = $type;
        $this->description = $description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
