<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Reflection;

use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

use const PREG_SET_ORDER;

use function array_map;
use function explode;
use function implode;
use function mb_strtolower;
use function mb_trim;
use function preg_match;
use function preg_match_all;
use function str_starts_with;

/**
 * @author Brian Faust <brian@cline.sh>
 */
abstract class AbstractFunction
{
    protected string $description = '';

    /** @var array<Prototype> */
    protected array $prototypes = [];

    public function __construct(
        protected readonly ReflectionFunctionAbstract $reflection,
        protected readonly string $namespace = '',
    ) {
        $this->buildSignature();
    }

    public function getName(): string
    {
        $name = $this->reflection->getName();

        return $this->namespace !== '' && $this->namespace !== '0' ? $this->namespace.'.'.$name : $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array<Prototype>
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
            ++$position;
        }

        $return = new ReflectionReturnValue($returnType);
        $this->prototypes[] = new Prototype($return, $params);
    }

    protected function parseDescription(string $docComment): string
    {
        if ($docComment === '' || $docComment === '0') {
            return '';
        }

        $lines = explode("\n", $docComment);
        $description = [];

        foreach ($lines as $line) {
            $line = mb_trim($line, " \t*");
            if (str_starts_with($line, '/')) {
                continue;
            }
            if (str_starts_with($line, '@')) {
                continue;
            }
            if ($line === '') {
                continue;
            }
            if ($line === '0') {
                continue;
            }

            $description[] = $line;
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

    protected function getNativeType(?ReflectionType $type): string
    {
        if (!$type instanceof \ReflectionType) {
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
                fn (\ReflectionIntersectionType|\ReflectionNamedType $t) => $t instanceof ReflectionNamedType ? $t->getName() : 'mixed',
                $type->getTypes(),
            );

            return implode('|', $types);
        }

        return 'mixed';
    }

    protected function normalizeType(string $type): string
    {
        // Handle common type aliases
        $normalized = match (mb_strtolower($type)) {
            'integer' => 'int',
            'boolean' => 'bool',
            'double' => 'float',
            default => $type,
        };

        // Resolve self/static to the declaring class name
        if (mb_strtolower($normalized) !== 'self' && mb_strtolower($normalized) !== 'static') {
            return $normalized;
        }
        if ($this->reflection instanceof ReflectionMethod) {
            return $this->reflection->getDeclaringClass()->getName();
        }

        return $normalized;
    }
}
