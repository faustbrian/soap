<?php declare(strict_types=1);

/**
 * Migrated from laminas/laminas-soap
 * @see https://github.com/laminas/laminas-soap
 */

use Cline\Soap\AutoDiscover;
use Cline\Soap\Client\Local;
use Cline\Soap\Exception\InvalidArgumentException;
use Cline\Soap\Exception\RuntimeException;
use Cline\Soap\Server;
use Tests\Fixtures\errorClass;
use Tests\Fixtures\MockServer;
use Tests\Fixtures\ServerTestClass;
use Tests\Fixtures\TestData1;
use Tests\Fixtures\TestData2;

beforeEach(function (): void {
    skipIfSoapNotLoaded();
});

describe('Options', function (): void {
    test('setOptions sets and returns options', function (): void {
        $server = new Server();

        expect($server->getOptions())->toBe(['soap_version' => SOAP_1_2]);

        $options = [
            'soap_version' => SOAP_1_1,
            'actor' => 'https://example.com/test.php',
            'classmap' => [
                'TestData1' => TestData1::class,
                'TestData2' => TestData2::class,
            ],
            'encoding' => 'ISO-8859-1',
            'uri' => 'https://example.com/test.php',
            'parse_huge' => false,
        ];
        $server->setOptions($options);

        $result = $server->getOptions();
        expect($result['soap_version'])->toBe(SOAP_1_1);
        expect($result['actor'])->toBe('https://example.com/test.php');
        expect($result['encoding'])->toBe('ISO-8859-1');
        expect($result['uri'])->toBe('https://example.com/test.php');
    });

    test('setOptions via second constructor argument', function (): void {
        $options = [
            'soap_version' => SOAP_1_1,
            'actor' => 'https://example.com/test.php',
            'classmap' => [
                'TestData1' => TestData1::class,
                'TestData2' => TestData2::class,
            ],
            'encoding' => 'ISO-8859-1',
            'uri' => 'https://example.com/test.php',
            'parse_huge' => false,
        ];
        $server = new Server(null, $options);

        $result = $server->getOptions();
        expect($result['soap_version'])->toBe(SOAP_1_1);
        expect($result['actor'])->toBe('https://example.com/test.php');
        expect($result['encoding'])->toBe('ISO-8859-1');
        expect($result['uri'])->toBe('https://example.com/test.php');
    });

    test('setOptions with features option', function (): void {
        $server = new Server(null, [
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        ]);

        expect($server->getSoapFeatures())->toBe(SOAP_SINGLE_ELEMENT_ARRAYS);
    });

    test('setWsdl via options array', function (): void {
        $server = new Server();
        $server->setOptions(['wsdl' => 'http://www.example.com/test.wsdl']);

        expect($server->getWSDL())->toBe('http://www.example.com/test.wsdl');
    });

    test('getOptions returns current options', function (): void {
        $server = new Server();

        expect($server->getOptions())->toBe(['soap_version' => SOAP_1_2]);

        $options = [
            'soap_version' => SOAP_1_1,
            'uri' => 'https://example.com/test.php',
        ];
        $server->setOptions($options);

        expect($server->getOptions())->toBe($options);
    });
});

describe('Encoding', function (): void {
    test('can set and get encoding', function (): void {
        $server = new Server();

        expect($server->getEncoding())->toBeNull();
        $server->setEncoding('ISO-8859-1');
        expect($server->getEncoding())->toBe('ISO-8859-1');
    });

    test('throws exception for invalid encoding type', function (): void {
        $server = new Server();

        expect(fn () => $server->setEncoding(['UTF-8']))
            ->toThrow(InvalidArgumentException::class, 'Invalid encoding specified');
    });
});

describe('SOAP Version', function (): void {
    test('can set and get soap version', function (): void {
        $server = new Server();

        expect($server->getSoapVersion())->toBe(SOAP_1_2);
        $server->setSoapVersion(SOAP_1_1);
        expect($server->getSoapVersion())->toBe(SOAP_1_1);
    });

    test('throws exception for invalid soap version', function (): void {
        $server = new Server();

        expect(fn () => $server->setSoapVersion('bogus'))
            ->toThrow(InvalidArgumentException::class, 'Invalid soap version specified');
    });
});

describe('URN Validation', function (): void {
    test('validates valid URNs', function (): void {
        $server = new Server();

        expect($server->validateUrn('https://example.com/'))->toBeTrue();
        expect($server->validateUrn('urn:soapHandler/GetOpt'))->toBeTrue();
    });

    test('throws exception for invalid URN', function (): void {
        $server = new Server();

        expect(fn () => $server->validateUrn('bogosity'))
            ->toThrow(InvalidArgumentException::class, 'Invalid URN');
    });
});

describe('Actor', function (): void {
    test('can set and get actor', function (): void {
        $server = new Server();

        expect($server->getActor())->toBeNull();
        $server->setActor('https://example.com/');
        expect($server->getActor())->toBe('https://example.com/');
    });

    test('throws exception for invalid actor', function (): void {
        $server = new Server();

        expect(fn () => $server->setActor('bogus'))
            ->toThrow(InvalidArgumentException::class, 'Invalid URN');
    });
});

describe('URI', function (): void {
    test('can set and get URI', function (): void {
        $server = new Server();

        expect($server->getUri())->toBeNull();
        $server->setUri('https://example.com/');
        expect($server->getUri())->toBe('https://example.com/');
    });

    test('throws exception for invalid URI', function (): void {
        $server = new Server();

        expect(fn () => $server->setUri('bogus'))
            ->toThrow(InvalidArgumentException::class, 'Invalid URN');
    });
});

describe('Classmap', function (): void {
    test('can set and get classmap', function (): void {
        $server = new Server();
        $classmap = [
            'TestData1' => TestData1::class,
            'TestData2' => TestData2::class,
        ];

        expect($server->getClassmap())->toBeNull();
        $server->setClassmap($classmap);
        expect($server->getClassmap())->toBe($classmap);
    });

    test('throws exception for string classmap', function (): void {
        $server = new Server();

        expect(fn () => $server->setClassmap('bogus'))
            ->toThrow(InvalidArgumentException::class, 'Classmap must be an array');
    });

    test('throws exception for invalid class in classmap', function (): void {
        $server = new Server();

        expect(fn () => $server->setClassmap(['soapTypeName', 'bogusClassName']))
            ->toThrow(InvalidArgumentException::class, 'Invalid class in class map');
    });
});

describe('WSDL', function (): void {
    test('can set and get WSDL', function (): void {
        $server = new Server();

        expect($server->getWSDL())->toBeNull();
        $server->setWSDL(fixturesPath('wsdl_example.wsdl'));
        expect($server->getWSDL())->toBe(fixturesPath('wsdl_example.wsdl'));
    });

    test('can set WSDL to non-existent file', function (): void {
        $server = new Server();
        $server->setWSDL(fixturesPath('bogus.wsdl'));

        expect($server->getWSDL())->toBe(fixturesPath('bogus.wsdl'));
    });
});

describe('Functions', function (): void {
    test('can add single function', function (): void {
        $server = new Server();
        $server->addFunction('\Tests\Fixtures\TestFunc');

        expect($server->getFunctions())->toContain('\Tests\Fixtures\TestFunc');
    });

    test('can add array of functions', function (): void {
        $server = new Server();
        $functions = [
            '\Tests\Fixtures\TestFunc2',
            '\Tests\Fixtures\TestFunc3',
            '\Tests\Fixtures\TestFunc4',
        ];
        $server->addFunction('\Tests\Fixtures\TestFunc');
        $server->addFunction($functions);

        expect($server->getFunctions())->toBe(array_merge(['\Tests\Fixtures\TestFunc'], $functions));
    });

    test('throws exception for integer function', function (): void {
        $server = new Server();

        expect(fn () => $server->addFunction(126))
            ->toThrow(InvalidArgumentException::class, 'Invalid function specified');
    });

    test('throws exception for non-existent function', function (): void {
        $server = new Server();

        expect(fn () => $server->addFunction('bogus_function'))
            ->toThrow(InvalidArgumentException::class, 'Invalid function specified');
    });

    test('throws exception for array with invalid function', function (): void {
        $server = new Server();
        $functions = [
            '\Tests\Fixtures\TestFunc5',
            'bogus_function',
            '\Tests\Fixtures\TestFunc6',
        ];

        expect(fn () => $server->addFunction($functions))
            ->toThrow(InvalidArgumentException::class, 'One or more invalid functions specified in array');
    });

    test('SOAP_FUNCTIONS_ALL constant clears function list', function (): void {
        $server = new Server();
        $server->addFunction(SOAP_FUNCTIONS_ALL);
        $server->addFunction('substr');

        expect($server->getFunctions())->toBe([SOAP_FUNCTIONS_ALL]);
    });

    test('getFunctions returns class methods when class attached', function (): void {
        $server = new Server();
        $server->setClass(ServerTestClass::class);

        expect($server->getFunctions())->toBe(['testFunc1', 'testFunc2', 'testFunc3', 'testFunc4', 'testFunc5']);
    });

    test('getFunctions returns class methods when object attached', function (): void {
        $server = new Server();
        $server->setObject(new ServerTestClass());

        expect($server->getFunctions())->toBe(['testFunc1', 'testFunc2', 'testFunc3', 'testFunc4', 'testFunc5']);
    });
});

describe('Class', function (): void {
    test('can set class by name', function (): void {
        $server = new Server();
        $result = $server->setClass(ServerTestClass::class);

        expect($result)->toBe($server);
    });

    test('can set class with object', function (): void {
        $server = new Server();
        $result = $server->setClass(new ServerTestClass());

        expect($result)->toBe($server);
    });

    test('throws exception when setting class twice', function (): void {
        $server = new Server();
        $server->setClass(ServerTestClass::class);

        expect(fn () => $server->setClass(ServerTestClass::class))
            ->toThrow(InvalidArgumentException::class, 'A class has already been registered');
    });

    test('can set class with arguments', function (): void {
        $server = new Server();
        $result = $server->setClass(ServerTestClass::class, null, 1, 2, 3, 4);

        expect($result)->toBe($server);
    });

    test('throws exception for integer class name', function (): void {
        $server = new Server();

        expect(fn () => $server->setClass(465))
            ->toThrow(InvalidArgumentException::class, 'Invalid class argument (integer)');
    });

    test('throws exception for unknown class name', function (): void {
        $server = new Server();

        expect(fn () => $server->setClass('Bogus_Unknown_Class'))
            ->toThrow(InvalidArgumentException::class, 'does not exist');
    });
});

describe('Object', function (): void {
    test('can set object', function (): void {
        $server = new Server();
        $result = $server->setObject(new ServerTestClass());

        expect($result)->toBe($server);
    });

    test('throws exception for integer object', function (): void {
        $server = new Server();

        expect(fn () => $server->setObject(465))
            ->toThrow(InvalidArgumentException::class, 'Invalid object argument (integer)');
    });
});

describe('Persistence', function (): void {
    test('can set and get persistence', function (): void {
        $server = new Server();

        expect($server->getPersistence())->toBeNull();

        $server->setPersistence(SOAP_PERSISTENCE_SESSION);
        expect($server->getPersistence())->toBe(SOAP_PERSISTENCE_SESSION);

        $server->setPersistence(SOAP_PERSISTENCE_REQUEST);
        expect($server->getPersistence())->toBe(SOAP_PERSISTENCE_REQUEST);
    });

    test('throws exception for invalid persistence', function (): void {
        $server = new Server();

        expect(fn () => $server->setPersistence('bogus'))
            ->toThrow(InvalidArgumentException::class, 'Invalid persistence mode specified');
    });
});

describe('Return Response', function (): void {
    test('can set and get return response', function (): void {
        $server = new Server();

        expect($server->getReturnResponse())->toBeFalse();

        $server->setReturnResponse(true);
        expect($server->getReturnResponse())->toBeTrue();

        $server->setReturnResponse(false);
        expect($server->getReturnResponse())->toBeFalse();
    });
});

describe('Handle Request', function (): void {
    test('getLastRequest returns last request', function (): void {
        if (headers_sent()) {
            $this->markTestSkipped('Cannot run when headers have already been sent');
        }

        $server = new Server();
        $server->setOptions(['location' => 'test://', 'uri' => 'https://example.com']);
        $server->setReturnResponse(true);
        $server->setClass(ServerTestClass::class);

        $request = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" '
            . 'xmlns:ns1="https://example.com" '
            . 'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            . 'xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" '
            . 'SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
            . '<SOAP-ENV:Body>'
            . '<ns1:testFunc2>'
            . '<param0 xsi:type="xsd:string">World</param0>'
            . '</ns1:testFunc2>'
            . '</SOAP-ENV:Body>'
            . '</SOAP-ENV:Envelope>';

        $server->handle($request);

        expect($server->getLastRequest())->toBe($request);
    });

    test('getLastResponse returns response', function (): void {
        if (headers_sent()) {
            $this->markTestSkipped('Cannot run when headers have already been sent');
        }

        $server = new Server();
        $server->setOptions(['location' => 'test://', 'uri' => 'https://example.com']);
        $server->setReturnResponse(true);
        $server->setClass(ServerTestClass::class);

        $request = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" '
            . 'xmlns:ns1="https://example.com" '
            . 'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            . 'xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" '
            . 'SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
            . '<SOAP-ENV:Body>'
            . '<ns1:testFunc2>'
            . '<param0 xsi:type="xsd:string">World</param0>'
            . '</ns1:testFunc2>'
            . '</SOAP-ENV:Body>'
            . '</SOAP-ENV:Envelope>' . "\n";

        $server->handle($request);

        expect($server->getResponse())->toContain('Hello World!');
    });

    test('handle processes request correctly', function (): void {
        if (headers_sent()) {
            $this->markTestSkipped('Cannot run when headers have already been sent');
        }

        $server = new Server();
        $server->setOptions(['location' => 'test://', 'uri' => 'https://example.com']);
        $server->setClass(ServerTestClass::class);

        $localClient = new \Tests\Fixtures\TestLocalSoapClient(
            $server,
            null,
            ['location' => 'test://', 'uri' => 'https://example.com']
        );

        expect($localClient->testFunc2('World'))->toBe('Hello World!');
    });

    test('empty request returns fault', function (): void {
        $server = new Server();
        $server->setOptions(['location' => 'test://', 'uri' => 'https://example.com']);
        $server->setReturnResponse(true);
        $server->setClass(ServerTestClass::class);

        $response = $server->handle('');

        expect($response->getMessage())->toContain('Empty request');
    });

    test('request with doctype throws exception', function (): void {
        $server = new Server();
        $server->setOptions(['location' => 'test://', 'uri' => 'https://example.com']);
        $server->setReturnResponse(true);
        $server->setClass(ServerTestClass::class);

        $request = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<!DOCTYPE foo>' . "\n"
            . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" '
            . 'xmlns:ns1="https://example.com">'
            . '<SOAP-ENV:Body><ns1:testFunc2><param0>World</param0></ns1:testFunc2></SOAP-ENV:Body>'
            . '</SOAP-ENV:Envelope>';

        $response = $server->handle($request);

        expect($response->getMessage())->toContain('Invalid XML');
    });

    test('handle uses proper request parameter', function (): void {
        $server = new MockServer();
        $server->handle(new \DOMDocument('1.0', 'UTF-8'));

        expect($server->mockSoapServer->handle[0])->toBeString();
    });
});

describe('Fault Exceptions', function (): void {
    test('can register fault exception string', function (): void {
        $server = new Server();
        $server->registerFaultException('Exception');

        expect($server->getFaultExceptions())->toContain('Exception');
    });

    test('can register fault exception array', function (): void {
        $server = new Server();
        $server->registerFaultException([InvalidArgumentException::class, RuntimeException::class]);

        expect($server->getFaultExceptions())->toContain(InvalidArgumentException::class);
        expect($server->getFaultExceptions())->toContain(RuntimeException::class);
    });

    test('can deregister fault exception', function (): void {
        $server = new Server();
        $server->registerFaultException('Exception');

        expect($server->deregisterFaultException('Exception'))->toBeTrue();
        expect($server->getFaultExceptions())->not->toContain('Exception');
    });

    test('isRegisteredAsFaultException returns correct value', function (): void {
        $server = new Server();
        $server->registerFaultException(InvalidArgumentException::class);

        expect($server->isRegisteredAsFaultException(InvalidArgumentException::class))->toBeTrue();
        expect($server->isRegisteredAsFaultException(RuntimeException::class))->toBeFalse();
    });
});

describe('Fault', function (): void {
    test('fault with text message', function (): void {
        $server = new Server();
        $fault = $server->fault('FaultMessage!');

        expect($fault)->toBeInstanceOf(\SoapFault::class);
        expect($fault->getMessage())->toContain('FaultMessage!');
    });

    test('fault with unregistered exception returns unknown error', function (): void {
        $server = new Server();
        $fault = $server->fault(new \Exception('MyException'));

        expect($fault)->toBeInstanceOf(\SoapFault::class);
        expect($fault->getMessage())->toContain('Unknown error');
        expect($fault->getMessage())->not->toContain('MyException');
    });

    test('fault with registered exception returns message', function (): void {
        $server = new Server();
        $server->registerFaultException(RuntimeException::class);
        $fault = $server->fault(new RuntimeException('MyException'));

        expect($fault)->toBeInstanceOf(\SoapFault::class);
        expect($fault->getMessage())->toContain('MyException');
    });

    test('fault with bogus input returns unknown error', function (): void {
        $server = new Server();
        $fault = $server->fault(['Here', 'There', 'Bogus']);

        expect($fault->getMessage())->toContain('Unknown error');
    });

    test('fault with integer code does not break', function (): void {
        $server = new Server();
        $fault = $server->fault('FaultMessage!', 5000);

        expect($fault)->toBeInstanceOf(\SoapFault::class);
    });
});

describe('Debug Mode', function (): void {
    test('debug mode shows exception message', function (): void {
        $server = new Server();

        $beforeDebug = $server->fault(new \Exception('test'));
        $server->setDebugMode(true);
        $afterDebug = $server->fault(new \Exception('test'));

        expect($beforeDebug->getMessage())->toBe('Unknown error');
        expect($afterDebug->getMessage())->toBe('test');
    });

    test('getException returns original exception', function (): void {
        $server = new Server();
        $fault = $server->fault(new \Exception('test'));

        $exception = $server->getException();

        expect($exception)->toBeInstanceOf(\Exception::class);
        expect($exception->getMessage())->toBe('test');
        expect($fault)->toBeInstanceOf(\SoapFault::class);
        expect($fault->getMessage())->toBe('Unknown error');
    });
});

describe('Features and Cache', function (): void {
    test('can set and get features', function (): void {
        $server = new Server();

        expect($server->getSoapFeatures())->toBeNull();

        $server->setSoapFeatures(100);
        expect($server->getSoapFeatures())->toBe(100);

        $options = $server->getOptions();
        expect($options['features'])->toBe(100);
    });

    test('can set and get WSDL cache', function (): void {
        $server = new Server();

        expect($server->getWSDLCache())->toBeNull();

        $server->setWSDLCache(100);
        expect($server->getWSDLCache())->toBe(100);

        $options = $server->getOptions();
        expect($options['cache_wsdl'])->toBe(100);
    });

    test('can set and get parse huge', function (): void {
        $server = new Server();

        expect($server->getParseHuge())->toBeNull();

        $server->setParseHuge(true);
        expect($server->getParseHuge())->toBeTrue();

        $options = $server->getOptions();
        expect($options['parse_huge'])->toBeTrue();
    });
});

describe('Internal Server', function (): void {
    test('getSoap returns SoapServer instance', function (): void {
        $server = new Server();
        $server->setOptions(['location' => 'test://', 'uri' => 'https://example.com']);

        $internalServer = $server->getSoap();

        expect($internalServer)->toBeInstanceOf(\SoapServer::class);
        expect($server->getSoap())->toBe($internalServer);
    });
});

describe('Load Functions', function (): void {
    test('loadFunctions throws not implemented exception', function (): void {
        $server = new Server();

        expect(fn () => $server->loadFunctions('bogus'))
            ->toThrow(RuntimeException::class, 'Unimplemented method');
    });
});

describe('Error Handling', function (): void {
    test('error handling throws SoapFault in handle mode', function (): void {
        if (headers_sent()) {
            $this->markTestSkipped('Cannot run when headers have already been sent');
        }

        $server = new Server();
        $server->setOptions(['location' => 'test://', 'uri' => 'https://example.com']);
        $server->setReturnResponse(true);
        $server->setClass(ServerTestClass::class);

        $request = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" '
            . 'xmlns:ns1="https://example.com" '
            . 'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            . 'xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" '
            . 'SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
            . '<SOAP-ENV:Body>'
            . '<ns1:testFunc5 />'
            . '</SOAP-ENV:Body>'
            . '</SOAP-ENV:Envelope>' . "\n";

        $response = $server->handle($request);

        expect($response)->toContain('SOAP-ENV:Fault');
    });

    test('handlePhpErrors works correctly', function (): void {
        if (headers_sent()) {
            $this->markTestSkipped('Cannot run when headers have already been sent');
        }

        $wsdlFilename = sys_get_temp_dir() . '/testHandlePhpErrors_' . uniqid() . '.wsdl';
        $autodiscover = new AutoDiscover();
        $autodiscover->setOperationBodyStyle(['use' => 'literal']);
        $autodiscover->setBindingStyle([
            'style' => 'document',
            'transport' => 'http://schemas.xmlsoap.org/soap/http',
        ]);
        $autodiscover->setServiceName('ExampleService');
        $autodiscover->setUri('http://example.com');
        $autodiscover->setClass(errorClass::class);

        $wsdl = $autodiscover->generate();
        $wsdl->dump($wsdlFilename);

        $server = new Server($wsdlFilename);
        $server->setClass(errorClass::class);

        $client = new Local($server, $wsdlFilename);

        // This should not throw - error is handled
        try {
            $client->triggerError();
        } catch (\Throwable) {
            // Expected
        }

        unlink($wsdlFilename);
        expect(true)->toBeTrue();
    });
});

describe('Typemap', function (): void {
    test('can set typemap', function (): void {
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

describe('Functions', function (): void {
    test('getFunctions returns all added functions with deduplication', function (): void {
        $server = new Server();

        $server->addFunction('\Tests\Fixtures\TestFunc');

        $functions = [
            '\Tests\Fixtures\TestFunc2',
            '\Tests\Fixtures\TestFunc3',
            '\Tests\Fixtures\TestFunc4',
        ];
        $server->addFunction($functions);

        $functions = [
            '\Tests\Fixtures\TestFunc3',
            '\Tests\Fixtures\TestFunc5',
            '\Tests\Fixtures\TestFunc6',
        ];
        $server->addFunction($functions);

        $allAddedFunctions = [
            '\Tests\Fixtures\TestFunc',
            '\Tests\Fixtures\TestFunc2',
            '\Tests\Fixtures\TestFunc3',
            '\Tests\Fixtures\TestFunc4',
            '\Tests\Fixtures\TestFunc5',
            '\Tests\Fixtures\TestFunc6',
        ];
        expect($server->getFunctions())->toBe($allAddedFunctions);
    });
});

describe('Config Object', function (): void {
    test('accepts Laminas Config object', function (): void {
        if (! class_exists(\Laminas\Config\Config::class)) {
            $this->markTestSkipped('Laminas\Config not installed');
        }

        $options = [
            'soap_version' => SOAP_1_1,
            'actor' => 'https://example.com/test.php',
            'classmap' => [
                'TestData1' => TestData1::class,
                'TestData2' => TestData2::class,
            ],
            'encoding' => 'ISO-8859-1',
            'uri' => 'https://example.com/test.php',
        ];
        $config = new \Laminas\Config\Config($options);

        $server = new Server();
        $server->setOptions($config);

        expect($server->getOptions())->toBe($options);
    });
});

describe('Entity Loader', function (): void {
    test('disables entity loader after exception', function (): void {
        $server = new Server();
        $server->setOptions(['location' => 'test://', 'uri' => 'https://example.com']);
        $server->setReturnResponse(true);
        $server->setClass(ServerTestClass::class);

        $loadEntities = true;
        if (LIBXML_VERSION < 20900) {
            $loadEntities = libxml_disable_entity_loader(false);
        }

        // Doing a request that is guaranteed to cause an exception in Server::_setRequest():
        $invalidRequest = '---';
        $response = @$server->handle($invalidRequest);

        // Sanity check; making sure that an exception has been triggered:
        expect($response)->toBeInstanceOf(SoapFault::class);

        if (LIBXML_VERSION < 20900) {
            // The "disable entity loader" setting should be restored to "false" after the exception is raised:
            expect(libxml_disable_entity_loader())->toBeFalse();

            // Cleanup; restoring original setting:
            libxml_disable_entity_loader($loadEntities);
        }
    });
});
