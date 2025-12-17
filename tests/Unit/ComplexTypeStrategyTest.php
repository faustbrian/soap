<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\Wsdl;
use Cline\Soap\Wsdl\ComplexTypeStrategy\AnyType;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence;
use Cline\Soap\Wsdl\ComplexTypeStrategy\Composite;
use Cline\Soap\Wsdl\ComplexTypeStrategy\DefaultComplexType;
use Tests\Fixtures\TestClass;
use Tests\Fixtures\WsdlTestClass;


beforeEach(function (): void {
    skipIfSoapNotLoaded();
});

describe('AnyType Strategy', function (): void {
    test('returns xsd:anyType for any class', function (): void {
        $strategy = new AnyType();
        $wsdl = new Wsdl('TestService', 'http://localhost/test', $strategy);

        $type = $wsdl->addComplexType(WsdlTestClass::class);

        expect($type)->toBe('xsd:anyType');
    });
});

describe('DefaultComplexType Strategy', function (): void {
    test('creates complex type for class', function (): void {
        $strategy = new DefaultComplexType();
        $wsdl = new Wsdl('TestService', 'http://localhost/test', $strategy);

        $type = $wsdl->addComplexType(WsdlTestClass::class);

        expect($type)->toBe('tns:WsdlTestClass');

        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom, 'http://localhost/test');

        $nodes = $xpath->query('//xsd:complexType[@name="WsdlTestClass"]');
        expect($nodes->length)->toBe(1);
    });

    test('adds sequence for class properties', function (): void {
        $strategy = new DefaultComplexType();
        $wsdl = new Wsdl('TestService', 'http://localhost/test', $strategy);

        $wsdl->addComplexType(WsdlTestClass::class);

        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom, 'http://localhost/test');

        $sequences = $xpath->query('//xsd:complexType[@name="WsdlTestClass"]/xsd:all | //xsd:complexType[@name="WsdlTestClass"]/xsd:sequence');
        expect($sequences->length)->toBe(1);
    });
});

describe('ArrayOfTypeComplex Strategy', function (): void {
    test('handles arrays of complex types', function (): void {
        $strategy = new ArrayOfTypeComplex();
        $wsdl = new Wsdl('TestService', 'http://localhost/test', $strategy);

        $type = $wsdl->addComplexType(WsdlTestClass::class.'[]');

        expect($type)->toBe('tns:ArrayOfWsdlTestClass');

        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom, 'http://localhost/test');

        // Check that ArrayOfWsdlTestClass type is created
        $arrayTypes = $xpath->query('//xsd:complexType[@name="ArrayOfWsdlTestClass"]');
        expect($arrayTypes->length)->toBe(1);
    });

    test('handles non-array class types', function (): void {
        $strategy = new ArrayOfTypeComplex();
        $wsdl = new Wsdl('TestService', 'http://localhost/test', $strategy);

        $type = $wsdl->addComplexType(WsdlTestClass::class);

        expect($type)->toBe('tns:WsdlTestClass');
    });
});

describe('ArrayOfTypeSequence Strategy', function (): void {
    test('creates sequence for array types', function (): void {
        $strategy = new ArrayOfTypeSequence();
        $wsdl = new Wsdl('TestService', 'http://localhost/test', $strategy);

        $type = $wsdl->addComplexType('string[]');

        expect($type)->toContain('ArrayOf');

        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom, 'http://localhost/test');

        // Check that sequence element is created within the array type
        $sequences = $xpath->query('//xsd:complexType/xsd:sequence');
        expect($sequences->length)->toBeGreaterThan(0);
    });
});

describe('Composite Strategy', function (): void {
    test('can use multiple strategies based on class', function (): void {
        $composite = new Composite();
        $composite->connectTypeToStrategy(WsdlTestClass::class, new DefaultComplexType());
        $composite->connectTypeToStrategy('string[]', new ArrayOfTypeSequence());

        $wsdl = new Wsdl('TestService', 'http://localhost/test', $composite);

        $type1 = $wsdl->addComplexType(WsdlTestClass::class);
        expect($type1)->toBe('tns:WsdlTestClass');
    });

    test('uses default strategy when no mapping found', function (): void {
        $defaultStrategy = new AnyType();
        $composite = new Composite([], $defaultStrategy);

        $wsdl = new Wsdl('TestService', 'http://localhost/test', $composite);

        $type = $wsdl->addComplexType(WsdlTestClass::class);

        expect($type)->toBe('xsd:anyType');
    });

    test('can get type strategy', function (): void {
        $defaultStrategy = new DefaultComplexType();
        $specificStrategy = new AnyType();

        $composite = new Composite([], $defaultStrategy);
        $composite->connectTypeToStrategy(WsdlTestClass::class, $specificStrategy);

        expect($composite->getStrategyOfType(WsdlTestClass::class))->toBe($specificStrategy);
        expect($composite->getDefaultStrategy())->toBe($defaultStrategy);
    });
});
