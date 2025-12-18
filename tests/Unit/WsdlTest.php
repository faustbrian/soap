<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\Exception\RuntimeException;
use Cline\Soap\Wsdl;
use Cline\Soap\Wsdl\ComplexTypeStrategy\AnyType;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence;
use Cline\Soap\Wsdl\ComplexTypeStrategy\DefaultComplexType;
use Tests\Fixtures\WsdlTestClass;
use Uri\Rfc3986\Uri;

beforeEach(function (): void {
    skipIfSoapNotLoaded();

    $this->defaultServiceName = 'MyService';
    $this->defaultServiceUri = 'http://localhost/MyService.php';
    $this->strategy = new DefaultComplexType();
    $this->wsdl = new Wsdl($this->defaultServiceName, $this->defaultServiceUri, $this->strategy);
    $this->dom = $this->wsdl->toDomDocument();
    $this->xpath = registerWsdlNamespaces($this->dom, $this->defaultServiceUri);
});

describe('Wsdl', function (): void {
    describe('Happy Paths', function (): void {
        test('creates WSDL with correct namespace URIs and attributes', function (): void {
            // Assert
            expect($this->dom->lookupNamespaceUri(null))->toBe(Wsdl::WSDL_NS_URI)
                ->and($this->dom->lookupNamespaceUri('soap'))->toBe(Wsdl::SOAP_11_NS_URI)
                ->and($this->dom->lookupNamespaceUri('soap12'))->toBe(Wsdl::SOAP_12_NS_URI)
                ->and($this->dom->lookupNamespaceUri('tns'))->toBe($this->defaultServiceUri)
                ->and($this->dom->lookupNamespaceUri('xsd'))->toBe(Wsdl::XSD_NS_URI)
                ->and($this->dom->lookupNamespaceUri('soap-enc'))->toBe(Wsdl::SOAP_ENC_URI)
                ->and($this->dom->lookupNamespaceUri('wsdl'))->toBe(Wsdl::WSDL_NS_URI)
                ->and($this->dom->documentElement->namespaceURI)->toBe(Wsdl::WSDL_NS_URI)
                ->and($this->dom->documentElement->getAttribute('name'))->toBe($this->defaultServiceName)
                ->and($this->dom->documentElement->getAttribute('targetNamespace'))->toBe($this->defaultServiceUri);

            assertDocumentNodesHaveNamespaces($this->dom);
        });

        test('setUri with string changes DOM document WSDL structure tns and targetNamespace attributes', function (string|Uri $uri, string $expectedUri): void {
            // Act
            if ($uri instanceof Uri) {
                $uri = $uri->toString();
            }
            $this->wsdl->setUri($uri);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            expect($this->dom->lookupNamespaceUri('tns'))->toBe($expectedUri)
                ->and($this->dom->documentElement->getAttribute('targetNamespace'))->toBe($expectedUri);
        })->with('uriTestingData');

        test('setUri with Uri object changes DOM document WSDL structure tns and targetNamespace attributes', function (string|Uri $uri, string $expectedUri): void {
            // Act
            $this->wsdl->setUri(
                new Uri($uri),
            );

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            expect($this->dom->lookupNamespaceUri('tns'))->toBe($expectedUri)
                ->and($this->dom->documentElement->getAttribute('targetNamespace'))->toBe($expectedUri);
        })->with('uriTestingData');

        test('constructs WSDL object with different URI', function (string|Uri $uri, string $expectedUri): void {
            // Act
            $wsdl = new Wsdl($this->defaultServiceName, $uri);
            $dom = $wsdl->toDomDocument();
            registerWsdlNamespaces($dom, $expectedUri);

            // Assert
            assertDocumentNodesHaveNamespaces($dom);
            expect($dom->lookupNamespaceUri('tns'))->toBe($expectedUri)
                ->and($dom->documentElement->getAttribute('targetNamespace'))->toBe($expectedUri);
        })->with('uriTestingData');

        test('addMessage creates message node with simple parameters', function (array $parameters): void {
            // Arrange
            $messageParts = [];

            foreach ($parameters as $i => $parameter) {
                $messageParts['parameter'.$i] = $this->wsdl->getType($parameter);
            }
            $messageName = 'myMessage';

            // Act
            $this->wsdl->addMessage($messageName, $messageParts);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $messageNodes = $this->xpath->query('//wsdl:definitions/wsdl:message');
            expect($messageNodes->length)->toBeGreaterThan(0)
                ->and($messageNodes->item(0)->getAttribute('name'))->toBe($messageName);

            foreach ($messageParts as $parameterName => $parameterType) {
                $part = $this->xpath->query('wsdl:part[@name="'.$parameterName.'"]', $messageNodes->item(0));
                expect($part->item(0)->getAttribute('type'))->toBe($parameterType);
            }
        })->with('addMessageData');

        test('addMessage creates message node with complex parameters', function (array $parameters): void {
            // Arrange
            $messageParts = [];

            foreach ($parameters as $i => $parameter) {
                $messageParts['parameter'.$i] = [
                    'type' => $this->wsdl->getType($parameter),
                    'name' => 'parameter'.$i,
                ];
            }
            $messageName = 'myMessage';

            // Act
            $this->wsdl->addMessage($messageName, $messageParts);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $messageNodes = $this->xpath->query('//wsdl:definitions/wsdl:message');
            expect($messageNodes->length)->toBeGreaterThan(0);

            foreach ($messageParts as $parameterName => $parameterDefinition) {
                $part = $this->xpath->query('wsdl:part[@name="'.$parameterName.'"]', $messageNodes->item(0));
                expect($part->item(0)->getAttribute('type'))->toBe($parameterDefinition['type'])
                    ->and($part->item(0)->getAttribute('name'))->toBe($parameterDefinition['name']);
            }
        })->with('addMessageData');

        test('addPortType creates portType node with correct name attribute', function (): void {
            // Arrange
            $portName = 'myPortType';

            // Act
            $this->wsdl->addPortType($portName);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $portTypeNodes = $this->xpath->query('//wsdl:definitions/wsdl:portType');
            expect($portTypeNodes->length)->toBeGreaterThan(0)
                ->and($portTypeNodes->item(0)->hasAttribute('name'))->toBeTrue()
                ->and($portTypeNodes->item(0)->getAttribute('name'))->toBe($portName);
        });

        test('addPortOperation creates operation node within portType', function (
            string $operationName,
            ?string $inputRequest = null,
            ?string $outputResponse = null,
            ?string $fail = null,
        ): void {
            // Arrange
            $portName = 'myPortType';
            $portType = $this->wsdl->addPortType($portName);

            // Act
            $this->wsdl->addPortOperation($portType, $operationName, $inputRequest, $outputResponse, $fail);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $portTypeNodes = $this->xpath->query('//wsdl:definitions/wsdl:portType[@name="'.$portName.'"]');
            expect($portTypeNodes->length)->toBeGreaterThan(0);

            $operationNodes = $this->xpath->query('wsdl:operation[@name="'.$operationName.'"]', $portTypeNodes->item(0));
            expect($operationNodes->length)->toBeGreaterThan(0);

            if (empty($inputRequest) && empty($outputResponse) && empty($fail)) {
                expect($operationNodes->item(0)->hasChildNodes())->toBeFalse();
            } else {
                expect($operationNodes->item(0)->hasChildNodes())->toBeTrue();
            }

            if (!empty($inputRequest)) {
                $inputNodes = $operationNodes->item(0)->getElementsByTagName('input');
                expect($inputNodes->item(0)->getAttribute('message'))->toBe($inputRequest);
            }

            if (!empty($outputResponse)) {
                $outputNodes = $operationNodes->item(0)->getElementsByTagName('output');
                expect($outputNodes->item(0)->getAttribute('message'))->toBe($outputResponse);
            }

            if (empty($fail)) {
                return;
            }

            $faultNodes = $operationNodes->item(0)->getElementsByTagName('fault');
            expect($faultNodes->item(0)->getAttribute('message'))->toBe($fail);
        })->with('addPortOperationData');

        test('addBinding creates binding node with correct attributes', function (): void {
            // Act
            $this->wsdl->addBinding('MyServiceBinding', 'myPortType');

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $bindingNodes = $this->xpath->query('//wsdl:definitions/wsdl:binding');
            expect($bindingNodes->length)->toBeGreaterThan(0)
                ->and($bindingNodes->item(0)->getAttribute('name'))->toBe('MyServiceBinding')
                ->and($bindingNodes->item(0)->getAttribute('type'))->toBe('myPortType');
        });

        test('addBindingOperation creates binding operation with input output and fault', function (
            string $operationName,
            ?string $input = null,
            ?string $inputEncoding = null,
            ?string $output = null,
            ?string $outputEncoding = null,
            ?string $fault = null,
            ?string $faultEncoding = null,
            ?string $faultName = null,
        ): void {
            // Arrange
            $binding = $this->wsdl->addBinding('MyServiceBinding', 'myPortType');

            $inputArray = [];

            if (!empty($input) && !empty($inputEncoding)) {
                $inputArray = ['use' => $input, 'encodingStyle' => $inputEncoding];
            }

            $outputArray = [];

            if (!empty($output) && !empty($outputEncoding)) {
                $outputArray = ['use' => $output, 'encodingStyle' => $outputEncoding];
            }

            $faultArray = [];

            if (!empty($fault) && !empty($faultEncoding) && !empty($faultName)) {
                $faultArray = ['use' => $fault, 'encodingStyle' => $faultEncoding, 'name' => $faultName];
            }

            // Act
            $this->wsdl->addBindingOperation($binding, $operationName, $inputArray, $outputArray, $faultArray);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $bindingNodes = $this->xpath->query('//wsdl:binding');
            expect($bindingNodes->length)->toBeGreaterThan(0)
                ->and($bindingNodes->item(0)->getAttribute('name'))->toBe('MyServiceBinding')
                ->and($bindingNodes->item(0)->getAttribute('type'))->toBe('myPortType');

            $operationNodes = $this->xpath->query('wsdl:operation[@name="'.$operationName.'"]', $bindingNodes->item(0));
            expect($operationNodes->length)->toBe(1);

            if (empty($inputArray) && empty($outputArray) && empty($faultArray)) {
                expect($operationNodes->item(0)->hasChildNodes())->toBeFalse();
            }

            foreach ([
                '//wsdl:input/soap:body' => $inputArray,
                '//wsdl:output/soap:body' => $outputArray,
                '//wsdl:fault' => $faultArray,
            ] as $query => $ar) {
                if (empty($ar)) {
                    continue;
                }

                $nodes = $this->xpath->query($query);
                expect($nodes->length)->toBeGreaterThan(0);

                foreach ($ar as $key => $val) {
                    expect($nodes->item(0)->getAttribute($key))->toBe($ar[$key]);
                }
            }
        })->with('addBindingOperationData');

        test('addSoapBinding creates soap:binding element with specified style', function (string $style): void {
            // Arrange
            $this->wsdl->addPortType('myPortType');
            $binding = $this->wsdl->addBinding('MyServiceBinding', 'myPortType');

            // Act
            $this->wsdl->addSoapBinding($binding, $style);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $nodes = $this->xpath->query('//soap:binding');
            expect($nodes->length)->toBeGreaterThan(0)
                ->and($nodes->item(0)->getAttribute('style'))->toBe($style);
        })->with('soapBindingStyleData');

        test('addSoapOperation creates soap:operation element with soapAction', function (string|Uri $operationUrl): void {
            // Arrange
            $this->wsdl->addPortType('myPortType');
            $binding = $this->wsdl->addBinding('MyServiceBinding', 'myPortType');
            $expectedUrl = $operationUrl instanceof Uri ? $operationUrl->toString() : $operationUrl;

            // Act
            $this->wsdl->addSoapOperation($binding, $operationUrl);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $node = $this->xpath->query('//soap:operation');
            expect($node->length)->toBeGreaterThan(0)
                ->and($node->item(0)->getAttribute('soapAction'))->toBe($expectedUrl);
        })->with('addSoapOperationData');

        test('addService creates service node with port and soap:address', function (string|Uri $serviceUrl): void {
            // Arrange
            $this->wsdl->addPortType('myPortType');
            $this->wsdl->addBinding('MyServiceBinding', 'myPortType');
            $expectedUrl = $serviceUrl instanceof Uri ? $serviceUrl->toString() : $serviceUrl;

            // Act
            $this->wsdl->addService('Service1', 'myPortType', 'MyServiceBinding', $serviceUrl);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $nodes = $this->xpath->query('//wsdl:service[@name="Service1"]/wsdl:port/soap:address');
            expect($nodes->length)->toBeGreaterThan(0)
                ->and($nodes->item(0)->getAttribute('location'))->toBe($expectedUrl);
        })->with('addServiceData');

        test('addBindingOperation properly encodes ampersands in URL', function (string $actualUrl, string $expectedUrl): void {
            // Arrange
            $this->wsdl->addPortType('myPortType');
            $binding = $this->wsdl->addBinding('MyServiceBinding', 'myPortType');

            // Act
            $this->wsdl->addBindingOperation(
                $binding,
                'operation1',
                ['use' => 'encoded', 'encodingStyle' => $actualUrl],
                ['use' => 'encoded', 'encodingStyle' => $actualUrl],
                ['name' => 'MyFault', 'use' => 'encoded', 'encodingStyle' => $actualUrl],
            );

            // Assert
            $nodes = $this->xpath->query(
                '//wsdl:binding[@type="myPortType" and @name="MyServiceBinding"]/wsdl:operation[@name="operation1"]/wsdl:input/soap:body',
            );
            expect($nodes->length)->toBeGreaterThanOrEqual(1)
                ->and($nodes->item(0)->getAttribute('encodingStyle'))->toBe($expectedUrl);
        })->with('ampersandInUrlData');

        test('addSoapOperation properly encodes ampersands in URL', function (string $actualUrl, string $expectedUrl): void {
            // Arrange
            $this->wsdl->addPortType('myPortType');
            $binding = $this->wsdl->addBinding('MyServiceBinding', 'myPortType');

            // Act
            $this->wsdl->addSoapOperation($binding, $actualUrl);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $nodes = $this->xpath->query('//wsdl:binding/soap:operation');
            expect($nodes->length)->toBeGreaterThanOrEqual(1)
                ->and($nodes->item(0)->getAttribute('soapAction'))->toBe($expectedUrl);
        })->with('ampersandInUrlData');

        test('addService properly encodes ampersands in URL', function (string $actualUrl, string $expectedUrl): void {
            // Arrange
            $this->wsdl->addPortType('myPortType');
            $this->wsdl->addBinding('MyServiceBinding', 'myPortType');

            // Act
            $this->wsdl->addService('Service1', 'myPortType', 'MyServiceBinding', $actualUrl);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $nodes = $this->xpath->query('//wsdl:port/soap:address');
            expect($nodes->length)->toBeGreaterThanOrEqual(1)
                ->and($nodes->item(0)->getAttribute('location'))->toBe($expectedUrl);
        })->with('ampersandInUrlData');

        test('addDocumentation adds documentation node to WSDL element', function (): void {
            // Arrange
            $doc = 'This is a description for Port Type node.';

            // Act
            $this->wsdl->addDocumentation($this->wsdl, $doc);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $nodes = $this->wsdl->toDomDocument()->childNodes;
            expect($nodes->length)->toBe(1)
                ->and($nodes->item(0)->nodeValue)->toBe($doc);
        });

        test('addDocumentation adds documentation node to specific element', function (): void {
            // Arrange
            $portType = $this->wsdl->addPortType('myPortType');
            $doc = 'This is a description for Port Type node.';

            // Act
            $this->wsdl->addDocumentation($portType, $doc);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $nodes = $this->xpath->query('//wsdl:portType[@name="myPortType"]/wsdl:documentation');
            expect($nodes->length)->toBe(1)
                ->and($nodes->item(0)->nodeValue)->toBe($doc);
        });

        test('addDocumentation inserts before existing child nodes', function (): void {
            // Arrange
            $messageParts = [
                'parameter1' => $this->wsdl->getType('int'),
                'parameter2' => $this->wsdl->getType('string'),
                'parameter3' => $this->wsdl->getType('mixed'),
            ];
            $message = $this->wsdl->addMessage('myMessage', $messageParts);

            // Act
            $this->wsdl->addDocumentation($message, 'foo');

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $nodes = $this->xpath->query('//wsdl:message[@name="myMessage"]/*[1]');
            expect($nodes->item(0)->nodeName)->toBe('documentation');
        });

        test('complex type documentation is added as xsd:annotation', function (): void {
            // Arrange
            $this->wsdl->addComplexType(WsdlTestClass::class);
            $nodes = $this->xpath->query('//xsd:complexType[@name="WsdlTestClass"]');

            // Act
            $this->wsdl->addDocumentation($nodes->item(0), 'documentation');

            // Assert
            $nodes = $this->xpath->query('//xsd:complexType[@name="WsdlTestClass"]/*[1]');
            expect($nodes->item(0)->nodeName)->toBe('xsd:annotation');

            $nodes = $this->xpath->query('//xsd:complexType[@name="WsdlTestClass"]/xsd:annotation/*[1]');
            expect($nodes->item(0)->nodeName)->toBe('xsd:documentation');
        });

        test('dump writes WSDL to file successfully', function (): void {
            // Arrange
            $file = tempnam(sys_get_temp_dir(), 'laminasunittest');

            // Act
            $dumpStatus = $this->wsdl->dump($file);
            $fileContent = file_get_contents($file);
            unlink($file);

            // Assert
            expect($dumpStatus)->toBeTrue();
            checkXMLContent($fileContent, $this);
        });

        test('dump outputs WSDL to stdout successfully', function (): void {
            // Act
            ob_start();
            $dumpStatus = $this->wsdl->dump();
            $screenContent = ob_get_clean();

            // Assert
            expect($dumpStatus)->toBeTrue();
            checkXMLContent($screenContent, $this);
        });

        test('getType maps PHP types to XSD types correctly', function (): void {
            // Assert
            expect($this->wsdl->getType('string'))->toBe('xsd:string')
                ->and($this->wsdl->getType('str'))->toBe('xsd:string')
                ->and($this->wsdl->getType('int'))->toBe('xsd:int')
                ->and($this->wsdl->getType('integer'))->toBe('xsd:int')
                ->and($this->wsdl->getType('float'))->toBe('xsd:float')
                ->and($this->wsdl->getType('double'))->toBe('xsd:double')
                ->and($this->wsdl->getType('boolean'))->toBe('xsd:boolean')
                ->and($this->wsdl->getType('bool'))->toBe('xsd:boolean')
                ->and($this->wsdl->getType('array'))->toBe('soap-enc:Array')
                ->and($this->wsdl->getType('object'))->toBe('xsd:struct')
                ->and($this->wsdl->getType('mixed'))->toBe('xsd:anyType')
                ->and($this->wsdl->getType('date'))->toBe('xsd:date')
                ->and($this->wsdl->getType('datetime'))->toBe('xsd:dateTime')
                ->and($this->wsdl->getType('void'))->toBe('');
        });

        test('getType uses DefaultComplexType strategy for complex types', function (): void {
            // Act
            $result = $this->wsdl->getType(WsdlTestClass::class);

            // Assert
            expect($result)->toBe('tns:WsdlTestClass')
                ->and($this->wsdl->getComplexTypeStrategy())->toBeInstanceOf(DefaultComplexType::class);
        });

        test('getType with explicit DefaultComplexType strategy creates complex type', function (): void {
            // Arrange
            $wsdl = new Wsdl($this->defaultServiceName, 'http://localhost/MyService.php', new DefaultComplexType());

            // Act & Assert
            expect($wsdl->getType(WsdlTestClass::class))->toBe('tns:WsdlTestClass')
                ->and($wsdl->getComplexTypeStrategy())->toBeInstanceOf(DefaultComplexType::class);
        });

        test('getType with AnyType strategy returns xsd:anyType for complex types', function (): void {
            // Arrange
            $wsdl = new Wsdl($this->defaultServiceName, $this->defaultServiceUri, new AnyType());

            // Act & Assert
            expect($wsdl->getType(WsdlTestClass::class))->toBe('xsd:anyType')
                ->and($wsdl->getComplexTypeStrategy())->toBeInstanceOf(AnyType::class);
        });

        test('addType ignores duplicate complex type registrations', function (): void {
            // Act
            $this->wsdl->addType(WsdlTestClass::class, 'tns:SomeTypeName');
            $this->wsdl->addType(WsdlTestClass::class, 'tns:AnotherTypeName');
            $types = $this->wsdl->getTypes();

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            expect(count($types))->toBe(1)
                ->and($types)->toBe([WsdlTestClass::class => 'tns:SomeTypeName']);
        });

        test('addComplexType reuses existing definition when called multiple times', function (): void {
            // Act
            $this->wsdl->addComplexType(WsdlTestClass::class);
            expect($this->wsdl->getTypes())->toBe([WsdlTestClass::class => 'tns:WsdlTestClass']);

            $this->wsdl->addComplexType(WsdlTestClass::class);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            expect($this->wsdl->getTypes())->toBe([WsdlTestClass::class => 'tns:WsdlTestClass']);
        });

        test('getSchema returns schema element with correct targetNamespace', function (): void {
            // Act
            $schema = $this->wsdl->getSchema();

            // Assert
            expect($schema->getAttribute('targetNamespace'))->toBe($this->defaultServiceUri);
        });

        test('addComplexType creates complexType definition with properties', function (): void {
            // Act
            $this->wsdl->addComplexType(WsdlTestClass::class);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $nodes = $this->xpath->query('//wsdl:types/xsd:schema/xsd:complexType/xsd:all/*');
            expect($nodes->length)->toBeGreaterThan(0);
        });

        test('addTypes from DOMDocument adds types element', function (): void {
            // Arrange
            $dom = new DOMDocument();
            $types = $dom->createElementNS(Wsdl::WSDL_NS_URI, 'types');
            $dom->appendChild($types);

            // Act
            $this->wsdl->addTypes($dom);

            // Assert
            $nodes = $this->xpath->query('//wsdl:types');
            expect($nodes->length)->toBeGreaterThanOrEqual(1);
            assertDocumentNodesHaveNamespaces($this->dom);
        });

        test('addTypes from DOMElement adds types element', function (): void {
            // Arrange
            $dom = $this->dom->createElementNS(Wsdl::WSDL_NS_URI, 'types');

            // Act
            $this->wsdl->addTypes($dom);

            // Assert
            $nodes = $this->xpath->query('//wsdl:types');
            expect($nodes->length)->toBeGreaterThanOrEqual(1);
            assertDocumentNodesHaveNamespaces($this->dom);
        });

        test('translateType uses class map for type translation', function (): void {
            // Arrange
            $this->wsdl->setClassMap(['SomeType' => 'SomeOtherType']);

            // Act & Assert
            expect($this->wsdl->translateType('SomeType'))->toBe('SomeOtherType');
        });

        test('translateType strips leading and trailing backslashes', function (string $type, string $expected): void {
            // Act & Assert
            expect($this->wsdl->translateType($type))->toBe($expected);
        })->with('translateTypeData');

        test('getType is case-insensitive for type detection (Laminas-3910, Laminas-11937)', function (): void {
            // Assert
            expect($this->wsdl->getType('StrIng'))->toBe('xsd:string')
                ->and($this->wsdl->getType('sTr'))->toBe('xsd:string')
                ->and($this->wsdl->getType('iNt'))->toBe('xsd:int')
                ->and($this->wsdl->getType('INTEGER'))->toBe('xsd:int')
                ->and($this->wsdl->getType('FLOAT'))->toBe('xsd:float')
                ->and($this->wsdl->getType('douBLE'))->toBe('xsd:double')
                ->and($this->wsdl->getType('daTe'))->toBe('xsd:date')
                ->and($this->wsdl->getType('long'))->toBe('xsd:long');
        });

        test('ArrayOfTypeSequence strategy recognizes duplicate sequence definitions only once (Laminas-5430)', function (): void {
            // Arrange
            $this->wsdl->setComplexTypeStrategy(
                new ArrayOfTypeSequence(),
            );

            // Act
            $this->wsdl->addComplexType('string[]');
            $this->wsdl->addComplexType('int[]');
            $this->wsdl->addComplexType('string[]');
            $this->wsdl->addComplexType('int[]');

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            $nodes = $this->xpath->query('//wsdl:types/xsd:schema/xsd:complexType[@name="ArrayOfString"]');
            expect($nodes->length)->toBe(1);

            $nodes = $this->xpath->query('//wsdl:types/xsd:schema/xsd:complexType[@name="ArrayOfInt"]');
            expect($nodes->length)->toBe(1);
        });

        test('setClassMap and getClassMap store and retrieve class mappings', function (): void {
            // Act
            $this->wsdl->setClassMap(['foo' => 'bar']);

            // Assert
            expect($this->wsdl->getClassMap())->toHaveKey('foo');
        });

        test('addElement creates element with sequence structure', function (): void {
            // Arrange
            $element = [
                'name' => 'MyElement',
                'sequence' => [
                    ['name' => 'myString', 'type' => 'string'],
                    ['name' => 'myInt',    'type' => 'int'],
                ],
            ];

            // Act
            $newElementName = $this->wsdl->addElement($element);

            // Assert
            assertDocumentNodesHaveNamespaces($this->dom);
            expect($newElementName)->toBe('tns:'.$element['name']);

            $nodes = $this->xpath->query(
                '//wsdl:types/xsd:schema/xsd:element[@name="'.$element['name'].'"]/xsd:complexType',
            );
            expect($nodes->length)->toBe(1)
                ->and($nodes->item(0)->firstChild->localName)->toBe('sequence');

            $n = 0;

            foreach ($element['sequence'] as $elementDefinition) {
                ++$n;
                $elementNode = $this->xpath->query(
                    'xsd:element[@name="'.$elementDefinition['name'].'"]',
                    $nodes->item(0)->firstChild,
                );
                expect($elementNode->item(0)->getAttribute('type'))->toBe($elementDefinition['type']);
            }
            expect($n)->toBe(count($element['sequence']));
        });
    });

    describe('Sad Paths', function (): void {
        test('addElement throws RuntimeException for invalid element structure', function (): void {
            // Act & Assert
            expect(fn () => $this->wsdl->addElement(1))
                ->toThrow(RuntimeException::class);
        });
    });
});

// Helper function for XML content validation
function checkXMLContent(string $content, object $context): void
{
    libxml_use_internal_errors(true);

    if (LIBXML_VERSION < 20_900) {
        libxml_disable_entity_loader(false);
    }
    $xml = new DOMDocument();
    $xml->preserveWhiteSpace = false;
    $xml->encoding = 'UTF-8';
    $xml->formatOutput = false;
    $xml->loadXML($content);

    $errors = libxml_get_errors();
    expect($errors)->toBeEmpty();

    registerWsdlNamespaces($xml, $context->defaultServiceUri);

    // Verify constructor expectations
    expect($xml->lookupNamespaceUri(null))->toBe(Wsdl::WSDL_NS_URI);
    expect($xml->lookupNamespaceUri('soap'))->toBe(Wsdl::SOAP_11_NS_URI);
    expect($xml->lookupNamespaceUri('soap12'))->toBe(Wsdl::SOAP_12_NS_URI);
    expect($xml->lookupNamespaceUri('tns'))->toBe($context->defaultServiceUri);
    expect($xml->lookupNamespaceUri('xsd'))->toBe(Wsdl::XSD_NS_URI);
    expect($xml->lookupNamespaceUri('soap-enc'))->toBe(Wsdl::SOAP_ENC_URI);
    expect($xml->lookupNamespaceUri('wsdl'))->toBe(Wsdl::WSDL_NS_URI);
    expect($xml->documentElement->namespaceURI)->toBe(Wsdl::WSDL_NS_URI);
    expect($xml->documentElement->getAttribute('name'))->toBe($context->defaultServiceName);
    expect($xml->documentElement->getAttribute('targetNamespace'))->toBe($context->defaultServiceUri);

    assertDocumentNodesHaveNamespaces($xml);
}

// Dataset definitions
dataset('uriTestingData', function () {
    return [
        ['http://localhost/MyService.php', 'http://localhost/MyService.php'],
        ['http://localhost/MyNewService.php', 'http://localhost/MyNewService.php'],
        [new Uri('http://localhost/MyService.php'), 'http://localhost/MyService.php'],
        // Bug Laminas-5736
        ['http://localhost/MyService.php?a=b&amp;b=c', 'http://localhost/MyService.php?a=b&amp;b=c'],
        ['http://localhost/MyService.php?a=b&b=c', 'http://localhost/MyService.php?a=b&amp;b=c'],
    ];
});

dataset('addMessageData', function () {
    return [
        [['int', 'int', 'int']],
        [['string', 'string', 'string', 'string']],
        [['mixed']],
        [['int', 'int', 'string', 'string']],
        [['int', 'string', 'int', 'string']],
    ];
});

dataset('addPortOperationData', function () {
    return [
        ['operation'],
        ['operation', 'tns:operationRequest', 'tns:operationResponse'],
        ['operation', 'tns:operationRequest', 'tns:operationResponse', 'tns:operationFault'],
        ['operation', 'tns:operationRequest', null, 'tns:operationFault'],
        ['operation', null, null, 'tns:operationFault'],
        ['operation', null, 'tns:operationResponse', 'tns:operationFault'],
        ['operation', null, 'tns:operationResponse'],
    ];
});

dataset('addBindingOperationData', function () {
    $enc = 'http://schemas.xmlsoap.org/soap/encoding/';

    return [
        ['operation'],
        ['operation', 'encoded', $enc, 'encoded', $enc, 'encoded', $enc, 'myFaultName'],
        ['operation', null, null, 'encoded', $enc, 'encoded', $enc, 'myFaultName'],
        ['operation', null, null, 'encoded', $enc, 'encoded'],
        ['operation', 'encoded', $enc],
        ['operation', null, null, null, null, 'encoded', $enc, 'myFaultName'],
        ['operation', 'encoded1', $enc.'1', 'encoded2', $enc.'2', 'encoded3', $enc.'3', 'myFaultName'],
    ];
});

dataset('soapBindingStyleData', function () {
    return [
        ['document'],
        ['rpc'],
    ];
});

dataset('addSoapOperationData', function () {
    return [
        ['http://localhost/MyService.php#myOperation'],
        [new Uri('http://localhost/MyService.php#myOperation')],
    ];
});

dataset('addServiceData', function () {
    return [
        ['http://localhost/MyService.php'],
        [new Uri('http://localhost/MyService.php')],
    ];
});

dataset('ampersandInUrlData', function () {
    return [
        'Decoded ampersand' => [
            'http://localhost/MyService.php?foo=bar&baz=qux',
            'http://localhost/MyService.php?foo=bar&amp;baz=qux',
        ],
        'Encoded ampersand' => [
            'http://localhost/MyService.php?foo=bar&amp;baz=qux',
            'http://localhost/MyService.php?foo=bar&amp;baz=qux',
        ],
        'Encoded and decoded ampersand' => [
            'http://localhost/MyService.php?foo=bar&&amp;baz=qux',
            'http://localhost/MyService.php?foo=bar&amp;&amp;baz=qux',
        ],
    ];
});

dataset('translateTypeData', function () {
    return [
        ['\\SomeType', 'SomeType'],
        ['SomeType\\', 'SomeType'],
        ['\\SomeType\\', 'SomeType'],
        ['\\SomeNamespace\SomeType\\', 'SomeType'],
        ['\\SomeNamespace\SomeType\\SomeOtherType', 'SomeOtherType'],
        ['\\SomeNamespace\SomeType\\SomeOtherType\\YetAnotherType', 'YetAnotherType'],
    ];
});
