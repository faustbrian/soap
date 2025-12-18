<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Reflection;

use ReflectionClass as NativeReflectionClass;
use ReflectionMethod as NativeReflectionMethod;

use function str_starts_with;

/**
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class ReflectionClass
{
    public function __construct(
        private NativeReflectionClass $reflection,
        private string $namespace = '',
    ) {}

    public function getName(): string
    {
        return $this->reflection->getName();
    }

    public function getShortName(): string
    {
        return $this->reflection->getShortName();
    }

    /**
     * @return array<ReflectionMethod>
     */
    public function getMethods(): array
    {
        $methods = [];

        foreach ($this->reflection->getMethods(NativeReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor()) {
                continue;
            }

            if ($method->isDestructor()) {
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
