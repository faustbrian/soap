<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\Wsdl\DocumentationStrategy\ReflectionDocumentation;
use Tests\Fixtures\DocumentedClass;

describe('ReflectionDocumentation', function (): void {
    describe('Happy Paths', function (): void {
        test('getPropertyDocumentation extracts description from docblock', function (): void {
            $strategy = new ReflectionDocumentation();
            $reflection = new ReflectionClass(DocumentedClass::class);
            $property = $reflection->getProperty('documented');

            $result = $strategy->getPropertyDocumentation($property);

            expect($result)->toBe('This property has documentation.');
        });

        test('getComplexTypeDocumentation extracts class description', function (): void {
            $strategy = new ReflectionDocumentation();
            $reflection = new ReflectionClass(DocumentedClass::class);

            $result = $strategy->getComplexTypeDocumentation($reflection);

            expect($result)->toBe('A class with documentation for testing.');
        });

        test('handles multiline documentation', function (): void {
            $strategy = new ReflectionDocumentation();
            $reflection = new ReflectionClass(DocumentedClass::class);
            $property = $reflection->getProperty('multiline');

            $result = $strategy->getPropertyDocumentation($property);

            expect($result)->toContain('First line of documentation.')
                ->and($result)->toContain('Second line continues.');
        });
    });

    describe('Sad Paths', function (): void {
        test('returns empty string for undocumented property', function (): void {
            $strategy = new ReflectionDocumentation();
            $reflection = new ReflectionClass(DocumentedClass::class);
            $property = $reflection->getProperty('undocumented');

            $result = $strategy->getPropertyDocumentation($property);

            expect($result)->toBe('');
        });

        test('returns empty string for undocumented class', function (): void {
            $strategy = new ReflectionDocumentation();
            $reflection = new ReflectionClass(stdClass::class);

            $result = $strategy->getComplexTypeDocumentation($reflection);

            expect($result)->toBe('');
        });
    });

    describe('Edge Cases', function (): void {
        test('stops at annotation tags', function (): void {
            $strategy = new ReflectionDocumentation();
            $reflection = new ReflectionClass(DocumentedClass::class);
            $property = $reflection->getProperty('withAnnotation');

            $result = $strategy->getPropertyDocumentation($property);

            expect($result)->toContain('Description before annotation.')
                ->and($result)->not->toContain('@deprecated');
        });
    });
});
