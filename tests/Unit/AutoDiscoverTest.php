<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\AutoDiscover;
use Cline\Soap\AutoDiscover\DiscoveryStrategy\ReflectionDiscovery;
use Cline\Soap\Exception\InvalidArgumentException as SoapInvalidArgumentException;
use Cline\Soap\Exception\RuntimeException;
use Cline\Soap\Wsdl;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ComplexTypeStrategyInterface;
use Tests\Fixtures\AutoDiscoverTestClass2;
use Tests\Fixtures\MyService;
use Tests\Fixtures\MyServiceSequence;
use Tests\Fixtures\NoReturnType;
use Tests\Fixtures\Recursion;
use Tests\Fixtures\Test;
use Tests\Fixtures\TestFixingMultiplePrototypes;
use Uri\Rfc3986\Uri;

beforeEach(function (): void {
    skipIfSoapNotLoaded();

    $this->server = new AutoDiscover();
    $this->defaultServiceName = 'MyService';
    $this->defaultServiceUri = 'http://localhost/MyService.php';
    $this->server->setUri($this->defaultServiceUri);
    $this->server->setServiceName($this->defaultServiceName);

    $this->dom = null;
    $this->xpath = null;
});

// Helper function to bind WSDL for XPath testing
function bindWsdl(Wsdl $wsdl, ?string $documentNamespace = null): void
{
    test()->dom = new DOMDocument();
    test()->dom->formatOutput = true;
    test()->dom->preserveWhiteSpace = false;
    test()->dom->loadXML($wsdl->toXML());

    if (empty($documentNamespace)) {
        $documentNamespace = test()->defaultServiceUri;
    }

    test()->xpath = registerWsdlNamespaces(test()->dom, $documentNamespace);
}

// Helper function to assert specific node number in XPath
function assertSpecificNodeNumberInXPath(int $n, string $xpath, ?string $msg = null): DOMNodeList
{
    $nodes = test()->xpath->query($xpath);

    if (!$nodes instanceof DOMNodeList) {
        throw new \RuntimeException('Nodes not found. Invalid XPath expression?');
    }

    expect($nodes->length)->toBe($n, $msg."\nXPath: ".$xpath);

    return $nodes;
}

// Helper function to assert attributes of nodes
function assertAttributesOfNodes(array $attributes, DOMNodeList $nodeList): void
{
    $keys = array_keys($attributes);
    $c = count($attributes);

    foreach ($nodeList as $node) {
        for ($i = 0; $i < $c; ++$i) {
            expect($node->getAttribute($keys[$i]))
                ->toBe($attributes[$keys[$i]], 'Invalid attribute value.');
        }
    }
}

// Helper function to validate WSDL
function assertValidWSDL(DOMDocument $dom): void
{
    // Save to temporary file for validation
    $file = fixturesPath('validate.wsdl');

    if (file_exists($file)) {
        unlink($file);
    }

    $dom->save($file);
    $dom = new DOMDocument();
    $dom->load($file);

    $result = $dom->schemaValidate(fixturesPath('schemas/wsdl.xsd'));
    unlink($file);

    expect($result)->toBeTrue('WSDL Did not validate');
}

describe('AutoDiscover', function (): void {
    describe('Constructor', function (): void {
        test('constructs AutoDiscover with URI as string', function (): void {
            $server = new AutoDiscover(null, 'http://example.com/service.php');

            expect($server->getUri()->toString())->toBe('http://example.com/service.php');
        });

        test('constructs AutoDiscover with URI as Uri instance', function (): void {
            $server = new AutoDiscover(null, new Uri('http://example.com/service.php'));

            expect($server->getUri()->toString())->toBe('http://example.com/service.php');
        });

        test('constructs AutoDiscover with URI containing ampersands encoded', function (): void {
            $server = new AutoDiscover(null, 'http://example.com/?a=b&amp;b=c');

            expect($server->getUri()->toString())->toBe('http://example.com/?a=b&amp;b=c');
        });

        test('constructs AutoDiscover with URI containing ampersands unencoded', function (): void {
            $server = new AutoDiscover(null, 'http://example.com/?a=b&b=c');

            expect($server->getUri()->toString())->toBe('http://example.com/?a=b&amp;b=c');
        });

        test('constructs AutoDiscover with URN namespace', function (): void {
            $server = new AutoDiscover(null, 'urn:acme:servicenamespace');

            expect($server->getUri()->toString())->toBe('urn:acme:servicenamespace');
        });

        test('constructs AutoDiscover with ArrayOfTypeComplex strategy', function (): void {
            $strategy = new ArrayOfTypeComplex();
            $server = new AutoDiscover($strategy);

            $server->addFunction('\Tests\Fixtures\TestFunc');
            $server->setServiceName('TestService');
            $server->setUri('http://example.com');
            $wsdl = $server->generate();

            expect(get_class($wsdl->getComplexTypeStrategy()))
                ->toBe($strategy::class);
        });

        test('constructs AutoDiscover with ArrayOfTypeSequence strategy', function (): void {
            $strategy = new ArrayOfTypeSequence();
            $server = new AutoDiscover($strategy);

            $server->addFunction('\Tests\Fixtures\TestFunc');
            $server->setServiceName('TestService');
            $server->setUri('http://example.com');
            $wsdl = $server->generate();

            expect(get_class($wsdl->getComplexTypeStrategy()))
                ->toBe($strategy::class);
        });

        test('constructs AutoDiscover with custom WSDL class', function (): void {
            $server = new AutoDiscover(null, null, Wsdl::class);

            $server->addFunction('\Tests\Fixtures\TestFunc');
            $server->setServiceName('TestService');
            $server->setUri('http://example.com');
            $wsdl = $server->generate();

            expect(mb_trim($wsdl::class, '\\'))->toBe(Wsdl::class);
            expect(mb_trim($server->getWsdlClass(), '\\'))->toBe(Wsdl::class);
        });
    });

    describe('Discovery Strategy', function (): void {
        test('returns ReflectionDiscovery as default discovery strategy', function (): void {
            $server = new AutoDiscover();

            expect(get_class($server->getDiscoveryStrategy()))
                ->toBe(ReflectionDiscovery::class);
        });
    });

    describe('Service Name', function (): void {
        test('sets service name successfully with valid name', function (): void {
            $this->server->setServiceName('MyServiceName123');
            bindWsdl($this->server->generate());

            assertSpecificNodeNumberInXPath(
                1,
                '/wsdl:definitions[@name="MyServiceName123"]',
            );
        });

        test('throws exception when service name starts with number', function (): void {
            expect(fn () => $this->server->setServiceName('1MyServiceName123'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception when service name contains dollar sign', function (): void {
            expect(fn () => $this->server->setServiceName('$MyServiceName123'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception when service name contains exclamation mark', function (): void {
            expect(fn () => $this->server->setServiceName('!MyServiceName123'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception when service name contains ampersand', function (): void {
            expect(fn () => $this->server->setServiceName('&MyServiceName123'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception when service name contains parenthesis', function (): void {
            expect(fn () => $this->server->setServiceName('(MyServiceName123'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception when service name contains backslash', function (): void {
            expect(fn () => $this->server->setServiceName('\\MyServiceName123'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('gets service name from class when setClass is used', function (): void {
            $server = new AutoDiscover();
            $server->setClass(Test::class);

            expect($server->getServiceName())->toBe('Test');
        });

        test('throws RuntimeException when getting service name with addFunction', function (): void {
            $server = new AutoDiscover();
            $server->addFunction('\Tests\Fixtures\TestFunc');

            expect(fn () => $server->getServiceName())
                ->toThrow(RuntimeException::class);
        });
    });

    describe('URI Configuration', function (): void {
        test('throws exception when setting URI with whitespace only', function (): void {
            $server = new AutoDiscover();

            expect(fn () => $server->setUri(' '))
                ->toThrow(SoapInvalidArgumentException::class);
        });

        test('throws exception when getting URI before it is set', function (): void {
            $server = new AutoDiscover();

            expect(fn () => $server->getUri())
                ->toThrow(RuntimeException::class);
        });

        test('throws exception when setting URI with non-string non-Uri type', function (): void {
            $server = new AutoDiscover();

            expect(fn () => $server->setUri(['bogus']))
                ->toThrow(SoapInvalidArgumentException::class)
                ->and(fn () => $server->setUri(['bogus']))
                ->toThrow(SoapInvalidArgumentException::class, 'Argument to \Cline\Soap\AutoDiscover::setUri should be string or \Uri\Rfc3986\Uri instance.');
        });
    });

    describe('Class Map', function (): void {
        test('sets and gets class map successfully', function (): void {
            $classMap = [
                'TestClass' => 'test_class',
            ];

            $this->server->setClassMap($classMap);

            expect($this->server->getClassMap())->toBe($classMap);
        });
    });

    describe('WSDL Class Configuration', function (): void {
        test('throws exception when setting WSDL class to non-string value', function (): void {
            $server = new AutoDiscover();

            expect(fn () => $server->setWsdlClass(
                new stdClass(),
            ))
                ->toThrow(SoapInvalidArgumentException::class);
        });
    });

    describe('Class Discovery', function (): void {
        test('generates WSDL from class with RPC style', function (): void {
            $this->server->setClass(Test::class);
            bindWsdl($this->server->generate());

            // Check schema definition
            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:types/xsd:schema[@targetNamespace="'.$this->defaultServiceUri.'"]',
                'Invalid schema definition',
            );

            // Check all 4 operations
            for ($i = 1; $i <= 4; ++$i) {
                assertSpecificNodeNumberInXPath(
                    1,
                    '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="testFunc'.$i.'"]',
                    'Invalid func'.$i.' operation definition',
                );
                assertSpecificNodeNumberInXPath(
                    1,
                    '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="testFunc'.$i.'"]/wsdl:documentation',
                    'Invalid func'.$i.' port definition - documentation node',
                );
                assertSpecificNodeNumberInXPath(
                    1,
                    '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="testFunc'.$i.'"]/wsdl:input[@message="tns:testFunc'.$i.'In"]',
                    'Invalid func'.$i.' port definition - input node',
                );
                assertSpecificNodeNumberInXPath(
                    1,
                    '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="testFunc'.$i.'"]/wsdl:output[@message="tns:testFunc'.$i.'Out"]',
                    'Invalid func'.$i.' port definition - output node',
                );
            }

            // Check binding
            $nodes = assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:binding[@name="MyServiceBinding"]',
                'Invalid service binding definition',
            );
            expect($nodes->item(0)->getAttribute('type'))
                ->toBe('tns:MyServicePort', 'Invalid type attribute value in service binding definition');

            $nodes = assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:binding[@name="MyServiceBinding"]/soap:binding',
                'Invalid service binding definition',
            );
            expect($nodes->item(0)->getAttribute('style'))
                ->toBe('rpc', 'Invalid style attribute value in service binding definition');
            expect($nodes->item(0)->getAttribute('transport'))
                ->toBe('http://schemas.xmlsoap.org/soap/http', 'Invalid transport attribute value in service binding definition');

            // Check service
            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:service[@name="MyServiceService"]',
                'Invalid service definition',
            );
            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:service[@name="MyServiceService"]/wsdl:port[@name="MyServicePort" and @binding="tns:MyServiceBinding"]',
                'Invalid service port definition',
            );

            // Check messages
            $nodes = assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:message[@name="testFunc1In"]',
                'Invalid message definition',
            );
            expect($nodes->item(0)->hasChildNodes())->toBeFalse();

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:message[@name="testFunc2In"]/wsdl:part[@name="who" and @type="xsd:string"]',
                'Invalid message definition',
            );

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });

        test('generates WSDL from class with document/literal style', function (): void {
            $this->server->setBindingStyle([
                'style' => 'document',
                'transport' => $this->defaultServiceUri,
            ]);
            $this->server->setOperationBodyStyle([
                'use' => 'literal',
                'namespace' => $this->defaultServiceUri,
            ]);
            $this->server->setClass(Test::class);

            bindWsdl($this->server->generate());

            // Check element definitions for document/literal style
            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:types/xsd:schema/xsd:element[@name="testFunc1"]',
                'Missing test func1 definition',
            );
            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:types/xsd:schema/xsd:element[@name="testFunc1"]/xsd:complexType',
                'Missing test func1 type definition',
            );
            assertSpecificNodeNumberInXPath(
                0,
                '//wsdl:types/xsd:schema/xsd:element[@name="testFunc1"]/xsd:complexType/*',
                'Test func1 does not have children',
            );

            $nodes = assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:types/xsd:schema/xsd:element[@name="testFunc1Response"]/xsd:complexType/xsd:sequence/xsd:element',
                'Test func1 return element is invalid',
            );
            assertAttributesOfNodes([
                'name' => 'testFunc1Result',
                'type' => 'xsd:string',
            ], $nodes);

            // Check binding style
            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/soap:binding[@style="document" and @transport="'.$this->defaultServiceUri.'"]',
                'Missing service binding transport definition',
            );

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });

        test('generates WSDL with return part compatibility mode', function (): void {
            $this->server->setClass(Test::class);
            bindWsdl($this->server->generate());

            for ($i = 1; $i <= 4; ++$i) {
                assertSpecificNodeNumberInXPath(
                    1,
                    '//wsdl:message[@name="testFunc'.$i.'Out"]/wsdl:part[@name="return"]',
                );
            }

            assertValidWSDL($this->dom);
        });
    });

    describe('Function Discovery', function (): void {
        test('throws exception when adding invalid function name', function (): void {
            expect(fn () => $this->server->addFunction('InvalidFunction'))
                ->toThrow(SoapInvalidArgumentException::class);
        });

        test('throws exception when adding integer as function', function (): void {
            expect(fn () => $this->server->addFunction(1))
                ->toThrow(SoapInvalidArgumentException::class);
        });

        test('throws exception when adding array of integers as function', function (): void {
            expect(fn () => $this->server->addFunction([1, 2]))
                ->toThrow(SoapInvalidArgumentException::class);
        });

        test('adds single function and generates WSDL with RPC style', function (): void {
            $this->server->addFunction('\Tests\Fixtures\TestFunc');
            bindWsdl($this->server->generate());

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]',
                'Missing service port definition',
            );
            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]/wsdl:documentation',
                'Missing service port definition documentation',
            );

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/soap:binding[@style="rpc" and @transport="http://schemas.xmlsoap.org/soap/http"]',
                'Missing service binding transport definition',
            );

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:message[@name="TestFuncIn"]/wsdl:part[@name="who" and @type="xsd:string"]',
                'Missing test testFunc input message definition',
            );
            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:message[@name="TestFuncOut"]/wsdl:part[@name="return" and @type="xsd:string"]',
                'Missing test testFunc input message definition',
            );

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });

        test('adds single function and generates WSDL with document/literal style', function (): void {
            $this->server->setBindingStyle([
                'style' => 'document',
                'transport' => $this->defaultServiceUri,
            ]);
            $this->server->setOperationBodyStyle([
                'use' => 'literal',
                'namespace' => $this->defaultServiceUri,
            ]);
            $this->server->addFunction('\Tests\Fixtures\TestFunc');
            bindWsdl($this->server->generate());

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:types/xsd:schema[@targetNamespace="'.$this->defaultServiceUri.'"]',
                'Missing service port definition',
            );

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:types/xsd:schema[@targetNamespace="'.$this->defaultServiceUri.'"]/xsd:element[@name="TestFunc"]/xsd:complexType/xsd:sequence/xsd:element[@name="who" and @type="xsd:string"]',
                'Missing complex type definition',
            );
            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:types/xsd:schema[@targetNamespace="'.$this->defaultServiceUri.'"]/xsd:element[@name="TestFuncResponse"]/xsd:complexType/xsd:sequence/xsd:element[@name="TestFuncResult" and @type="xsd:string"]',
                'Missing complex type definition',
            );

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:message[@name="TestFuncIn"]/wsdl:part[@name="parameters" and @element="tns:TestFunc"]',
                'Missing test testFunc input message definition',
            );
            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:message[@name="TestFuncOut"]/wsdl:part[@name="parameters" and @element="tns:TestFuncResponse"]',
                'Missing test testFunc input message definition',
            );

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });

        test('generates WSDL with return name compatibility mode for single function', function (): void {
            $this->server->addFunction('\Tests\Fixtures\TestFunc');
            bindWsdl($this->server->generate());

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:message[@name="TestFuncOut"]/wsdl:part[@name="return" and @type="xsd:string"]',
                'Missing test testFunc input message definition',
            );

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });

        test('adds multiple functions and generates WSDL', function (): void {
            $this->server->addFunction('\Tests\Fixtures\TestFunc');
            $this->server->addFunction('\Tests\Fixtures\TestFunc2');
            $this->server->addFunction('\Tests\Fixtures\TestFunc3');
            $this->server->addFunction('\Tests\Fixtures\TestFunc4');
            $this->server->addFunction('\Tests\Fixtures\TestFunc5');
            $this->server->addFunction('\Tests\Fixtures\TestFunc6');
            $this->server->addFunction('\Tests\Fixtures\TestFunc7');
            $this->server->addFunction('\Tests\Fixtures\TestFunc9');

            bindWsdl($this->server->generate());

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:types/xsd:schema[@targetNamespace="'.$this->defaultServiceUri.'"]',
                'Missing service port definition',
            );

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/soap:binding[@style="rpc" and @transport="http://schemas.xmlsoap.org/soap/http"]',
                'Missing service port definition',
            );

            foreach (['', 2, 3, 4, 5, 6, 7, 9] as $i) {
                assertSpecificNodeNumberInXPath(
                    1,
                    '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc'.$i.'"]',
                    'Missing service port definition for TestFunc'.$i,
                );
                assertSpecificNodeNumberInXPath(
                    1,
                    '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc'.$i.'"]/wsdl:documentation',
                    'Missing service port definition documentation for TestFunc'.$i,
                );
            }

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });
    });

    describe('URI Changes', function (): void {
        test('changes WSDL URI in constructor', function (): void {
            $this->server->addFunction('\Tests\Fixtures\TestFunc');
            $this->server->setUri('http://example.com/service.php');
            bindWsdl($this->server->generate());

            expect($this->dom->documentElement->getAttribute('targetNamespace'))
                ->toBe('http://example.com/service.php');
            expect($this->dom->saveXML())
                ->not->toContain($this->defaultServiceUri);

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });

        test('changes WSDL URI after generation', function (): void {
            $this->server->addFunction('\Tests\Fixtures\TestFunc');
            $wsdl = $this->server->generate();
            $wsdl->setUri('http://example.com/service.php');

            expect($wsdl->toDomDocument()->documentElement->getAttribute('targetNamespace'))
                ->toBe('http://example.com/service.php');

            assertValidWSDL($wsdl->toDomDocument());
        });
    });

    describe('Complex Types', function (): void {
        test('generates WSDL with class having methods with multiple default parameter values', function (): void {
            $this->server->setClass(TestFixingMultiplePrototypes::class);
            bindWsdl($this->server->generate());

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:message[@name="testFuncIn"]',
            );
            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:message[@name="testFuncOut"]',
            );

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });

        test('recognizes complex types used multiple times only once with ArrayOfTypeComplex', function (): void {
            $this->server->setComplexTypeStrategy(
                new ArrayOfTypeComplex(),
            );
            $this->server->setClass(AutoDiscoverTestClass2::class);
            bindWsdl($this->server->generate());

            assertSpecificNodeNumberInXPath(
                1,
                '//xsd:attribute[@wsdl:arrayType="tns:AutoDiscoverTestClass1[]"]',
                'Definition of TestClass1 has to occur once.',
            );
            assertSpecificNodeNumberInXPath(
                1,
                '//xsd:complexType[@name="AutoDiscoverTestClass1"]',
                'AutoDiscoverTestClass1 has to be defined once.',
            );
            assertSpecificNodeNumberInXPath(
                1,
                '//xsd:complexType[@name="ArrayOfAutoDiscoverTestClass1"]',
                'AutoDiscoverTestClass1 should be defined once.',
            );

            $nodes = assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:part[@name="test" and @type="tns:AutoDiscoverTestClass1"]',
                'AutoDiscoverTestClass1 appears once or more than once in the message parts section.',
            );
            expect($nodes->length)->toBeGreaterThanOrEqual(1);

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });

        test('returns same array of objects response on different methods when using ArrayOfTypeComplex', function (): void {
            $this->server->setComplexTypeStrategy(
                new ArrayOfTypeComplex(),
            );
            $this->server->setClass(MyService::class);
            bindWsdl($this->server->generate());

            assertSpecificNodeNumberInXPath(
                1,
                '//xsd:complexType[@name="ArrayOfMyResponse"]',
            );
            assertSpecificNodeNumberInXPath(
                0,
                '//wsdl:part[@type="tns:My_Response[]"]',
            );

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });

        test('returns same array of objects response on different methods when using ArrayOfTypeSequence', function (): void {
            $this->server->setComplexTypeStrategy(
                new ArrayOfTypeSequence(),
            );
            $this->server->setClass(MyServiceSequence::class);
            bindWsdl($this->server->generate());

            assertSpecificNodeNumberInXPath(
                1,
                '//xsd:complexType[@name="ArrayOfString"]',
            );
            assertSpecificNodeNumberInXPath(
                1,
                '//xsd:complexType[@name="ArrayOfArrayOfString"]',
            );
            assertSpecificNodeNumberInXPath(
                1,
                '//xsd:complexType[@name="ArrayOfArrayOfArrayOfString"]',
            );

            expect($this->dom->saveXML())->not->toContain('tns:string[]');

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });
    });

    describe('One-Way Operations', function (): void {
        test('generates one-way operation when method has no return type in class', function (): void {
            $this->server->setClass(NoReturnType::class);
            bindWsdl($this->server->generate());

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType/wsdl:operation[@name="pushOneWay"]/wsdl:input',
            );
            assertSpecificNodeNumberInXPath(
                0,
                '//wsdl:portType/wsdl:operation[@name="pushOneWay"]/wsdl:output',
            );

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });

        test('generates one-way operation when function has no return type', function (): void {
            $this->server->addFunction('\Tests\Fixtures\OneWay');
            bindWsdl($this->server->generate());

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType/wsdl:operation[@name="OneWay"]/wsdl:input',
            );
            assertSpecificNodeNumberInXPath(
                0,
                '//wsdl:portType/wsdl:operation[@name="OneWay"]/wsdl:output',
            );

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });
    });

    describe('Recursive Dependencies', function (): void {
        test('handles recursive WSDL dependencies', function (): void {
            $this->server->setComplexTypeStrategy(
                new ArrayOfTypeSequence(),
            );
            $this->server->setClass(Recursion::class);

            bindWsdl($this->server->generate());

            assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:types/xsd:schema/xsd:complexType[@name="Recursion"]/xsd:all/xsd:element[@name="recursion" and @type="tns:Recursion"]',
            );

            assertValidWSDL($this->dom);
            assertDocumentNodesHaveNamespaces($this->dom);
        });
    });

    describe('WSDL Output', function (): void {
        test('handles WSDL output when calling handle method', function (): void {
            $scriptUri = 'http://localhost/MyService.php';

            $this->server->setClass(Test::class);

            ob_start();
            $this->server->handle();
            $actualWsdl = ob_get_clean();

            expect($actualWsdl)->not->toBeEmpty('WSDL content was not outputted.');
            expect($actualWsdl)->toContain($scriptUri, 'Script URL was not found in WSDL content.');
        })->skip('Requires output buffering in separate process');
    });
});
