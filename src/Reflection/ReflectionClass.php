<?php

namespace Cline\Soap\Reflection;

use ReflectionClass as NativeReflectionClass;

class ReflectionClass
{
    protected NativeReflectionClass $reflection;
    protected string $namespace;

    public function __construct(NativeReflectionClass $reflection, string $namespace = '')
    {
        $this->reflection = $reflection;
        $this->namespace = $namespace;
    }

    public function getName(): string
    {
        return $this->reflection->getName();
    }

    public function getShortName(): string
    {
        return $this->reflection->getShortName();
    }

    /**
     * @return ReflectionMethod[]
     */
    public function getMethods(): array
    {
        $methods = [];

        foreach ($this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isDestructor()) {
                continue;
            }
            if (str_starts_with($method->getName(), '__')) {
                continue;
            }

            $methods[] = new ReflectionMethod($method, $this->namespace);
        }

        return $methods;
    }
}
