<?php // phpcs:disable

namespace Tests\Fixtures;

use ReturnTypeWillChange;

/* Test Functions */

/**
 * Test Function
 *
 * @param string $arg
 * @return string
 */
function TestFunc($who)
{
    return "Hello $who";
}

/**
 * Test Function 2
 */
function TestFunc2()
{
    return "Hello World";
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
    return "string";
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
    $return = (object) ['foo' => 'bar', 'baz' => true, 'bat' => 123, 'qux' => false];
    return $return;
}

/**
 * Multiple Args
 *
 * @param string $foo
 * @param string $bar
 * @return string
 */
function TestFunc9($foo, $bar)
{
    return "$foo $bar";
}

class TestFixingMultiplePrototypes
{
    /**
     * Test function
     *
     * @param integer $a
     * @param integer $b
     * @param integer $d
     * @return integer
     */
    public function testFunc($a = 100, $b = 200, $d = 300)
    {

    }
}

class Test
{
    /**
     * Test Function 1
     *
     * @return string
     */
    public function testFunc1()
    {
        return "Hello World";
    }

    /**
     * Test Function 2
     *
     * @param string $who Some Arg
     * @return string
     */
    public function testFunc2($who)
    {
        return "Hello $who!";
    }

    /**
     * Test Function 3
     *
     * @param string $who Some Arg
     * @param int $when Some
     * @return string
     */
    public function testFunc3($who, $when)
    {
        return "Hello $who, How are you $when";
    }

    /**
     * Test Function 4
     *
     * @return string
     */
    public static function testFunc4()
    {
        return "I'm Static!";
    }
}

class AutoDiscoverTestClass1
{
    /**
     * @var integer $var
     */
    public $var = 1;

    /**
     * @var string $param
     */
    public $param = "hello";
}

class AutoDiscoverTestClass2
{
    /**
     *
     * @param \Tests\Fixtures\AutoDiscoverTestClass1 $test
     * @return bool
     */
    public function add(AutoDiscoverTestClass1 $test)
    {
        return true;
    }

    /**
     * @return \Tests\Fixtures\AutoDiscoverTestClass1[]
     */
    public function fetchAll()
    {
        return [
            new AutoDiscoverTestClass1(),
            new AutoDiscoverTestClass1(),
        ];
    }

    /**
     * @param \Tests\Fixtures\AutoDiscoverTestClass1[]
     */
    public function addMultiple($test)
    {

    }
}

class ComplexTypeB
{
    /**
     * @var string
     */
    public $bar;
    /**
     * @var string
     */
    public $foo;
}

class ComplexTypeA
{
    /**
     * @var \Tests\Fixtures\ComplexTypeB[]
     */
    public $baz = [];
}

class ComplexTest
{
    /**
     * @var int
     */
    public $var = 5;
}

class ComplexObjectStructure
{
    /**
     * @var bool
     */
    public $boolean = true;

    /**
     * @var string
     */
    public $string = "Hello World";

    /**
     * @var int
     */
    public $int = 10;

    /**
     * @var array
     */
    public $array = [1, 2, 3];
}

class ComplexObjectWithObjectStructure
{
    /**
     * @var \Tests\Fixtures\ComplexTest
     */
    public $object;
}

class MyService
{
    /**
     *    @param string $foo
     *    @return \Tests\Fixtures\MyResponse[]
     */
    public function foo($foo)
    {
    }
    /**
     *    @param string $bar
     *    @return \Tests\Fixtures\MyResponse[]
     */
    public function bar($bar)
    {
    }

    /**
     *    @param string $baz
     *    @return \Tests\Fixtures\MyResponse[]
     */
    public function baz($baz)
    {
    }
}

class MyServiceSequence
{
    /**
     *    @param string $foo
     *    @return string[]
     */
    public function foo($foo)
    {
    }
    /**
     *    @param string $bar
     *    @return string[]
     */
    public function bar($bar)
    {
    }

    /**
     *    @param string $baz
     *    @return string[]
     */
    public function baz($baz)
    {
    }

    /**
     *    @param string $baz
     *    @return string[][][]
     */
    public function bazNested($baz)
    {
    }
}

class MyResponse
{
    /**
     * @var string
     */
    public $p1;
}

class Recursion
{
    /**
     * @var \Tests\Fixtures\Recursion
     */
    public $recursion;

    /**
     * @return \Tests\Fixtures\Recursion
     */
    public function create()
    {
    }
}

/**
 * @param string $message
 */
function OneWay($message)
{

}

class NoReturnType
{
    /**
     *
     * @param string $message
     */
    public function pushOneWay($message)
    {

    }
}

/* Client test classes */
/** Test Class */
class TestClass
{
    /**
     * Test Function 1
     *
     * @return string
     */
    public function testFunc1()
    {
        return "Hello World";
    }

    /**
     * Test Function 2
     *
     * @param string $who Some Arg
     * @return string
     */
    public function testFunc2($who)
    {
        return "Hello $who!";
    }

    /**
     * Test Function 3
     *
     * @param string $who Some Arg
     * @param int $when Some
     * @return string
     */
    public function testFunc3($who, $when)
    {
        return "Hello $who, How are you $when";
    }

    /**
     * Test Function 4
     *
     * @return string
     */
    public static function testFunc4()
    {
        return "I'm Static!";
    }
}

/** Test class 2 */
class TestData1
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
class TestData2
{
    /**
     * Property1
     *
     * @var integer
     */
    public $property1;

    /**
     * Property1
     *
     * @var float
     */
    public $property2;
}

class MockSoapServer
{
    public $handle = null;
    public function handle()
    {
        $this->handle = func_get_args();
    }
    public function __call($name, $args)
    {
    }
}

class MockServer extends \Cline\Soap\Server
{
    public $mockSoapServer = null;
    public function getSoap()
    {
        $this->mockSoapServer = new MockSoapServer();
        return $this->mockSoapServer;
    }
}


/** Server test classes */
class ServerTestClass
{
    /**
     * Test Function 1
     *
     * @return string
     */
    public function testFunc1()
    {
        return "Hello World";
    }

    /**
     * Test Function 2
     *
     * @param string $who Some Arg
     * @return string
     */
    public function testFunc2($who)
    {
        return "Hello $who!";
    }

    /**
     * Test Function 3
     *
     * @param string $who Some Arg
     * @param int $when Some
     * @return string
     */
    public function testFunc3($who, $when)
    {
        return "Hello $who, How are you $when";
    }

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
     * Test Function 5 raises a user error
     *
     * @return void
     */
    public function testFunc5()
    {
        trigger_error("Test Message", E_USER_ERROR);
    }
}

if (extension_loaded('soap')) {

    /** Local SOAP client */
    class TestLocalSoapClient extends \SoapClient
    {
        /**
         * Server object
         *
         * @var \Cline\Soap\Server
         */
        public $server;

        /**
         * Local client constructor
         *
         * @param Laminas_Soap_Server $server
         * @param string $wsdl
         * @param array $options
         */
        public function __construct(\Cline\Soap\Server $server, $wsdl, $options)
        {
            $this->server = $server;
            parent::__construct($wsdl, $options);
        }

        public function __doRequest(
            string $request,
            string $location,
            string $action,
            int $version,
            bool $oneWay = false,
            ?string $uriParserClass = null
        ): ?string {
            ob_start();
            $this->server->handle($request);
            $response = ob_get_clean();

            return $response;
        }
    }

}

class SequenceTest
{
    /**
     * @var int
     */
    public $var = 5;
}

class Book
{
    /**
     * @var int
     */
    public $somevar;
}

class Cookie
{
    /**
     * @var int
     */
    public $othervar;
}

class Anything
{
}

class PublicPrivateProtected
{
    const PROTECTED_VAR_NAME = 'bar';
    const PRIVATE_VAR_NAME = 'baz';

    /**
     * @var string
     */
    public $foo;

    /**
     * @var string
     */
    protected $bar;

    /**
     * @var string
     */
    private $baz;
}

class errorClass
{
    public function triggerError()
    {
        trigger_error('TestError', E_USER_ERROR);
    }
}
