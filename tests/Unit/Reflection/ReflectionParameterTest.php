<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\Reflection\ReflectionParameter;
use ReflectionParameter as NativeReflectionParameter;

describe('ReflectionParameter', function (): void {
    describe('Happy Paths', function (): void {
        test('getType returns type', function (): void {
            $native = new NativeReflectionParameter(fn (string $param): mixed => $param, 'param');
            $param = new ReflectionParameter($native, 'string', 'A parameter');

            expect($param->getType())->toBe('string');
        });

        test('setType changes type', function (): void {
            $native = new NativeReflectionParameter(fn ($param): mixed => $param, 'param');
            $param = new ReflectionParameter($native);

            $param->setType('int');

            expect($param->getType())->toBe('int');
        });

        test('getDescription returns description', function (): void {
            $native = new NativeReflectionParameter(fn ($param): mixed => $param, 'param');
            $param = new ReflectionParameter($native, 'mixed', 'Parameter description');

            expect($param->getDescription())->toBe('Parameter description');
        });

        test('getName returns parameter name', function (): void {
            $native = new NativeReflectionParameter(fn ($myParam): mixed => $myParam, 'myParam');
            $param = new ReflectionParameter($native);

            expect($param->getName())->toBe('myParam');
        });

        test('isOptional returns true for optional parameter', function (): void {
            $native = new NativeReflectionParameter(fn ($param = null): mixed => $param, 'param');
            $param = new ReflectionParameter($native);

            expect($param->isOptional())->toBeTrue();
        });

        test('isOptional returns false for required parameter', function (): void {
            $native = new NativeReflectionParameter(fn ($param): mixed => $param, 'param');
            $param = new ReflectionParameter($native);

            expect($param->isOptional())->toBeFalse();
        });

        test('isDefaultValueAvailable returns true when default exists', function (): void {
            $native = new NativeReflectionParameter(fn ($param = 'default'): mixed => $param, 'param');
            $param = new ReflectionParameter($native);

            expect($param->isDefaultValueAvailable())->toBeTrue();
        });

        test('isDefaultValueAvailable returns false when no default', function (): void {
            $native = new NativeReflectionParameter(fn ($param): mixed => $param, 'param');
            $param = new ReflectionParameter($native);

            expect($param->isDefaultValueAvailable())->toBeFalse();
        });

        test('getDefaultValue returns default value', function (): void {
            $native = new NativeReflectionParameter(fn ($param = 42): mixed => $param, 'param');
            $param = new ReflectionParameter($native);

            expect($param->getDefaultValue())->toBe(42);
        });

        test('setPosition and getPosition manage position', function (): void {
            $native = new NativeReflectionParameter(fn ($param): mixed => $param, 'param');
            $param = new ReflectionParameter($native);

            expect($param->getPosition())->toBe(0);

            $param->setPosition(5);

            expect($param->getPosition())->toBe(5);
        });
    });
});
