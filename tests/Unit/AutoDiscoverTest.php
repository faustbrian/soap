<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\AutoDiscover;
use Cline\Soap\Exception\InvalidArgumentException;
use Cline\Soap\Exception\RuntimeException;
use Cline\Soap\Wsdl;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence;
use Tests\Fixtures\TestClass;


beforeEach(function (): void {
    skipIfSoapNotLoaded();
});

describe('AutoDiscover Construction', function (): void {
    test('can create AutoDiscover instance', function (): void {
        $autodiscover = new AutoDiscover();

        expect($autodiscover)->toBeInstanceOf(AutoDiscover::class);
    });

    test('can set service name', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setServiceName('MyService');

        expect($autodiscover->getServiceName())->toBe('MyService');
    });

    test('can set URI', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setUri('http://localhost/myservice');

        expect($autodiscover->getUri()->toString())->toBe('http://localhost/myservice');
    });
});

describe('Class Discovery', function (): void {
    test('can set class for discovery', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setClass(TestClass::class);

        expect($autodiscover)->toBeInstanceOf(AutoDiscover::class);
    });

    test('can generate WSDL from class', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setServiceName('TestService');
        $autodiscover->setUri('http://localhost/test');
        $autodiscover->setClass(TestClass::class);

        $wsdl = $autodiscover->generate();

        expect($wsdl)->toBeInstanceOf(Wsdl::class);

        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom, 'http://localhost/test');

        // Check service name
        expect($dom->documentElement->getAttribute('name'))->toBe('TestService');

        // Check target namespace
        expect($dom->documentElement->getAttribute('targetNamespace'))->toBe('http://localhost/test');
    });
});

describe('Function Discovery', function (): void {
    test('can add function for discovery', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->addFunction('Tests\Fixtures\TestFunc');

        expect($autodiscover)->toBeInstanceOf(AutoDiscover::class);
    });

    test('throws exception for non-existent function', function (): void {
        $autodiscover = new AutoDiscover();

        expect(fn () => $autodiscover->addFunction('NonExistentFunction'))
            ->toThrow(InvalidArgumentException::class);
    });

    test('throws exception for invalid function argument', function (): void {
        $autodiscover = new AutoDiscover();

        expect(fn () => $autodiscover->addFunction(123))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('URI Validation', function (): void {
    test('throws exception for invalid URI type', function (): void {
        $autodiscover = new AutoDiscover();

        expect(fn () => $autodiscover->setUri(123))
            ->toThrow(InvalidArgumentException::class);
    });

    test('throws exception for empty URI', function (): void {
        $autodiscover = new AutoDiscover();

        expect(fn () => $autodiscover->setUri(''))
            ->toThrow(InvalidArgumentException::class);
    });

    test('throws exception when URI not set during generate', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setServiceName('MyService');
        $autodiscover->setClass(TestClass::class);

        expect(fn () => $autodiscover->generate())
            ->toThrow(RuntimeException::class);
    });
});

describe('WSDL Class', function (): void {
    test('can set custom WSDL class', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setWsdlClass(Wsdl::class);

        expect($autodiscover->getWsdlClass())->toBe(Wsdl::class);
    });
});

describe('Complex Type Strategy', function (): void {
    test('can set ArrayOfTypeComplex strategy', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setComplexTypeStrategy(new ArrayOfTypeComplex());

        expect($autodiscover)->toBeInstanceOf(AutoDiscover::class);
    });

    test('can set ArrayOfTypeSequence strategy', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setComplexTypeStrategy(new ArrayOfTypeSequence());

        expect($autodiscover)->toBeInstanceOf(AutoDiscover::class);
    });
});

describe('Operation Body Style', function (): void {
    test('can set operation body style', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setOperationBodyStyle([
            'use' => 'literal',
        ]);

        expect($autodiscover)->toBeInstanceOf(AutoDiscover::class);
    });

    test('throws exception when use key is missing', function (): void {
        $autodiscover = new AutoDiscover();

        expect(fn () => $autodiscover->setOperationBodyStyle([]))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('Binding Style', function (): void {
    test('can set binding style', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setBindingStyle([
            'style' => 'document',
            'transport' => 'http://schemas.xmlsoap.org/soap/http',
        ]);

        expect($autodiscover)->toBeInstanceOf(AutoDiscover::class);
    });

    test('can set RPC binding style', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setBindingStyle([
            'style' => 'rpc',
        ]);

        expect($autodiscover)->toBeInstanceOf(AutoDiscover::class);
    });
});

describe('WSDL Generation', function (): void {
    test('generated WSDL contains port type', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setServiceName('TestService');
        $autodiscover->setUri('http://localhost/test');
        $autodiscover->setClass(TestClass::class);

        $wsdl = $autodiscover->generate();
        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom, 'http://localhost/test');

        $portTypes = $xpath->query('wsdl:portType');
        expect($portTypes->length)->toBeGreaterThan(0);
    });

    test('generated WSDL contains binding', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setServiceName('TestService');
        $autodiscover->setUri('http://localhost/test');
        $autodiscover->setClass(TestClass::class);

        $wsdl = $autodiscover->generate();
        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom, 'http://localhost/test');

        $bindings = $xpath->query('wsdl:binding');
        expect($bindings->length)->toBeGreaterThan(0);
    });

    test('generated WSDL contains service', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setServiceName('TestService');
        $autodiscover->setUri('http://localhost/test');
        $autodiscover->setClass(TestClass::class);

        $wsdl = $autodiscover->generate();
        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom, 'http://localhost/test');

        $services = $xpath->query('wsdl:service');
        expect($services->length)->toBeGreaterThan(0);
    });

    test('generated WSDL contains messages for operations', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setServiceName('TestService');
        $autodiscover->setUri('http://localhost/test');
        $autodiscover->setClass(TestClass::class);

        $wsdl = $autodiscover->generate();
        $dom = $wsdl->toDomDocument();
        $xpath = registerWsdlNamespaces($dom, 'http://localhost/test');

        $messages = $xpath->query('wsdl:message');
        expect($messages->length)->toBeGreaterThan(0);
    });
});

describe('WSDL Dump', function (): void {
    test('can dump WSDL to file', function (): void {
        $autodiscover = new AutoDiscover();
        $autodiscover->setServiceName('TestService');
        $autodiscover->setUri('http://localhost/test');
        $autodiscover->setClass(TestClass::class);

        $wsdl = $autodiscover->generate();
        $tempFile = sys_get_temp_dir().'/test_autodiscover_'.uniqid().'.wsdl';

        $result = $wsdl->dump($tempFile);

        expect($result)->toBeTrue();
        expect(file_exists($tempFile))->toBeTrue();

        $content = file_get_contents($tempFile);
        expect($content)->toContain('TestService');
        expect($content)->toContain('definitions');

        unlink($tempFile);
    });
});
