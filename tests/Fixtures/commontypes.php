<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

use ReturnTypeWillChange;
use SoapClient;

use const E_USER_ERROR;

use function extension_loaded;
use function func_get_args;
use function ob_get_clean;
use function ob_start;
use function trigger_error;

/* Test Functions */

/**
 * Test Function
 *
 * @param  mixed  $who
 * @return string
 */
function TestFunc($who)
{
    return "Hello {$who}";
}

/**
 * Test Function 2
 */
function TestFunc2()
{
    return 'Hello World';
}

/**
 * Return false
 *
 * @return bool
 */
function TestFunc3()
{
    return false;
}

/**
 * Return true
 *
 * @return bool
 */
function TestFunc4()
{
    return true;
}

/**
 * Return integer
 *
 * @return int
 */
function TestFunc5()
{
    return 123;
}

/**
 * Return string
 *
 * @return string
 */
function TestFunc6()
{
    return 'string';
}

/**
 * Return array
 *
 * @return array
 */
function TestFunc7()
{
    return ['foo' => 'bar', 'baz' => true, 1 => false, 'bat' => 123];
}

/**
 * Return Object
 *
 * @return stdClass
 */
function TestFunc8()
{
    return (object) ['foo' => 'bar', 'baz' => true, 'bat' => 123, 'qux' => false];
}

/**
 * Multiple Args
 *
 * @param  string $foo
 * @param  string $bar
 * @return string
 */
function TestFunc9($foo, $bar)
{
    return "{$foo} {$bar}";
}

final class TestFixingMultiplePrototypes
{
    /**
     * Test function
     *
     * @param  int $a
     * @param  int $b
     * @param  int $d
     * @return int
     */
    public function testFunc($a = 100, $b = 200, $d = 300) {}
}

final class Test
{
    /**
     * Test Function 4
     *
     * @return string
     */
    public static function testFunc4()
    {
        return "I'm Static!";
    }

    /**
     * Test Function 1
     *
     * @return string
     */
    public function testFunc1()
    {
        return 'Hello World';
    }

    /**
     * Test Function 2
     *
     * @param  string $who Some Arg
     * @return string
     */
    public function testFunc2($who)
    {
        return "Hello {$who}!";
    }

    /**
     * Test Function 3
     *
     * @param  string $who  Some Arg
     * @param  int    $when Some
     * @return string
     */
    public function testFunc3($who, $when)
    {
        return "Hello {$who}, How are you {$when}";
    }
}

final class AutoDiscoverTestClass1
{
    /** @var int */
    public $var = 1;

    /** @var string */
    public $param = 'hello';
}

final class AutoDiscoverTestClass2
{
    /**
     * @return bool
     */
    public function add(AutoDiscoverTestClass1 $test)
    {
        return true;
    }

    /**
     * @return array<AutoDiscoverTestClass1>
     */
    public function fetchAll()
    {
        return [
            new AutoDiscoverTestClass1(),
            new AutoDiscoverTestClass1(),
        ];
    }

    /**
     * @param mixed $test
     */
    public function addMultiple($test): void {}
}

final class ComplexTypeB
{
    /** @var string */
    public $bar;

    /** @var string */
    public $foo;
}

final class ComplexTypeA
{
    /** @var array<ComplexTypeB> */
    public $baz = [];
}

final class ComplexTest
{
    /** @var int */
    public $var = 5;
}

final class ComplexObjectStructure
{
    /** @var bool */
    public $boolean = true;

    /** @var string */
    public $string = 'Hello World';

    /** @var int */
    public $int = 10;

    /** @var array */
    public $array = [1, 2, 3];
}

final class ComplexObjectWithObjectStructure
{
    /** @var ComplexTest */
    public $object;
}

final class MyService
{
    /**
     * @param  string            $foo
     * @return array<MyResponse>
     */
    public function foo($foo) {}

    /**
     * @param  string            $bar
     * @return array<MyResponse>
     */
    public function bar($bar) {}

    /**
     * @param  string            $baz
     * @return array<MyResponse>
     */
    public function baz($baz) {}
}

final class MyServiceSequence
{
    /**
     * @param  string        $foo
     * @return array<string>
     */
    public function foo($foo) {}

    /**
     * @param  string        $bar
     * @return array<string>
     */
    public function bar($bar) {}

    /**
     * @param  string        $baz
     * @return array<string>
     */
    public function baz($baz) {}

    /**
     * @param  string                      $baz
     * @return array<array<array<string>>>
     */
    public function bazNested($baz) {}
}

final class MyResponse
{
    /** @var string */
    public $p1;
}

final class Recursion
{
    /** @var self */
    public $recursion;

    /**
     * @return self
     */
    public function create() {}
}

/**
 * @param string $message
 */
function OneWay($message): void {}

final class NoReturnType
{
    /**
     * @param string $message
     */
    public function pushOneWay($message): void {}
}

/* Client test classes */
/** Test Class */
final class TestClass
{
    /**
     * Test Function 4
     *
     * @return string
     */
    public static function testFunc4()
    {
        return "I'm Static!";
    }

    /**
     * Test Function 1
     *
     * @return string
     */
    public function testFunc1()
    {
        return 'Hello World';
    }

    /**
     * Test Function 2
     *
     * @param  string $who Some Arg
     * @return string
     */
    public function testFunc2($who)
    {
        return "Hello {$who}!";
    }

    /**
     * Test Function 3
     *
     * @param  string $who  Some Arg
     * @param  int    $when Some
     * @return string
     */
    public function testFunc3($who, $when)
    {
        return "Hello {$who}, How are you {$when}";
    }
}

/** Test class 2 */
final class TestData1
{
    /**
     * Property1
     *
     * @var string
     */
    public $property1;

    /**
     * Property2
     *
     * @var float
     */
    public $property2;
}

/** Test class 2 */
final class TestData2
{
    /**
     * Property1
     *
     * @var int
     */
    public $property1;

    /**
     * Property1
     *
     * @var float
     */
    public $property2;
}

/** Server test classes */
final class ServerTestClass
{
    /**
     * Test Function 4
     *
     * @return string
     */
    public static function testFunc4()
    {
        return "I'm Static!";
    }

    /**
     * Test Function 1
     *
     * @return string
     */
    public function testFunc1()
    {
        return 'Hello World';
    }

    /**
     * Test Function 2
     *
     * @param  string $who Some Arg
     * @return string
     */
    public function testFunc2($who)
    {
        return "Hello {$who}!";
    }

    /**
     * Test Function 3
     *
     * @param  string $who  Some Arg
     * @param  int    $when Some
     * @return string
     */
    public function testFunc3($who, $when)
    {
        return "Hello {$who}, How are you {$when}";
    }

    /**
     * Test Function 5 raises a user error
     */
    public function testFunc5(): void
    {
        trigger_error('Test Message', E_USER_ERROR);
    }
}

if (extension_loaded('soap')) {
    /** Local SOAP client */
    final class TestLocalSoapClient extends SoapClient
    {
        /**
         * Local client constructor
         *
         * @param Laminas_Soap_Server $server
         * @param string              $wsdl
         * @param array               $options
         */
        public function __construct(
            /**
             * Server object
             *
             * @var \Cline\Soap\Server
             */
            public readonly \Cline\Soap\Server $server,
            $wsdl,
            $options,
        ) {
            parent::__construct($wsdl, $options);
        }

        public function __doRequest(
            string $request,
            string $location,
            string $action,
            int $version,
            bool $oneWay = false,
            ?string $uriParserClass = null,
        ): ?string {
            ob_start();
            $this->server->handle($request);

            return ob_get_clean();
        }
    }
}

final class SequenceTest
{
    /** @var int */
    public $var = 5;
}

final class Book
{
    /** @var int */
    public $somevar;
}

final class Cookie
{
    /** @var int */
    public $othervar;
}

final class Anything {}

final class PublicPrivateProtected
{
    public const string PROTECTED_VAR_NAME = 'bar';

    public const string PRIVATE_VAR_NAME = 'baz';

    /** @var string */
    public $foo;

    /** @var string */
    protected $bar;

    /** @var string */
    private $baz;
}

final class errorClass
{
    public function triggerError(): void
    {
        trigger_error('TestError', E_USER_ERROR);
    }
}
