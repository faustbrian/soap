<?php

namespace Cline\Soap\Reflection;

class Prototype
{
    protected ReflectionReturnValue $return;
    /** @var ReflectionParameter[] */
    protected array $params;

    /**
     * @param ReflectionParameter[] $params
     */
    public function __construct(ReflectionReturnValue $return, array $params = [])
    {
        $this->return = $return;
        $this->params = $params;
    }

    public function getReturnType(): string
    {
        return $this->return->getType();
    }

    public function getReturnValue(): ReflectionReturnValue
    {
        return $this->return;
    }

    /**
     * @return ReflectionParameter[]
     */
    public function getParameters(): array
    {
        return $this->params;
    }
}
