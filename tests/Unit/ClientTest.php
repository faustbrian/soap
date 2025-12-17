<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\Client;
use Cline\Soap\Client\Local;
use Cline\Soap\Server;
use Tests\Fixtures\TestClass;
use Tests\Fixtures\TestData1;
use Tests\Fixtures\TestData2;


beforeEach(function (): void {
    skipIfSoapNotLoaded();
});

describe('Client Options', function (): void {
    test('returns default options', function (): void {
        $client = new Client();

        expect($client->getOptions())->toBe([
            'encoding' => 'UTF-8',
            'soap_version' => SOAP_1_2,
        ]);
    });

    test('can set and get non-WSDL mode options', function (): void {
        $client = new Client();
        $ctx = stream_context_create();

        $typeMap = [
            [
                'type_name' => 'dateTime',
                'type_ns' => 'http://www.w3.org/2001/XMLSchema',
                'from_xml' => 'strtotime',
                'to_xml' => 'strtotime',
            ],
            [
                'type_name' => 'date',
                'type_ns' => 'http://www.w3.org/2001/XMLSchema',
                'from_xml' => 'strtotime',
                'to_xml' => 'strtotime',
            ],
        ];

        $nonWSDLOptions = [
            'soap_version' => SOAP_1_1,
            'classmap' => [
                'TestData1' => TestData1::class,
                'TestData2' => TestData2::class,
            ],
            'encoding' => 'ISO-8859-1',
            'uri' => 'https://example.com/Soap_ServerTest.php',
            'location' => 'https://example.com/Soap_ServerTest.php',
            'use' => SOAP_ENCODED,
            'style' => SOAP_RPC,
            'login' => 'http_login',
            'password' => 'http_password',
            'proxy_host' => 'proxy.somehost.com',
            'proxy_port' => 8080,
            'proxy_login' => 'proxy_login',
            'proxy_password' => 'proxy_password',
            'local_cert' => fixturesPath('cert_file'),
            'passphrase' => 'some pass phrase',
            'stream_context' => $ctx,
            'cache_wsdl' => 8,
            'features' => 4,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 5,
            'typemap' => $typeMap,
            'keep_alive' => true,
            'ssl_method' => 3,
        ];

        $client->setOptions($nonWSDLOptions);

        $options = $client->getOptions();
        expect($options['soap_version'])->toBe(SOAP_1_1);
        expect($options['encoding'])->toBe('ISO-8859-1');
        expect($options['uri'])->toBe('https://example.com/Soap_ServerTest.php');
        expect($options['login'])->toBe('http_login');
    });

    test('can set and get WSDL mode options', function (): void {
        $client = new Client();
        $ctx = stream_context_create();

        $typeMap = [
            [
                'type_name' => 'dateTime',
                'type_ns' => 'http://www.w3.org/2001/XMLSchema',
                'from_xml' => 'strtotime',
                'to_xml' => 'strtotime',
            ],
        ];

        $wsdlOptions = [
            'soap_version' => SOAP_1_1,
            'wsdl' => fixturesPath('wsdl_example.wsdl'),
            'classmap' => [
                'TestData1' => TestData1::class,
                'TestData2' => TestData2::class,
            ],
            'encoding' => 'ISO-8859-1',
            'login' => 'http_login',
            'password' => 'http_password',
            'proxy_host' => 'proxy.somehost.com',
            'proxy_port' => 8080,
            'proxy_login' => 'proxy_login',
            'proxy_password' => 'proxy_password',
            'local_cert' => fixturesPath('cert_file'),
            'passphrase' => 'some pass phrase',
            'stream_context' => $ctx,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 5,
            'typemap' => $typeMap,
            'keep_alive' => true,
            'ssl_method' => 3,
        ];

        $client->setOptions($wsdlOptions);

        $options = $client->getOptions();
        expect($options['soap_version'])->toBe(SOAP_1_1);
        expect($options['encoding'])->toBe('ISO-8859-1');
        expect($options['wsdl'])->toBe(fixturesPath('wsdl_example.wsdl'));
        expect($options['login'])->toBe('http_login');
    });
});

describe('User Agent', function (): void {
    test('can get and set user agent option', function (): void {
        $client = new Client();

        expect($client->getUserAgent())->toBeNull();

        $client->setUserAgent('agent1');
        expect($client->getUserAgent())->toBe('agent1');

        $client->setOptions(['user_agent' => 'agent2']);
        expect($client->getUserAgent())->toBe('agent2');

        $client->setOptions(['useragent' => 'agent3']);
        expect($client->getUserAgent())->toBe('agent3');

        $client->setOptions(['userAgent' => 'agent4']);
        expect($client->getUserAgent())->toBe('agent4');

        $options = $client->getOptions();
        expect($options['user_agent'])->toBe('agent4');
    });

    test('allows empty string for user agent', function (): void {
        $client = new Client();

        expect($client->getUserAgent())->toBeNull();
        expect($client->getOptions())->not->toHaveKey('user_agent');

        $client->setUserAgent('');
        expect($client->getUserAgent())->toBe('');
        expect($client->getOptions()['user_agent'])->toBe('');

        $client->setUserAgent(null);
        expect($client->getUserAgent())->toBeNull();
        expect($client->getOptions())->not->toHaveKey('user_agent');
    });
});

describe('WSDL Cache', function (): void {
    test('allows numeric zero for cache_wsdl option', function (): void {
        $client = new Client();

        expect($client->getWsdlCache())->toBeNull();
        expect($client->getOptions())->not->toHaveKey('cache_wsdl');

        $client->setWsdlCache(WSDL_CACHE_NONE);
        expect($client->getWsdlCache())->toBe(WSDL_CACHE_NONE);
        expect($client->getOptions()['cache_wsdl'])->toBe(WSDL_CACHE_NONE);

        $client->setWsdlCache(null);
        expect($client->getWsdlCache())->toBeNull();
        expect($client->getOptions())->not->toHaveKey('cache_wsdl');
    });
});

describe('Compression', function (): void {
    test('allows numeric zero for compression options', function (): void {
        $client = new Client();

        expect($client->getCompressionOptions())->toBeNull();
        expect($client->getOptions())->not->toHaveKey('compression');

        $client->setCompressionOptions(SOAP_COMPRESSION_GZIP);
        expect($client->getCompressionOptions())->toBe(SOAP_COMPRESSION_GZIP);
        expect($client->getOptions()['compression'])->toBe(SOAP_COMPRESSION_GZIP);

        $client->setCompressionOptions(null);
        expect($client->getCompressionOptions())->toBeNull();
        expect($client->getOptions())->not->toHaveKey('compression');
    });
});

describe('Local Client', function (): void {
    test('can get functions from WSDL', function (): void {
        $server = new Server(fixturesPath('wsdl_example.wsdl'));
        $server->setClass(TestClass::class);

        $client = new Local($server, fixturesPath('wsdl_example.wsdl'));

        $expected = [
            'string testFunc()',
            'string testFunc2(string $who)',
            'string testFunc3(string $who, int $when)',
            'string testFunc4()',
        ];

        expect($client->getFunctions())->toBe($expected);
    });

    test('can invoke methods via __call', function (): void {
        if (headers_sent()) {
            $this->markTestSkipped('Cannot run when headers have already been sent');
        }

        $server = new Server(fixturesPath('wsdl_example.wsdl'));
        $server->setClass(TestClass::class);

        $client = new Local($server, fixturesPath('wsdl_example.wsdl'));

        expect($client->testFunc2('World'))->toBe('Hello World!');
    });

    test('can call methods directly', function (): void {
        if (headers_sent()) {
            $this->markTestSkipped('Cannot run when headers have already been sent');
        }

        $server = new Server(fixturesPath('wsdl_example.wsdl'));
        $server->setClass(TestClass::class);

        $client = new Local($server, fixturesPath('wsdl_example.wsdl'));

        expect($client->call('testFunc2', ['World']))->toBe('Hello World!');
    });

    test('can call methods with string argument', function (): void {
        if (headers_sent()) {
            $this->markTestSkipped('Cannot run when headers have already been sent');
        }

        $server = new Server(fixturesPath('wsdl_example.wsdl'));
        $server->setClass(TestClass::class);

        $client = new Local($server, fixturesPath('wsdl_example.wsdl'));

        expect($client->call('testFunc2', 'World'))->toBe('Hello World!');
    });
});

describe('Soap Client', function (): void {
    test('can set and get soap client', function (): void {
        $realClient = new \SoapClient(
            null,
            ['uri' => 'https://www.example.com', 'location' => 'https://www.example.com']
        );

        $soap = new Client();
        $soap->setSoapClient($realClient);

        expect($soap->getSoapClient())->toBe($realClient);
    });

    test('throws exception when initializing without required options', function (
        ?string $wsdl,
        array $options
    ): void {
        $client = new Client($wsdl, $options);

        expect(fn () => $client->getSoapClient())
            ->toThrow(UnexpectedValueException::class);
    })->with([
        [null, []],
        [null, ['location' => 'http://example.com']],
    ]);
});
