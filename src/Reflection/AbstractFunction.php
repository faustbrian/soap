<?php

namespace Cline\Soap\Reflection;

use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

abstract class AbstractFunction
{
    protected ReflectionFunctionAbstract $reflection;
    protected string $namespace = '';
    protected string $description = '';
    /** @var Prototype[] */
    protected array $prototypes = [];

    public function __construct(ReflectionFunctionAbstract $r, string $namespace = '')
    {
        $this->reflection = $r;
        $this->namespace = $namespace;
        $this->buildSignature();
    }

    public function getName(): string
    {
        $name = $this->reflection->getName();
        return $this->namespace ? $this->namespace . '.' . $name : $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return Prototype[]
     */
    public function getPrototypes(): array
    {
        return $this->prototypes;
    }

    protected function buildSignature(): void
    {
        $docComment = $this->reflection->getDocComment();
        $this->description = $this->parseDescription($docComment ?: '');

        $paramTypesByName = $this->parseParamTypesByName($docComment ?: '');
        $paramTypesByPosition = $this->parseParamTypesByPosition($docComment ?: '');
        $returnType = $this->parseReturnType($docComment ?: '');

        $params = [];
        $position = 0;
        foreach ($this->reflection->getParameters() as $nativeParam) {
            $name = $nativeParam->getName();
            // First try native type, then docblock by name, then docblock by position
            $nativeType = $nativeParam->getType();
            if ($nativeType !== null) {
                $type = $this->getNativeType($nativeType);
            } elseif (isset($paramTypesByName[$name])) {
                $type = $paramTypesByName[$name];
            } elseif (isset($paramTypesByPosition[$position])) {
                $type = $paramTypesByPosition[$position];
            } else {
                $type = 'mixed';
            }
            $params[] = new ReflectionParameter($nativeParam, $type);
            $position++;
        }

        $return = new ReflectionReturnValue($returnType);
        $this->prototypes[] = new Prototype($return, $params);
    }

    protected function parseDescription(string $docComment): string
    {
        if (empty($docComment)) {
            return '';
        }

        $lines = explode("\n", $docComment);
        $description = [];

        foreach ($lines as $line) {
            $line = trim($line, " \t*");
            if (str_starts_with($line, '/') || str_starts_with($line, '@')) {
                continue;
            }
            if (!empty($line)) {
                $description[] = $line;
            }
        }

        return implode(' ', $description);
    }

    /**
     * @return array<string, string>
     */
    protected function parseParamTypesByName(string $docComment): array
    {
        $types = [];

        if (preg_match_all('/@param\s+(\S+)\s+\$(\w+)/', $docComment, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $types[$match[2]] = $this->normalizeType($match[1]);
            }
        }

        return $types;
    }

    /**
     * @return array<int, string>
     */
    protected function parseParamTypesByPosition(string $docComment): array
    {
        $types = [];

        if (preg_match_all('/@param\s+(\S+)\s+\$\w+/', $docComment, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $index => $match) {
                $types[$index] = $this->normalizeType($match[1]);
            }
        }

        return $types;
    }

    protected function parseReturnType(string $docComment): string
    {
        // First check native return type
        $nativeReturn = $this->reflection->getReturnType();
        if ($nativeReturn !== null) {
            return $this->getNativeType($nativeReturn);
        }

        // Fall back to docblock
        if (preg_match('/@return\s+(\S+)/', $docComment, $matches)) {
            return $this->normalizeType($matches[1]);
        }

        return 'void';
    }

    protected function getNativeType(?\ReflectionType $type): string
    {
        if ($type === null) {
            return 'mixed';
        }

        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
            if ($type->allowsNull() && $name !== 'mixed' && $name !== 'null') {
                return $name;
            }
            return $name;
        }

        if ($type instanceof ReflectionUnionType) {
            $types = array_map(
                fn($t) => $t instanceof ReflectionNamedType ? $t->getName() : 'mixed',
                $type->getTypes()
            );
            return implode('|', $types);
        }

        return 'mixed';
    }

    protected function normalizeType(string $type): string
    {
        // Handle common type aliases
        return match (strtolower($type)) {
            'integer' => 'int',
            'boolean' => 'bool',
            'double' => 'float',
            default => $type,
        };
    }
}
