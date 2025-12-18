<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\Exception\InvalidArgumentException;
use Cline\Soap\Wsdl;
use Cline\Soap\Wsdl\ComplexTypeStrategy\AnyType;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence;
use Cline\Soap\Wsdl\ComplexTypeStrategy\Composite;
use Cline\Soap\Wsdl\ComplexTypeStrategy\DefaultComplexType;
use Cline\Soap\Wsdl\DocumentationStrategy\DocumentationStrategyInterface;
use Tests\Fixtures\Anything;
use Tests\Fixtures\Book;
use Tests\Fixtures\ComplexObjectStructure;
use Tests\Fixtures\ComplexObjectWithObjectStructure;
use Tests\Fixtures\ComplexTest;
use Tests\Fixtures\ComplexTypeA;
use Tests\Fixtures\ComplexTypeB;
use Tests\Fixtures\Cookie;
use Tests\Fixtures\PublicPrivateProtected;
use Tests\Fixtures\SequenceTest;
use Tests\Fixtures\WsdlTestClass;

beforeEach(function (): void {
    skipIfSoapNotLoaded();
});

describe('ArrayOfTypeComplex Strategy', function (): void {
    describe('Happy Paths', function (): void {
        test('creates array type for simple objects with single property', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeComplex();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act
            $return = $wsdl->addComplexType('\Tests\Fixtures\ComplexTest[]');
            $return2 = $wsdl->addComplexType('\Tests\Fixtures\ComplexTest[]');

            // Assert
            expect($return)->toBe('tns:ArrayOfComplexTest')
                ->and($return2)->toBe('tns:ArrayOfComplexTest');

            $dom = $wsdl->toDomDocument();
            $xpath = registerWsdlNamespaces($dom, 'http://localhost/MyService.php');

            // Verify single element in complex type
            $nodes = $xpath->query('//wsdl:types/*/xsd:complexType[@name="ComplexTest"]/xsd:all/xsd:element');
            expect($nodes->length)->toBe(1);
            expect($nodes->item(0)->getAttribute('name'))->toBe('var');
            expect($nodes->item(0)->getAttribute('type'))->toBe('xsd:int');

            // Verify array type definition
            $nodes = $xpath->query(
                '//wsdl:types/*/xsd:complexType[@name="ArrayOfComplexTest"]/xsd:complexContent/xsd:restriction',
            );
            expect($nodes->length)->toBe(1);
            expect($nodes->item(0)->getAttribute('base'))->toBe('soap-enc:Array');

            $nodes = $xpath->query('xsd:attribute', $nodes->item(0));
            expect($nodes->item(0)->getAttribute('ref'))->toBe('soap-enc:arrayType');
            expect($nodes->item(0)->getAttributeNS(Wsdl::WSDL_NS_URI, 'arrayType'))->toBe('tns:ComplexTest[]');

            assertDocumentNodesHaveNamespaces($dom);
        });

        test('creates array type for complex objects with multiple properties', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeComplex();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act
            $return = $wsdl->addComplexType('\Tests\Fixtures\ComplexObjectStructure[]');

            // Assert
            expect($return)->toBe('tns:ArrayOfComplexObjectStructure');

            $dom = $wsdl->toDomDocument();
            $xpath = registerWsdlNamespaces($dom, 'http://localhost/MyService.php');

            $nodes = $xpath->query(
                '//wsdl:types/xsd:schema/xsd:complexType[@name="ComplexObjectStructure"]/xsd:all',
            );
            expect($nodes->item(0)->childNodes->length)->toBe(4);

            // Verify each property
            $expectedProperties = [
                'boolean' => 'xsd:boolean',
                'string' => 'xsd:string',
                'int' => 'xsd:int',
                'array' => 'soap-enc:Array',
            ];

            foreach ($expectedProperties as $name => $type) {
                $node = $xpath->query('xsd:element[@name="'.$name.'"]', $nodes->item(0));
                expect($node->item(0)->getAttribute('name'))->toBe($name);
                expect($node->item(0)->getAttribute('type'))->toBe($type);
            }

            assertDocumentNodesHaveNamespaces($dom);
        });

        test('creates array type for objects containing nested objects', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeComplex();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act
            $return = $wsdl->addComplexType('\Tests\Fixtures\ComplexObjectWithObjectStructure[]');

            // Assert
            expect($return)->toBe('tns:ArrayOfComplexObjectWithObjectStructure');

            $dom = $wsdl->toDomDocument();
            $xpath = registerWsdlNamespaces($dom, 'http://localhost/MyService.php');

            // Verify nested ComplexTest type
            $nodes = $xpath->query('//wsdl:types/*/xsd:complexType[@name="ComplexTest"]/xsd:all/xsd:element');
            expect($nodes->length)->toBe(1);
            expect($nodes->item(0)->getAttribute('name'))->toBe('var');
            expect($nodes->item(0)->getAttribute('type'))->toBe('xsd:int');

            // Verify parent object structure
            $nodes = $xpath->query(
                '//wsdl:types/*/xsd:complexType[@name="ComplexObjectWithObjectStructure"]/xsd:all/xsd:element',
            );
            expect($nodes->length)->toBe(1);
            expect($nodes->item(0)->getAttribute('name'))->toBe('object');
            expect($nodes->item(0)->getAttribute('type'))->toBe('tns:ComplexTest');
            expect($nodes->item(0)->getAttribute('nillable'))->toBe('true');

            assertDocumentNodesHaveNamespaces($dom);
        });

        test('handles recursive complex type arrays with proper nesting', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeComplex();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act
            $return = $wsdl->addComplexType(ComplexTypeA::class);

            // Assert
            expect($return)->toBe('tns:ComplexTypeA');

            $dom = $wsdl->toDomDocument();
            $xpath = registerWsdlNamespaces($dom, 'http://localhost/MyService.php');

            // Verify ComplexTypeB structure
            $nodes = $xpath->query('//wsdl:types/xsd:schema/xsd:complexType[@name="ComplexTypeB"]/xsd:all');
            expect($nodes->item(0)->childNodes->length)->toBe(2);

            foreach (['bar' => 'xsd:string', 'foo' => 'xsd:string'] as $name => $type) {
                $node = $xpath->query('xsd:element[@name="'.$name.'"]', $nodes->item(0));
                expect($node->item(0)->getAttribute('name'))->toBe($name);
                expect($node->item(0)->getAttribute('type'))->toBe($type);
                expect($node->item(0)->getAttribute('nillable'))->toBe('true');
            }

            // Verify ComplexTypeA references ArrayOfComplexTypeB
            $nodes = $xpath->query(
                '//wsdl:types/*/xsd:complexType[@name="ComplexTypeA"]/xsd:all/xsd:element',
            );
            expect($nodes->length)->toBe(1);
            expect($nodes->item(0)->getAttribute('name'))->toBe('baz');
            expect($nodes->item(0)->getAttribute('type'))->toBe('tns:ArrayOfComplexTypeB');

            assertDocumentNodesHaveNamespaces($dom);
        });

        test('resets strategy after processing array types', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeComplex();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act
            $return = $wsdl->addComplexType('\Tests\Fixtures\ComplexTest[]');

            // Assert
            expect($return)->toBe('tns:ArrayOfComplexTest');
        });
    });

    describe('Sad Paths', function (): void {
        test('throws exception when nesting arrays deeper than one level', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeComplex();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act & Assert
            expect(fn () => $wsdl->addComplexType('\Tests\Fixtures\ComplexTest[][]'))
                ->toThrow(InvalidArgumentException::class, 'ArrayOfTypeComplex cannot return nested ArrayOfObject deeper than one level');
        });

        test('throws exception when adding array of non-existent class', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeComplex();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act & Assert
            expect(fn () => $wsdl->addComplexType('\Tests\Fixtures\UnknownClass[]'))
                ->toThrow(InvalidArgumentException::class, 'Cannot add a complex type \Tests\Fixtures\UnknownClass that is not an object or where class');
        });
    });

    describe('Edge Cases', function (): void {
        test('prevents duplicate type definitions when adding same array type multiple times', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeComplex();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act
            $wsdl->addComplexType('\Tests\Fixtures\ComplexObjectWithObjectStructure[]');
            $wsdl->addComplexType('\Tests\Fixtures\ComplexObjectWithObjectStructure[]');

            // Assert
            $dom = $wsdl->toDomDocument();
            $xpath = registerWsdlNamespaces($dom, 'http://localhost/MyService.php');

            $nodes = $xpath->query('//*[@*[namespace-uri()="'.Wsdl::WSDL_NS_URI
                .'" and local-name()="arrayType"]="tns:ComplexObjectWithObjectStructure[]"]');
            expect($nodes->length)->toBe(1);

            $nodes = $xpath->query('//xsd:complexType[@name="ArrayOfComplexObjectWithObjectStructure"]');
            expect($nodes->length)->toBe(1);

            $nodes = $xpath->query('//xsd:complexType[@name="ComplexTest"]');
            expect($nodes->length)->toBe(1);

            assertDocumentNodesHaveNamespaces($dom);
        });

        test('handles adding singular type followed by array type correctly', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeComplex();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act
            $wsdl->addComplexType(ComplexObjectWithObjectStructure::class);
            $wsdl->addComplexType('\Tests\Fixtures\ComplexObjectWithObjectStructure[]');

            // Assert
            $dom = $wsdl->toDomDocument();
            $xpath = registerWsdlNamespaces($dom, 'http://localhost/MyService.php');

            $nodes = $xpath->query('//*[@*[namespace-uri()="'.Wsdl::WSDL_NS_URI
                .'" and local-name()="arrayType"]="tns:ComplexObjectWithObjectStructure[]"]');
            expect($nodes->length)->toBe(1);

            $nodes = $xpath->query('//xsd:complexType[@name="ArrayOfComplexObjectWithObjectStructure"]');
            expect($nodes->length)->toBe(1);

            $nodes = $xpath->query('//xsd:complexType[@name="ComplexTest"]');
            expect($nodes->length)->toBe(1);

            assertDocumentNodesHaveNamespaces($dom);
        });
    });
});

describe('ArrayOfTypeSequence Strategy', function (): void {
    describe('Happy Paths', function (): void {
        test('creates sequence structure for arrays of basic types', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeSequence();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            $testCases = [
                ['int', 'ArrayOfInt'],
                ['string', 'ArrayOfString'],
                ['boolean', 'ArrayOfBoolean'],
                ['float', 'ArrayOfFloat'],
                ['double', 'ArrayOfDouble'],
            ];

            foreach ($testCases as [$type, $arrayTypeName]) {
                // Act
                $wsdl->addComplexType($type.'[]');
                $wsdl->addComplexType($type.'[]'); // Test duplicate handling

                // Assert
                $dom = $wsdl->toDomDocument();
                $xpath = registerWsdlNamespaces($dom, 'http://localhost/MyService.php');

                $nodes = $xpath->query('//wsdl:types/xsd:schema/xsd:complexType[@name="'.$arrayTypeName.'"]');
                expect($nodes->length)->toBe(1);

                $nodes = $xpath->query('xsd:sequence/xsd:element', $nodes->item(0));
                expect($nodes->length)->toBe(1);
                expect($nodes->item(0)->getAttribute('name'))->toBe('item');
                expect($nodes->item(0)->getAttribute('type'))->toBe('xsd:'.$type);
                expect($nodes->item(0)->getAttribute('minOccurs'))->toBe('0');
                expect($nodes->item(0)->getAttribute('maxOccurs'))->toBe('unbounded');

                assertDocumentNodesHaveNamespaces($dom);
            }
        });

        test('creates nested array definitions for multidimensional arrays', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeSequence();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            $testCases = [
                [
                    'string[][]',
                    'ArrayOfArrayOfString',
                    [
                        'ArrayOfString' => 'xsd:string',
                        'ArrayOfArrayOfString' => 'tns:ArrayOfString',
                    ],
                ],
                [
                    'string[][][]',
                    'ArrayOfArrayOfArrayOfString',
                    [
                        'ArrayOfString' => 'xsd:string',
                        'ArrayOfArrayOfString' => 'tns:ArrayOfString',
                        'ArrayOfArrayOfArrayOfString' => 'tns:ArrayOfArrayOfString',
                    ],
                ],
                [
                    'int[][]',
                    'ArrayOfArrayOfInt',
                    [
                        'ArrayOfInt' => 'xsd:int',
                        'ArrayOfArrayOfInt' => 'tns:ArrayOfInt',
                    ],
                ],
            ];

            foreach ($testCases as [$stringDefinition, $definedTypeName, $nestedTypeNames]) {
                // Act
                $return = $wsdl->addComplexType($stringDefinition);

                // Assert
                expect($return)->toBe('tns:'.$definedTypeName);

                $dom = $wsdl->toDomDocument();
                $xpath = registerWsdlNamespaces($dom, 'http://localhost/MyService.php');

                foreach ($nestedTypeNames as $nestedTypeName => $typeName) {
                    $nodes = $xpath->query('//wsdl:types/xsd:schema/xsd:complexType[@name="'.$nestedTypeName.'"]');
                    expect($nodes->length)->toBe(1);

                    $nodes = $xpath->query('xsd:sequence/xsd:element', $nodes->item(0));
                    expect($nodes->length)->toBe(1);
                    expect($nodes->item(0)->getAttribute('name'))->toBe('item');
                    expect($nodes->item(0)->getAttribute('minOccurs'))->toBe('0');
                    expect($nodes->item(0)->getAttribute('maxOccurs'))->toBe('unbounded');
                    expect($nodes->item(0)->getAttribute('type'))->toBe($typeName);
                }

                assertDocumentNodesHaveNamespaces($dom);
            }
        });

        test('adds complex type definition for single object', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeSequence();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act
            $return = $wsdl->addComplexType(SequenceTest::class);

            // Assert
            expect($return)->toBe('tns:SequenceTest');

            $dom = $wsdl->toDomDocument();
            $xpath = registerWsdlNamespaces($dom, 'http://localhost/MyService.php');

            $nodes = $xpath->query('//xsd:complexType[@name="SequenceTest"]');
            expect($nodes->length)->toBe(1);

            $nodes = $xpath->query('xsd:all/xsd:element', $nodes->item(0));
            expect($nodes->length)->toBe(1);
            expect($nodes->item(0)->getAttribute('name'))->toBe('var');
            expect($nodes->item(0)->getAttribute('type'))->toBe('xsd:int');

            assertDocumentNodesHaveNamespaces($dom);
        });

        test('creates array definitions for complex objects with nested arrays', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeSequence();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act
            $return = $wsdl->addComplexType('\Tests\Fixtures\ComplexTypeA[]');

            // Assert
            expect($return)->toBe('tns:ArrayOfComplexTypeA');

            $dom = $wsdl->toDomDocument();
            $xpath = registerWsdlNamespaces($dom, 'http://localhost/MyService.php');

            // Verify ComplexTypeA definition
            $nodes = $xpath->query('//wsdl:types/xsd:schema/xsd:complexType[@name="ComplexTypeA"]');
            expect($nodes->length)->toBe(1);

            $nodes = $xpath->query('xsd:all/xsd:element', $nodes->item(0));
            expect($nodes->length)->toBe(1);
            expect($nodes->item(0)->getAttribute('name'))->toBe('baz');
            expect($nodes->item(0)->getAttribute('type'))->toBe('tns:ArrayOfComplexTypeB');

            // Verify ComplexTypeB definition
            $nodes = $xpath->query('//wsdl:types/xsd:schema/xsd:complexType[@name="ComplexTypeB"]');
            expect($nodes->length)->toBe(1);

            foreach (['bar' => 'xsd:string', 'foo' => 'xsd:string'] as $name => $type) {
                $node = $xpath->query('xsd:all/xsd:element[@name="'.$name.'"]', $nodes->item(0));
                expect($node->item(0)->getAttribute('name'))->toBe($name);
                expect($node->item(0)->getAttribute('type'))->toBe($type);
                expect($node->item(0)->getAttribute('nillable'))->toBe('true');
            }

            // Verify array type definitions
            foreach (
                [
                    'ArrayOfComplexTypeB' => 'ComplexTypeB',
                    'ArrayOfComplexTypeA' => 'ComplexTypeA',
                ] as $arrayTypeName => $typeName
            ) {
                $nodes = $xpath->query(
                    '//wsdl:types/xsd:schema/xsd:complexType[@name="'.$arrayTypeName.'"]',
                );
                expect($nodes->length)->toBe(1);

                $nodes = $xpath->query('xsd:sequence/xsd:element', $nodes->item(0));
                expect($nodes->length)->toBe(1);
                expect($nodes->item(0)->getAttribute('name'))->toBe('item');
                expect($nodes->item(0)->getAttribute('type'))->toBe('tns:'.$typeName);
                expect($nodes->item(0)->getAttribute('minOccurs'))->toBe('0');
                expect($nodes->item(0)->getAttribute('maxOccurs'))->toBe('unbounded');
            }

            assertDocumentNodesHaveNamespaces($dom);
        });
    });

    describe('Sad Paths', function (): void {
        test('throws exception when adding array of non-existent class', function (): void {
            // Arrange
            $strategy = new ArrayOfTypeSequence();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act & Assert
            expect(fn () => $wsdl->addComplexType('Tests\Fixtures\Wsdl\UnknownClass[]'))
                ->toThrow(InvalidArgumentException::class, 'Cannot add a complex type');
        });
    });
});

describe('Composite Strategy', function (): void {
    describe('Happy Paths', function (): void {
        test('connects specific types to different strategies via API', function (): void {
            // Arrange
            $strategy = new Composite([], new ArrayOfTypeSequence());
            $strategy->connectTypeToStrategy('Book', new ArrayOfTypeComplex());

            // Act
            $bookStrategy = $strategy->getStrategyOfType('Book');
            $cookieStrategy = $strategy->getStrategyOfType('Cookie');

            // Assert
            expect($bookStrategy)->toBeInstanceOf(ArrayOfTypeComplex::class);
            expect($cookieStrategy)->toBeInstanceOf(ArrayOfTypeSequence::class);
        });

        test('initializes type mappings via constructor', function (): void {
            // Arrange
            $typeMap = ['Book' => ArrayOfTypeComplex::class];
            $strategy = new Composite($typeMap, new ArrayOfTypeSequence());

            // Act
            $bookStrategy = $strategy->getStrategyOfType('Book');
            $cookieStrategy = $strategy->getStrategyOfType('Cookie');

            // Assert
            expect($bookStrategy)->toBeInstanceOf(ArrayOfTypeComplex::class);
            expect($cookieStrategy)->toBeInstanceOf(ArrayOfTypeSequence::class);
        });

        test('delegates complex type additions to appropriate sub-strategies', function (): void {
            // Arrange
            $strategy = new Composite([], new AnyType());
            $strategy->connectTypeToStrategy(Book::class, new ArrayOfTypeComplex());
            $strategy->connectTypeToStrategy(Cookie::class, new DefaultComplexType());

            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act
            $bookType = $wsdl->addComplexType(Book::class);
            $cookieType = $wsdl->addComplexType(Cookie::class);
            $anythingType = $wsdl->addComplexType(Anything::class);

            // Assert
            expect($bookType)->toBe('tns:Book');
            expect($cookieType)->toBe('tns:Cookie');
            expect($anythingType)->toBe('xsd:anyType');

            $dom = $wsdl->toDomDocument();
            assertDocumentNodesHaveNamespaces($dom);
        });

        test('returns default strategy instance', function (): void {
            // Arrange
            $strategyClass = AnyType::class;
            $strategy = new Composite([], $strategyClass);

            // Act & Assert
            expect(get_class($strategy->getDefaultStrategy()))->toBe($strategyClass);
        });
    });

    describe('Sad Paths', function (): void {
        test('throws TypeError when connecting invalid type to strategy', function (): void {
            // Arrange
            $strategy = new Composite();

            // Act & Assert - TypeError is thrown due to strict type hint (string $type)
            expect(fn () => $strategy->connectTypeToStrategy([], 'strategy'))
                ->toThrow(TypeError::class);
        });

        test('throws exception when retrieving strategy with invalid strategy definition', function (): void {
            // Arrange
            $strategy = new Composite([], 'invalid');
            $strategy->connectTypeToStrategy('Book', 'strategy');

            // Act & Assert
            expect(fn () => $strategy->getStrategyOfType('Book'))
                ->toThrow(InvalidArgumentException::class, 'Strategy for Complex Type "Book" is not a valid strategy');
        });

        test('throws exception when default strategy is invalid', function (): void {
            // Arrange
            $strategy = new Composite([], 'invalid');
            $strategy->connectTypeToStrategy('Book', 'strategy');

            // Act & Assert
            expect(fn () => $strategy->getStrategyOfType('Anything'))
                ->toThrow(InvalidArgumentException::class, 'Default Strategy for Complex Types is not a valid strategy object');
        });

        test('throws exception when adding complex type without context', function (): void {
            // Arrange
            $strategy = new Composite();

            // Act & Assert
            expect(fn () => $strategy->addComplexType('Test'))
                ->toThrow(InvalidArgumentException::class, 'Cannot add complex type "Test"');
        });
    });
});

describe('DefaultComplexType Strategy', function (): void {
    describe('Happy Paths', function (): void {
        test('discovers only public properties when building complex type', function (): void {
            // Arrange
            $strategy = new DefaultComplexType();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act
            $wsdl->addComplexType(PublicPrivateProtected::class);

            // Assert
            $dom = $wsdl->toDomDocument();
            $xpath = registerWsdlNamespaces($dom, 'http://localhost/MyService.php');

            $protectedNodes = $xpath->query('//xsd:element[@name="'.PublicPrivateProtected::PROTECTED_VAR_NAME.'"]');
            expect($protectedNodes->length)->toBe(0);

            $privateNodes = $xpath->query('//xsd:element[@name="'.PublicPrivateProtected::PRIVATE_VAR_NAME.'"]');
            expect($privateNodes->length)->toBe(0);

            assertDocumentNodesHaveNamespaces($dom);
        });

        test('handles duplicate class additions without creating duplicate definitions', function (): void {
            // Arrange
            $strategy = new DefaultComplexType();
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act
            $wsdl->addComplexType(WsdlTestClass::class);
            $wsdl->addComplexType(WsdlTestClass::class);

            // Assert
            $dom = $wsdl->toDomDocument();
            $xpath = registerWsdlNamespaces($dom, 'http://localhost/MyService.php');

            $nodes = $xpath->query('//xsd:complexType[@name="WsdlTestClass"]');
            expect($nodes->length)->toBe(1);

            assertDocumentNodesHaveNamespaces($dom);
        });

        test('invokes documentation strategy for properties and complex types', function (): void {
            // Arrange
            $documentation = Mockery::mock(DocumentationStrategyInterface::class);
            $documentation->shouldReceive('getPropertyDocumentation')
                ->with(Mockery::type(ReflectionProperty::class))
                ->times(2)
                ->andReturn('Property');
            $documentation->shouldReceive('getComplexTypeDocumentation')
                ->with(Mockery::type(ReflectionClass::class))
                ->once()
                ->andReturn('Complex type');

            $strategy = new DefaultComplexType();
            $strategy->setDocumentationStrategy($documentation);
            $wsdl = new Wsdl('MyService', 'http://localhost/MyService.php', $strategy);

            // Act
            $wsdl->addComplexType(WsdlTestClass::class);

            // Assert - expectations are verified by Mockery
        });
    });
});
