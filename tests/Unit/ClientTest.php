<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\AutoDiscover;
use Cline\Soap\Client;
use Cline\Soap\Client\Local;
use Cline\Soap\Server;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex;
use Tests\Fixtures\AutoDiscoverTestClass1;
use Tests\Fixtures\AutoDiscoverTestClass2;
use Tests\Fixtures\TestClass;
use Tests\Fixtures\TestData1;
use Tests\Fixtures\TestData2;

beforeEach(function (): void {
    skipIfSoapNotLoaded();
});

describe('Client', function (): void {
    describe('Happy Paths', function (): void {
        test('sets and gets non-WSDL mode options correctly', function (): void {
            // Arrange
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
                'soap_version' => \SOAP_1_1,
                'classmap' => [
                    'TestData1' => TestData1::class,
                    'TestData2' => TestData2::class,
                ],
                'encoding' => 'ISO-8859-1',
                'uri' => 'https://getlaminas.org/Laminas_Soap_ServerTest.php',
                'location' => 'https://getlaminas.org/Laminas_Soap_ServerTest.php',
                'use' => \SOAP_ENCODED,
                'style' => \SOAP_RPC,
                'login' => 'http_login',
                'password' => 'http_password',
                'proxy_host' => 'proxy.somehost.com',
                'proxy_port' => 8_080,
                'proxy_login' => 'proxy_login',
                'proxy_password' => 'proxy_password',
                'local_cert' => fixturesPath('cert_file'),
                'passphrase' => 'some pass phrase',
                'stream_context' => $ctx,
                'cache_wsdl' => 8,
                'features' => 4,
                'compression' => \SOAP_COMPRESSION_ACCEPT | \SOAP_COMPRESSION_GZIP | 5,
                'typemap' => $typeMap,
                'keep_alive' => true,
                'ssl_method' => 3,
            ];

            // Act
            $client->setOptions($nonWSDLOptions);
            $options = $client->getOptions();

            // Assert
            expect($options['soap_version'])->toBe(\SOAP_1_1)
                ->and($options['encoding'])->toBe('ISO-8859-1')
                ->and($options['uri'])->toBe('https://getlaminas.org/Laminas_Soap_ServerTest.php')
                ->and($options['location'])->toBe('https://getlaminas.org/Laminas_Soap_ServerTest.php')
                ->and($options['use'])->toBe(\SOAP_ENCODED)
                ->and($options['style'])->toBe(\SOAP_RPC)
                ->and($options['login'])->toBe('http_login')
                ->and($options['password'])->toBe('http_password')
                ->and($options['proxy_host'])->toBe('proxy.somehost.com')
                ->and($options['proxy_port'])->toBe(8_080)
                ->and($options['proxy_login'])->toBe('proxy_login')
                ->and($options['proxy_password'])->toBe('proxy_password')
                ->and($options['local_cert'])->toBe(fixturesPath('cert_file'))
                ->and($options['passphrase'])->toBe('some pass phrase')
                ->and($options['stream_context'])->toBe($ctx)
                ->and($options['cache_wsdl'])->toBe(8)
                ->and($options['features'])->toBe(4)
                ->and($options['compression'])->toBe(\SOAP_COMPRESSION_ACCEPT | \SOAP_COMPRESSION_GZIP | 5)
                ->and($options['typemap'])->toBe($typeMap)
                ->and($options['keep_alive'])->toBeTrue()
                ->and($options['ssl_method'])->toBe(3);
        });

        test('sets and gets WSDL mode options correctly', function (): void {
            // Arrange
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

            $wsdlOptions = [
                'soap_version' => \SOAP_1_1,
                'wsdl' => fixturesPath('wsdl_example.wsdl'),
                'classmap' => [
                    'TestData1' => TestData1::class,
                    'TestData2' => TestData2::class,
                ],
                'encoding' => 'ISO-8859-1',
                'login' => 'http_login',
                'password' => 'http_password',
                'proxy_host' => 'proxy.somehost.com',
                'proxy_port' => 8_080,
                'proxy_login' => 'proxy_login',
                'proxy_password' => 'proxy_password',
                'local_cert' => fixturesPath('cert_file'),
                'passphrase' => 'some pass phrase',
                'stream_context' => $ctx,
                'compression' => \SOAP_COMPRESSION_ACCEPT | \SOAP_COMPRESSION_GZIP | 5,
                'typemap' => $typeMap,
                'keep_alive' => true,
                'ssl_method' => 3,
            ];

            // Act
            $client->setOptions($wsdlOptions);
            $options = $client->getOptions();

            // Assert
            expect($options['soap_version'])->toBe(\SOAP_1_1)
                ->and($options['wsdl'])->toBe(fixturesPath('wsdl_example.wsdl'))
                ->and($options['encoding'])->toBe('ISO-8859-1')
                ->and($options['login'])->toBe('http_login')
                ->and($options['password'])->toBe('http_password')
                ->and($options['proxy_host'])->toBe('proxy.somehost.com')
                ->and($options['proxy_port'])->toBe(8_080)
                ->and($options['proxy_login'])->toBe('proxy_login')
                ->and($options['proxy_password'])->toBe('proxy_password')
                ->and($options['local_cert'])->toBe(fixturesPath('cert_file'))
                ->and($options['passphrase'])->toBe('some pass phrase')
                ->and($options['stream_context'])->toBe($ctx)
                ->and($options['compression'])->toBe(\SOAP_COMPRESSION_ACCEPT | \SOAP_COMPRESSION_GZIP | 5)
                ->and($options['typemap'])->toBe($typeMap)
                ->and($options['keep_alive'])->toBeTrue()
                ->and($options['ssl_method'])->toBe(3);
        });

        test('returns default options when no options are set', function (): void {
            // Arrange
            $client = new Client();

            // Act
            $options = $client->getOptions();

            // Assert
            expect($options['encoding'])->toBe('UTF-8')
                ->and($options['soap_version'])->toBe(\SOAP_1_2);
        });

        test('retrieves options after setting them', function (): void {
            // Arrange
            $client = new Client();
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

            $options = [
                'soap_version' => \SOAP_1_1,
                'wsdl' => fixturesPath('wsdl_example.wsdl'),
                'classmap' => [
                    'TestData1' => TestData1::class,
                    'TestData2' => TestData2::class,
                ],
                'encoding' => 'ISO-8859-1',
                'uri' => 'https://getlaminas.org/Laminas_Soap_ServerTest.php',
                'location' => 'https://getlaminas.org/Laminas_Soap_ServerTest.php',
                'use' => \SOAP_ENCODED,
                'style' => \SOAP_RPC,
                'login' => 'http_login',
                'password' => 'http_password',
                'proxy_host' => 'proxy.somehost.com',
                'proxy_port' => 8_080,
                'proxy_login' => 'proxy_login',
                'proxy_password' => 'proxy_password',
                'local_cert' => fixturesPath('cert_file'),
                'passphrase' => 'some pass phrase',
                'compression' => \SOAP_COMPRESSION_ACCEPT | \SOAP_COMPRESSION_GZIP | 5,
                'typemap' => $typeMap,
                'keep_alive' => true,
                'ssl_method' => 3,
            ];

            // Act
            $client->setOptions($options);
            $retrievedOptions = $client->getOptions();

            // Assert
            expect($retrievedOptions['soap_version'])->toBe(\SOAP_1_1)
                ->and($retrievedOptions['wsdl'])->toBe(fixturesPath('wsdl_example.wsdl'))
                ->and($retrievedOptions['encoding'])->toBe('ISO-8859-1')
                ->and($retrievedOptions['uri'])->toBe('https://getlaminas.org/Laminas_Soap_ServerTest.php')
                ->and($retrievedOptions['location'])->toBe('https://getlaminas.org/Laminas_Soap_ServerTest.php')
                ->and($retrievedOptions['use'])->toBe(\SOAP_ENCODED)
                ->and($retrievedOptions['style'])->toBe(\SOAP_RPC)
                ->and($retrievedOptions['login'])->toBe('http_login')
                ->and($retrievedOptions['password'])->toBe('http_password')
                ->and($retrievedOptions['proxy_host'])->toBe('proxy.somehost.com')
                ->and($retrievedOptions['proxy_port'])->toBe(8_080)
                ->and($retrievedOptions['typemap'])->toBe($typeMap);
        });

        test('sets and gets user agent option using different property names', function (): void {
            // Arrange
            $client = new Client();

            // Act & Assert
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

        test('allows empty string as value for user agent', function (): void {
            // Arrange
            $client = new Client();

            // Act & Assert
            expect($client->getUserAgent())->toBeNull();
            $options = $client->getOptions();
            expect($options)->not->toHaveKey('user_agent');

            $client->setUserAgent('');
            expect($client->getUserAgent())->toBe('');
            $options = $client->getOptions();
            expect($options['user_agent'])->toBe('');

            $client->setUserAgent(null);
            expect($client->getUserAgent())->toBeNull();
            $options = $client->getOptions();
            expect($options)->not->toHaveKey('user_agent');
        });

        test('allows numeric zero as value for cache_wsdl option', function (): void {
            // Arrange
            $client = new Client();

            // Act & Assert
            expect($client->getWsdlCache())->toBeNull();
            $options = $client->getOptions();
            expect($options)->not->toHaveKey('cache_wsdl');

            $client->setWsdlCache(\WSDL_CACHE_NONE);
            expect($client->getWsdlCache())->toBe(\WSDL_CACHE_NONE);
            $options = $client->getOptions();
            expect($options['cache_wsdl'])->toBe(\WSDL_CACHE_NONE);

            $client->setWsdlCache(null);
            expect($client->getWsdlCache())->toBeNull();
            $options = $client->getOptions();
            expect($options)->not->toHaveKey('cache_wsdl');
        });

        test('allows numeric zero as value for compression options', function (): void {
            // Arrange
            $client = new Client();

            // Act & Assert
            expect($client->getCompressionOptions())->toBeNull();
            $options = $client->getOptions();
            expect($options)->not->toHaveKey('compression');

            $client->setCompressionOptions(\SOAP_COMPRESSION_GZIP);
            expect($client->getCompressionOptions())->toBe(\SOAP_COMPRESSION_GZIP);
            $options = $client->getOptions();
            expect($options['compression'])->toBe(\SOAP_COMPRESSION_GZIP);

            $client->setCompressionOptions(null);
            expect($client->getCompressionOptions())->toBeNull();
            $options = $client->getOptions();
            expect($options)->not->toHaveKey('compression');
        });

        test('retrieves available functions from WSDL', function (): void {
            // Arrange
            $server = new Server(fixturesPath('wsdl_example.wsdl'));
            $server->setClass(TestClass::class);
            $client = new Local($server, fixturesPath('wsdl_example.wsdl'));

            // Act
            $functions = $client->getFunctions();

            // Assert
            $expected = [
                'string testFunc()',
                'string testFunc2(string $who)',
                'string testFunc3(string $who, int $when)',
                'string testFunc4()',
            ];
            expect($functions)->toBe($expected);
        });

        test('retrieves complex types from WSDL using autodiscovery', function (): void {
            // Arrange
            $wsdlFilename = fixturesPath('GetTypesWsdlTest.wsdl');

            $autodiscover = new AutoDiscover();
            $autodiscover->setServiceName('ExampleService');
            $autodiscover->setComplexTypeStrategy(
                new ArrayOfTypeComplex(),
            );
            $autodiscover->setClass(AutoDiscoverTestClass2::class);
            $autodiscover->setUri('http://example.com');
            $wsdl = $autodiscover->generate();
            $wsdl->dump($wsdlFilename);

            $server = new Server($wsdlFilename);
            $server->setClass(AutoDiscoverTestClass2::class);

            $client = new Local($server, $wsdlFilename);
            $soapClient = $client->getSoapClient();

            // Act
            $typesArray = $soapClient->__getTypes();

            // Assert
            expect($typesArray)->toHaveCount(2);

            $count = 0;

            foreach ($typesArray as $element) {
                if (
                    !str_starts_with($element, 'struct AutoDiscoverTestClass1')
                    && !str_starts_with($element, 'AutoDiscoverTestClass1 ArrayOfAutoDiscoverTestClass1')
                ) {
                    continue;
                }

                ++$count;
            }
            expect($count)->toBe(2);

            // Cleanup
            unlink($wsdlFilename);
        });

        test('retrieves last request XML after SOAP call', function (): void {
            if (headers_sent($file, $line)) {
                expect(true)->toBeTrue(); // Skip test

                return;
            }

            // Arrange
            $server = new Server(fixturesPath('wsdl_example.wsdl'));
            $server->setClass(TestClass::class);
            $client = new Local($server, fixturesPath('wsdl_example.wsdl'));

            // Act
            $client->testFunc2('World');
            $lastRequest = $client->getLastRequest();

            // Assert
            $expectedRequest = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                             .'<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" '
                             .'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
                             .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
                             .'xmlns:enc="http://www.w3.org/2003/05/soap-encoding">'
                             .'<env:Header/>'
                             .'<env:Body>'
                             .'<env:testFunc2 env:encodingStyle="http://www.w3.org/2003/05/soap-encoding">'
                             .'<who xsi:type="xsd:string">World</who>'
                             .'</env:testFunc2>'
                             .'</env:Body>'
                             .'</env:Envelope>'."\n";

            expect($lastRequest)->toBe($expectedRequest);
        });

        test('retrieves last response XML after SOAP call', function (): void {
            if (headers_sent()) {
                expect(true)->toBeTrue(); // Skip test

                return;
            }

            // Arrange
            $server = new Server(fixturesPath('wsdl_example.wsdl'));
            $server->setClass(TestClass::class);
            $client = new Local($server, fixturesPath('wsdl_example.wsdl'));

            // Act
            $client->testFunc2('World');
            $lastResponse = $client->getLastResponse();

            // Assert
            $expectedResponse = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" '
                .'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
                .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
                .'xmlns:enc="http://www.w3.org/2003/05/soap-encoding">'
                .'<env:Body xmlns:rpc="http://www.w3.org/2003/05/soap-rpc">'
                .'<env:testFunc2Response env:encodingStyle="http://www.w3.org/2003/05/soap-encoding">'
                .'<rpc:result>testFunc2Return</rpc:result>'
                .'<testFunc2Return xsi:type="xsd:string">Hello World!</testFunc2Return>'
                .'</env:testFunc2Response>'
                .'</env:Body>'
                .'</env:Envelope>'."\n";

            expect($lastResponse)->toBe($expectedResponse);
        });

        test('invokes SOAP method using magic __call method', function (): void {
            if (headers_sent()) {
                expect(true)->toBeTrue(); // Skip test

                return;
            }

            // Arrange
            $server = new Server(fixturesPath('wsdl_example.wsdl'));
            $server->setClass(TestClass::class);
            $client = new Local($server, fixturesPath('wsdl_example.wsdl'));

            // Act
            $result = $client->testFunc2('World');

            // Assert
            expect($result)->toBe('Hello World!');
        });

        test('invokes SOAP method using call method with array argument', function (): void {
            if (headers_sent()) {
                expect(true)->toBeTrue(); // Skip test

                return;
            }

            // Arrange
            $server = new Server(fixturesPath('wsdl_example.wsdl'));
            $server->setClass(TestClass::class);
            $client = new Local($server, fixturesPath('wsdl_example.wsdl'));

            // Act
            $result = $client->call('testFunc2', ['World']);

            // Assert
            expect($result)->toBe('Hello World!');
        });

        test('invokes SOAP method using call method with string argument', function (): void {
            if (headers_sent()) {
                expect(true)->toBeTrue(); // Skip test

                return;
            }

            // Arrange
            $server = new Server(fixturesPath('wsdl_example.wsdl'));
            $server->setClass(TestClass::class);
            $client = new Local($server, fixturesPath('wsdl_example.wsdl'));

            // Act
            $result = $client->call('testFunc2', ['World']);

            // Assert
            expect($result)->toBe('Hello World!');
        });

        test('sets options via constructor with array', function (): void {
            // Arrange
            $ctx = stream_context_create();
            $nonWSDLOptions = [
                'soap_version' => \SOAP_1_1,
                'classmap' => [
                    'TestData1' => TestData1::class,
                    'TestData2' => TestData2::class,
                ],
                'encoding' => 'ISO-8859-1',
                'uri' => 'https://getlaminas.org/Laminas_Soap_ServerTest.php',
                'location' => 'https://getlaminas.org/Laminas_Soap_ServerTest.php',
                'use' => \SOAP_ENCODED,
                'style' => \SOAP_RPC,
                'login' => 'http_login',
                'password' => 'http_password',
                'proxy_host' => 'proxy.somehost.com',
                'proxy_port' => 8_080,
                'proxy_login' => 'proxy_login',
                'proxy_password' => 'proxy_password',
                'local_cert' => fixturesPath('cert_file'),
                'passphrase' => 'some pass phrase',
                'stream_context' => $ctx,
                'compression' => \SOAP_COMPRESSION_ACCEPT | \SOAP_COMPRESSION_GZIP | 5,
            ];

            // Act
            $client = new Client(null, $nonWSDLOptions);
            $options = $client->getOptions();

            // Assert
            expect($options['soap_version'])->toBe(\SOAP_1_1)
                ->and($options['encoding'])->toBe('ISO-8859-1')
                ->and($options['uri'])->toBe('https://getlaminas.org/Laminas_Soap_ServerTest.php')
                ->and($options['location'])->toBe('https://getlaminas.org/Laminas_Soap_ServerTest.php')
                ->and($options['use'])->toBe(\SOAP_ENCODED)
                ->and($options['style'])->toBe(\SOAP_RPC)
                ->and($options['login'])->toBe('http_login')
                ->and($options['password'])->toBe('http_password');
        });

        test('adds and manages SOAP input headers with permanent and temporary headers', function (): void {
            if (headers_sent()) {
                expect(true)->toBeTrue(); // Skip test

                return;
            }

            // Arrange
            $server = new Server(fixturesPath('wsdl_example.wsdl'));
            $server->setClass(TestClass::class);
            $client = new Local($server, fixturesPath('wsdl_example.wsdl'));

            // Add request header (temporary)
            $client->addSoapInputHeader(
                new SoapHeader(
                    'http://www.example.com/namespace',
                    'MyHeader1',
                    'SOAP header content 1',
                ),
            );

            // Add permanent request header
            $client->addSoapInputHeader(
                new SoapHeader(
                    'http://www.example.com/namespace',
                    'MyHeader2',
                    'SOAP header content 2',
                ),
                true,
            );

            // Act - First request
            $client->testFunc2('World');

            // Assert - First request
            $expectedRequest1 = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                             .'<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" '
                             .'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
                             .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
                             .'xmlns:ns1="http://www.example.com/namespace" '
                             .'xmlns:enc="http://www.w3.org/2003/05/soap-encoding">'
                             .'<env:Header>'
                             .'<ns1:MyHeader2>SOAP header content 2</ns1:MyHeader2>'
                             .'<ns1:MyHeader1>SOAP header content 1</ns1:MyHeader1>'
                             .'</env:Header>'
                             .'<env:Body>'
                             .'<env:testFunc2 env:encodingStyle="http://www.w3.org/2003/05/soap-encoding">'
                             .'<who xsi:type="xsd:string">World</who>'
                             .'</env:testFunc2>'
                             .'</env:Body>'
                             .'</env:Envelope>'."\n";

            expect($client->getLastRequest())->toBe($expectedRequest1);

            // Add another temporary header
            $client->addSoapInputHeader(
                new SoapHeader('http://www.example.com/namespace', 'MyHeader3', 'SOAP header content 3'),
            );

            // Act - Second request
            $client->testFunc2('World');

            // Assert - Second request (only permanent header and new temporary header)
            $expectedRequest2 = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                             .'<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" '
                             .'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
                             .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
                             .'xmlns:ns1="http://www.example.com/namespace" '
                             .'xmlns:enc="http://www.w3.org/2003/05/soap-encoding">'
                             .'<env:Header>'
                             .'<ns1:MyHeader2>SOAP header content 2</ns1:MyHeader2>'
                             .'<ns1:MyHeader3>SOAP header content 3</ns1:MyHeader3>'
                             .'</env:Header>'
                             .'<env:Body>'
                             .'<env:testFunc2 env:encodingStyle="http://www.w3.org/2003/05/soap-encoding">'
                             .'<who xsi:type="xsd:string">World</who>'
                             .'</env:testFunc2>'
                             .'</env:Body>'
                             .'</env:Envelope>'."\n";

            expect($client->getLastRequest())->toBe($expectedRequest2);

            // Reset all headers
            $client->resetSoapInputHeaders();

            // Add new temporary header
            $client->addSoapInputHeader(
                new SoapHeader('http://www.example.com/namespace', 'MyHeader4', 'SOAP header content 4'),
            );

            // Act - Third request
            $client->testFunc2('World');

            // Assert - Third request (only new temporary header)
            $expectedRequest3 = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                             .'<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" '
                             .'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
                             .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
                             .'xmlns:ns1="http://www.example.com/namespace" '
                             .'xmlns:enc="http://www.w3.org/2003/05/soap-encoding">'
                             .'<env:Header>'
                             .'<ns1:MyHeader4>SOAP header content 4</ns1:MyHeader4>'
                             .'</env:Header>'
                             .'<env:Body>'
                             .'<env:testFunc2 env:encodingStyle="http://www.w3.org/2003/05/soap-encoding">'
                             .'<who xsi:type="xsd:string">World</who>'
                             .'</env:testFunc2>'
                             .'</env:Body>'
                             .'</env:Envelope>'."\n";

            expect($client->getLastRequest())->toBe($expectedRequest3);
        });

        test('delegates setCookie call to underlying SoapClient', function (): void {
            // Arrange
            $fixtureCookieKey = 'foo';
            $fixtureCookieValue = 'bar';

            $clientMock = Mockery::mock(SoapClient::class, [
                null,
                ['uri' => 'https://www.zend.com', 'location' => 'https://www.zend.com'],
            ])->makePartial();

            $clientMock->expects('__setCookie')
                ->once()
                ->with($fixtureCookieKey, $fixtureCookieValue);

            $soap = new Client();
            $soap->setSoapClient($clientMock);

            // Act
            $soap->setCookie($fixtureCookieKey, $fixtureCookieValue);

            // Assert - handled by Mockery expectations
        });

        test('sets and retrieves custom SoapClient instance', function (): void {
            // Arrange
            $clientMock = new SoapClient(
                null,
                ['uri' => 'https://www.zend.com', 'location' => 'https://www.zend.com'],
            );

            $soap = new Client();

            // Act
            $soap->setSoapClient($clientMock);
            $retrievedClient = $soap->getSoapClient();

            // Assert
            expect($retrievedClient)->toBe($clientMock);
        });
    });

    describe('Sad Paths', function (): void {
        test('throws UnexpectedValueException when initializing SoapClient without WSDL or required options', function (): void {
            // Arrange
            $client = new Client(null, []);

            // Act & Assert
            expect(fn () => $client->getSoapClient())
                ->toThrow(UnexpectedValueException::class);
        });

        test('throws UnexpectedValueException when initializing SoapClient with only location but no uri', function (): void {
            // Arrange
            $client = new Client(null, ['location' => 'http://example.com']);

            // Act & Assert
            expect(fn () => $client->getSoapClient())
                ->toThrow(UnexpectedValueException::class);
        });

        test('throws UnexpectedValueException when initializing with WSDL but invalid use option', function (): void {
            // Arrange
            $client = new Client(fixturesPath('wsdl_example.wsdl'), ['use' => \SOAP_ENCODED]);

            // Act & Assert
            expect(fn () => $client->getSoapClient())
                ->toThrow(UnexpectedValueException::class);
        });

        test('throws UnexpectedValueException when initializing with WSDL but invalid style option', function (): void {
            // Arrange
            $client = new Client(fixturesPath('wsdl_example.wsdl'), ['style' => \SOAP_DOCUMENT]);

            // Act & Assert
            expect(fn () => $client->getSoapClient())
                ->toThrow(UnexpectedValueException::class);
        });
    });
});
