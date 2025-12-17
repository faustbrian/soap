<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\Exception\InvalidArgumentException;
use Cline\Soap\Server;
use Tests\Fixtures\TestClass;


beforeEach(function (): void {
    skipIfSoapNotLoaded();
});

describe('Server Construction', function (): void {
    test('can create server with WSDL', function (): void {
        $server = new Server(fixturesPath('wsdl_example.wsdl'));

        expect($server)->toBeInstanceOf(Server::class);
    });

    test('can create server without WSDL', function (): void {
        $server = new Server(null, ['uri' => 'http://localhost/myservice']);

        expect($server)->toBeInstanceOf(Server::class);
    });
});

describe('Server Options', function (): void {
    test('can set and get options', function (): void {
        $server = new Server();

        $server->setOptions([
            'soap_version' => SOAP_1_2,
            'encoding' => 'UTF-8',
        ]);

        $options = $server->getOptions();

        expect($options['soap_version'])->toBe(SOAP_1_2);
        expect($options['encoding'])->toBe('UTF-8');
    });

    test('can set WSDL via setWSDL', function (): void {
        $server = new Server();
        $server->setWSDL(fixturesPath('wsdl_example.wsdl'));

        expect($server->getWSDL())->toBe(fixturesPath('wsdl_example.wsdl'));
    });

    test('can set encoding', function (): void {
        $server = new Server();
        $server->setEncoding('ISO-8859-1');

        expect($server->getEncoding())->toBe('ISO-8859-1');
    });

    test('can set soap version to SOAP 1.1', function (): void {
        $server = new Server();
        $server->setSoapVersion(SOAP_1_1);

        expect($server->getSoapVersion())->toBe(SOAP_1_1);
    });

    test('can set soap version to SOAP 1.2', function (): void {
        $server = new Server();
        $server->setSoapVersion(SOAP_1_2);

        expect($server->getSoapVersion())->toBe(SOAP_1_2);
    });

    test('throws exception for invalid soap version', function (): void {
        $server = new Server();

        expect(fn () => $server->setSoapVersion(999))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('Class and Object', function (): void {
    test('can set class', function (): void {
        $server = new Server(fixturesPath('wsdl_example.wsdl'));
        $server->setClass(TestClass::class);

        expect($server)->toBeInstanceOf(Server::class);
    });

    test('can set object', function (): void {
        $server = new Server(fixturesPath('wsdl_example.wsdl'));
        $object = new TestClass();
        $server->setObject($object);

        expect($server)->toBeInstanceOf(Server::class);
    });

});

describe('Functions', function (): void {
    test('can add function', function (): void {
        $server = new Server(fixturesPath('wsdl_example.wsdl'));
        $server->addFunction('Tests\Fixtures\TestFunc');

        expect($server->getFunctions())->toContain('Tests\Fixtures\TestFunc');
    });

    test('can add multiple functions', function (): void {
        $server = new Server(fixturesPath('wsdl_example.wsdl'));
        $server->addFunction(['Tests\Fixtures\TestFunc', 'Tests\Fixtures\TestFunc2']);

        expect($server->getFunctions())->toContain('Tests\Fixtures\TestFunc');
        expect($server->getFunctions())->toContain('Tests\Fixtures\TestFunc2');
    });
});

describe('URI', function (): void {
    test('can set and get URI', function (): void {
        $server = new Server();
        $server->setUri('http://localhost/myservice');

        expect($server->getUri())->toBe('http://localhost/myservice');
    });
});

describe('Actor', function (): void {
    test('can set and get actor', function (): void {
        $server = new Server();
        $server->setActor('http://localhost/myactor');

        expect($server->getActor())->toBe('http://localhost/myactor');
    });
});

describe('Return Response', function (): void {
    test('can set and get return response', function (): void {
        $server = new Server();

        expect($server->getReturnResponse())->toBeFalse();

        $server->setReturnResponse(true);

        expect($server->getReturnResponse())->toBeTrue();
    });
});

describe('Persistence', function (): void {
    test('can set persistence mode', function (): void {
        $server = new Server();
        $server->setPersistence(SOAP_PERSISTENCE_SESSION);

        expect($server)->toBeInstanceOf(Server::class);
    });

    test('can set persistence to request mode', function (): void {
        $server = new Server();
        $server->setPersistence(SOAP_PERSISTENCE_REQUEST);

        expect($server)->toBeInstanceOf(Server::class);
    });

    test('throws exception for invalid persistence mode', function (): void {
        $server = new Server();

        expect(fn () => $server->setPersistence(999))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('Class Map', function (): void {
    test('can set class map', function (): void {
        $server = new Server();

        $classMap = [
            'TestClass' => TestClass::class,
        ];

        $server->setClassmap($classMap);

        expect($server->getClassmap())->toBe($classMap);
    });
});

describe('Type Map', function (): void {
    test('can set type map', function (): void {
        $server = new Server();

        $typeMap = [
            [
                'type_name' => 'dateTime',
                'type_ns' => 'http://www.w3.org/2001/XMLSchema',
                'from_xml' => 'strtotime',
                'to_xml' => 'strtotime',
            ],
        ];

        $server->setTypemap($typeMap);

        expect($server->getTypemap())->toBe($typeMap);
    });
});
