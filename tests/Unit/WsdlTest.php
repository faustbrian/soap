<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\Wsdl;
use Cline\Soap\Wsdl\ComplexTypeStrategy\AnyType;
use Cline\Soap\Wsdl\ComplexTypeStrategy\DefaultComplexType;
use Laminas\Uri\Uri;


beforeEach(function (): void {
    skipIfSoapNotLoaded();
});

describe('WSDL Constructor', function (): void {
    test('creates WSDL with correct namespaces', function (): void {
        $serviceName = 'MyService';
        $serviceUri = 'http://localhost/MyService.php';

        $wsdl = createWsdl($serviceName, $serviceUri);
        $dom = $wsdl->toDomDocument();

        expect($dom->lookupNamespaceUri(null))->toBe(Wsdl::WSDL_NS_URI);
        expect($dom->lookupNamespaceUri('soap'))->toBe(Wsdl::SOAP_11_NS_URI);
        expect($dom->lookupNamespaceUri('soap12'))->toBe(Wsdl::SOAP_12_NS_URI);
        expect($dom->lookupNamespaceUri('tns'))->toBe($serviceUri);
        expect($dom->lookupNamespaceUri('xsd'))->toBe(Wsdl::XSD_NS_URI);
        expect($dom->lookupNamespaceUri('soap-enc'))->toBe(Wsdl::SOAP_ENC_URI);
        expect($dom->lookupNamespaceUri('wsdl'))->toBe(Wsdl::WSDL_NS_URI);
    });

    test('sets correct root element attributes', function (): void {
        $serviceName = 'MyService';
        $serviceUri = 'http://localhost/MyService.php';

        $wsdl = createWsdl($serviceName, $serviceUri);
        $dom = $wsdl->toDomDocument();

        expect($dom->documentElement->namespaceURI)->toBe(Wsdl::WSDL_NS_URI);
        expect($dom->documentElement->getAttribute('name'))->toBe($serviceName);
        expect($dom->documentElement->getAttribute('targetNamespace'))->toBe($serviceUri);
    });

    test('all document nodes have valid namespaces', function (): void {
        $wsdl = createWsdl();
        $dom = $wsdl->toDomDocument();

        assertDocumentNodesHaveNamespaces($dom);
    });
});

describe('URI Handling', function (): void {
    test('setUri changes DOM document structure', function (
        string|Uri $uri,
        string $expectedUri
    ): void {
        $wsdl = createWsdl();

        if ($uri instanceof Uri) {
            $wsdl->setUri($uri->toString());
        } else {
            $wsdl->setUri($uri);
        }

        $dom = $wsdl->toDomDocument();

        expect($dom->lookupNamespaceUri('tns'))->toBe($expectedUri);
        expect($dom->documentElement->getAttribute('targetNamespace'))->toBe($expectedUri);

        assertDocumentNodesHaveNamespaces($dom);
    })->with([
        ['http://localhost/MyNewService.php', 'http://localhost/MyNewService.php'],
        ['http://localhost/MyNewService.php?a=b&b=c', 'http://localhost/MyNewService.php?a=b&amp;b=c'],
    ]);

    test('can construct with different URIs', function (string $uri, string $expectedUri): void {
        $wsdl = new Wsdl('MyService', $uri);
        $dom = $wsdl->toDomDocument();

        expect($dom->lookupNamespaceUri('tns'))->toBe($expectedUri);
        expect($dom->documentElement->getAttribute('targetNamespace'))->toBe($expectedUri);
    })->with([
        ['http://localhost/MyNewService.php', 'http://localhost/MyNewService.php'],
        ['http://localhost/MyNewService.php?a=b&b=c', 'http://localhost/MyNewService.php?a=b&amp;b=c'],
    ]);
});

describe('Port Type', function (): void {
    test('addPortType adds wsdl:portType element', function (): void {
        $wsdl = createWsdl();
        $portType = $wsdl->addPortType('myPortType');
        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom);

        expect($portType)->toBeInstanceOf(DOMElement::class);

        $nodes = $xpath->query('wsdl:portType[@name="myPortType"]');
        expect($nodes)->toHaveCount(1);
        expect($nodes->item(0)->getAttribute('name'))->toBe('myPortType');
    });
});

describe('Binding', function (): void {
    test('addBinding adds wsdl:binding element', function (): void {
        $wsdl = createWsdl();
        $binding = $wsdl->addBinding('MyBinding', 'tns:myPortType');
        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom);

        expect($binding)->toBeInstanceOf(DOMElement::class);

        $nodes = $xpath->query('wsdl:binding[@name="MyBinding"][@type="tns:myPortType"]');
        expect($nodes)->toHaveCount(1);
    });
});

describe('SOAP Binding', function (): void {
    test('addSoapBinding adds soap:binding element with RPC style', function (): void {
        $wsdl = createWsdl();
        $binding = $wsdl->addBinding('MyBinding', 'tns:myPortType');
        $wsdl->addSoapBinding($binding, 'rpc');

        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom);

        $nodes = $xpath->query('wsdl:binding[@name="MyBinding"]/soap:binding');
        expect($nodes)->toHaveCount(1);
        expect($nodes->item(0)->getAttribute('style'))->toBe('rpc');
        expect($nodes->item(0)->getAttribute('transport'))->toBe('http://schemas.xmlsoap.org/soap/http');
    });

    test('addSoapBinding adds soap:binding element with document style', function (): void {
        $wsdl = createWsdl();
        $binding = $wsdl->addBinding('MyBinding', 'tns:myPortType');
        $wsdl->addSoapBinding($binding, 'document');

        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom);

        $nodes = $xpath->query('wsdl:binding[@name="MyBinding"]/soap:binding');
        expect($nodes)->toHaveCount(1);
        expect($nodes->item(0)->getAttribute('style'))->toBe('document');
    });
});

describe('Messages', function (): void {
    test('addMessage adds wsdl:message element', function (): void {
        $wsdl = createWsdl();
        $message = $wsdl->addMessage('myMessage', ['part1' => 'xsd:string']);

        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom);

        expect($message)->toBeInstanceOf(DOMElement::class);

        $nodes = $xpath->query('wsdl:message[@name="myMessage"]');
        expect($nodes)->toHaveCount(1);

        $partNodes = $xpath->query('wsdl:message[@name="myMessage"]/wsdl:part[@name="part1"][@type="xsd:string"]');
        expect($partNodes)->toHaveCount(1);
    });
});

describe('Port Operations', function (): void {
    test('addPortOperation adds wsdl:operation to port type', function (): void {
        $wsdl = createWsdl();
        $portType = $wsdl->addPortType('myPortType');

        $operation = $wsdl->addPortOperation(
            $portType,
            'myOperation',
            'tns:myInputMessage',
            'tns:myOutputMessage'
        );

        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom);

        expect($operation)->toBeInstanceOf(DOMElement::class);

        $nodes = $xpath->query('wsdl:portType[@name="myPortType"]/wsdl:operation[@name="myOperation"]');
        expect($nodes)->toHaveCount(1);
    });
});

describe('Service', function (): void {
    test('addService adds wsdl:service element', function (): void {
        $wsdl = createWsdl();

        $wsdl->addService(
            'MyServiceService',
            'myPort',
            'tns:MyBinding',
            'http://localhost/myservice.php'
        );

        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom);

        $nodes = $xpath->query('wsdl:service[@name="MyServiceService"]');
        expect($nodes)->toHaveCount(1);

        $portNodes = $xpath->query('wsdl:service[@name="MyServiceService"]/wsdl:port[@name="myPort"]');
        expect($portNodes)->toHaveCount(1);
    });
});

describe('Type Mapping', function (): void {
    test('maps PHP types to XSD types', function (string $type, string $expected): void {
        $wsdl = createWsdl();

        expect($wsdl->getType($type))->toBe($expected);
    })->with([
        ['string', 'xsd:string'],
        ['str', 'xsd:string'],
        ['int', 'xsd:int'],
        ['integer', 'xsd:int'],
        ['float', 'xsd:float'],
        ['double', 'xsd:double'],
        ['boolean', 'xsd:boolean'],
        ['bool', 'xsd:boolean'],
        ['array', 'soap-enc:Array'],
        ['object', 'xsd:struct'],
        ['mixed', 'xsd:anyType'],
        ['void', ''],
    ]);
});

describe('Complex Types', function (): void {
    test('addComplexType with DefaultComplexType strategy', function (): void {
        $wsdl = createWsdl();

        $type = $wsdl->addComplexType(Tests\Fixtures\WsdlTestClass::class);

        expect($type)->toBe('tns:WsdlTestClass');

        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom);

        $nodes = $xpath->query('//xsd:complexType[@name="WsdlTestClass"]');
        expect($nodes)->toHaveCount(1);
    });
});

describe('WSDL Output', function (): void {
    test('toXml returns valid XML', function (): void {
        $wsdl = createWsdl();
        $xml = $wsdl->toXml();

        expect($xml)->toContain('<?xml version="1.0"');
        expect($xml)->toContain('definitions');
        expect($xml)->toContain('xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"');
    });

    test('dump writes to file', function (): void {
        $wsdl = createWsdl();
        $tempFile = sys_get_temp_dir().'/test_wsdl_'.uniqid().'.wsdl';

        $result = $wsdl->dump($tempFile);

        expect($result)->toBeTrue();
        expect(file_exists($tempFile))->toBeTrue();
        expect(file_get_contents($tempFile))->toContain('definitions');

        unlink($tempFile);
    });
});
