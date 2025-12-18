<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap;

use Cline\Soap\Contract\ServerInterface;
use Cline\Soap\Exception\ExtensionNotLoadedException;
use Cline\Soap\Exception\InvalidArgumentException;
use Cline\Soap\Exception\RuntimeException;
use DOMDocument;
use DOMNode;
use Exception;
use ReflectionClass;
use SimpleXMLElement;
use SoapFault;
use SoapServer;
use stdClass;
use Traversable;

use const E_USER_ERROR;
use const LIBXML_PARSEHUGE;
use const LIBXML_VERSION;
use const PHP_URL_SCHEME;
use const SOAP_1_1;
use const SOAP_1_2;
use const SOAP_FUNCTIONS_ALL;
use const SOAP_PERSISTENCE_REQUEST;
use const SOAP_PERSISTENCE_SESSION;
use const XML_DOCUMENT_TYPE_NODE;

use function array_merge;
use function array_search;
use function array_slice;
use function array_unique;
use function array_unshift;
use function class_exists;
use function extension_loaded;
use function file_get_contents;
use function func_get_args;
use function func_num_args;
use function function_exists;
use function get_class_methods;
use function gettype;
use function in_array;
use function ini_get;
use function ini_set;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function is_subclass_of;
use function iterator_to_array;
use function libxml_disable_entity_loader;
use function mb_strtolower;
use function mb_trim;
use function ob_get_clean;
use function ob_start;
use function parse_url;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function throw_if;
use function throw_unless;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class Server implements ServerInterface
{
    /**
     * Actor URI
     *
     * @var string URI
     */
    private ?string $actor = null;

    /**
     * Class registered with this server
     */
    private ?string $class = null;

    /**
     * Server instance
     */
    private ?SoapServer $server = null;

    /**
     * Arguments to pass to {@link $class} constructor
     */
    private array $classArgs = [];

    /**
     * Array of SOAP type => PHP class pairings for handling return/incoming values
     */
    private ?array $classmap = null;

    /**
     * Encoding
     */
    private ?string $encoding = null;

    /**
     * Registered fault exceptions
     */
    private array $faultExceptions = [];

    /**
     * Container for caught exception during business code execution
     */
    private ?Exception $caughtException = null;

    /**
     * SOAP Server Features
     */
    private ?int $features = null;

    /**
     * Functions registered with this server; may be either an array or the SOAP_FUNCTIONS_ALL constant
     */
    private array|int $functions = [];

    /**
     * Object registered with this server
     */
    private ?object $object = null;

    /**
     * Informs if the soap server is in debug mode
     */
    private bool $debug = false;

    /**
     * Persistence mode; should be one of the SOAP persistence constants
     */
    private ?int $persistence = null;

    /**
     * Request XML
     */
    private ?string $request = null;

    /**
     * Response XML
     */
    private string $response = '';

    /**
     * Flag: whether or not {@link handle()} should return a response instead of automatically emitting it.
     */
    private bool $returnResponse = false;

    /**
     * SOAP version to use; SOAP_1_2 by default, to allow processing of headers
     */
    private int $soapVersion = SOAP_1_2;

    /**
     * Array of type mappings
     */
    private ?array $typemap = null;

    /**
     * URI namespace for SOAP server
     *
     * @var string URI
     */
    private ?string $uri = null;

    /**
     * URI or path to WSDL
     */
    private ?string $wsdl = null;

    /**
     * WSDL Caching Options of SOAP Server
     */
    private bool|int|string|null $wsdlCache = null;

    /**
     * The send_errors Options of SOAP Server
     */
    private ?bool $sendErrors = null;

    /**
     * Allows LIBXML_PARSEHUGE Options of DOMDocument->loadXML( string $source [, int $options = 0 ] ) to be set
     */
    private ?bool $parseHuge = null;

    /**
     * Constructor
     *
     * Sets display_errors INI setting to off (prevent client errors due to bad
     * XML in response). Registers {@link handlePhpErrors()} as error handler
     * for E_USER_ERROR.
     *
     * If $wsdl is provided, it is passed on to {@link setWSDL()}; if any
     * options are specified, they are passed on to {@link setOptions()}.
     *
     * @throws ExtensionNotLoadedException
     */
    public function __construct(?string $wsdl = null, ?array $options = null)
    {
        throw_unless(extension_loaded('soap'), ExtensionNotLoadedException::class, 'SOAP extension is not loaded.');

        if (null !== $wsdl) {
            $this->setWSDL($wsdl);
        }

        if (null === $options) {
            return;
        }

        $this->setOptions($options);
    }

    /**
     * Set Options
     *
     * Allows setting options as an associative array of option => value pairs.
     */
    public function setOptions(array|Traversable $options): static
    {
        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }

        foreach ($options as $key => $value) {
            switch (mb_strtolower((string) $key)) {
                case 'actor':
                    $this->setActor($value);

                    break;

                case 'classmap':
                case 'class_map':
                    $this->setClassmap($value);

                    break;

                case 'typemap':
                case 'type_map':
                    $this->setTypemap($value);

                    break;

                case 'encoding':
                    $this->setEncoding($value);

                    break;

                case 'soapversion':
                case 'soap_version':
                    $this->setSoapVersion($value);

                    break;

                case 'uri':
                    $this->setUri($value);

                    break;

                case 'wsdl':
                    $this->setWSDL($value);

                    break;

                case 'cache_wsdl':
                    $this->setWSDLCache($value);

                    break;

                case 'features':
                    $this->setSoapFeatures($value);

                    break;

                case 'send_errors':
                    $this->setSendErrors($value);

                    break;

                case 'parse_huge':
                    $this->setParseHuge($value);

                    break;

                default:
                    break;
            }
        }

        return $this;
    }

    /**
     * Return array of options suitable for using with SoapServer constructor
     */
    public function getOptions(): array
    {
        $options = [];

        if (null !== $this->actor) {
            $options['actor'] = $this->actor;
        }

        if (null !== $this->classmap) {
            $options['classmap'] = $this->classmap;
        }

        if (null !== $this->typemap) {
            $options['typemap'] = $this->typemap;
        }

        if (null !== $this->encoding) {
            $options['encoding'] = $this->encoding;
        }

        if (null !== $this->soapVersion) {
            $options['soap_version'] = $this->soapVersion;
        }

        if (null !== $this->uri) {
            $options['uri'] = $this->uri;
        }

        if (null !== $this->features) {
            $options['features'] = $this->features;
        }

        if (null !== $this->wsdlCache) {
            $options['cache_wsdl'] = $this->wsdlCache;
        }

        if (null !== $this->sendErrors) {
            $options['send_errors'] = $this->sendErrors;
        }

        if (null !== $this->parseHuge) {
            $options['parse_huge'] = $this->parseHuge;
        }

        return $options;
    }

    /**
     * Set encoding
     *
     * @throws InvalidArgumentException With invalid encoding argument.
     */
    public function setEncoding(string $encoding): static
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * Get encoding
     */
    public function getEncoding(): ?string
    {
        return $this->encoding;
    }

    /**
     * Set SOAP version
     *
     * @param  int                      $version One of the SOAP_1_1 or SOAP_1_2 constants
     * @throws InvalidArgumentException With invalid soap version argument.
     */
    public function setSoapVersion(int $version): static
    {
        throw_unless(in_array($version, [SOAP_1_1, SOAP_1_2], true), InvalidArgumentException::class, 'Invalid soap version specified');

        $this->soapVersion = $version;

        return $this;
    }

    /**
     * Get SOAP version
     */
    public function getSoapVersion(): int
    {
        return $this->soapVersion;
    }

    /**
     * Check for valid URN
     *
     * @throws InvalidArgumentException On invalid URN.
     * @return true
     */
    public function validateUrn(string $urn): bool
    {
        $scheme = parse_url($urn, PHP_URL_SCHEME);

        throw_if($scheme === false || $scheme === null, InvalidArgumentException::class, 'Invalid URN');

        return true;
    }

    /**
     * Set actor
     *
     * Actor is the actor URI for the server.
     */
    public function setActor(string $actor): static
    {
        $this->validateUrn($actor);
        $this->actor = $actor;

        return $this;
    }

    /**
     * Retrieve actor
     */
    public function getActor(): ?string
    {
        return $this->actor;
    }

    /**
     * Set URI
     *
     * URI in SoapServer is actually the target namespace, not a URI; $uri must begin with 'urn:'.
     */
    public function setUri(string $uri): static
    {
        $this->validateUrn($uri);
        $this->uri = $uri;

        return $this;
    }

    /**
     * Retrieve URI
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * Set classmap
     *
     * @throws InvalidArgumentException For any invalid class in the class map.
     */
    public function setClassmap(array $classmap): static
    {
        foreach ($classmap as $class) {
            throw_unless(class_exists($class), InvalidArgumentException::class, 'Invalid class in class map');
        }

        $this->classmap = $classmap;

        return $this;
    }

    /**
     * Retrieve classmap
     */
    public function getClassmap(): ?array
    {
        return $this->classmap;
    }

    /**
     * Set typemap with xml to php type mappings with appropriate validation.
     *
     * @throws InvalidArgumentException
     */
    public function setTypemap(array $typeMap): static
    {
        foreach ($typeMap as $type) {
            if (!is_callable($type['from_xml'])) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid from_xml callback for type: %s',
                    $type['type_name'],
                ));
            }

            if (!is_callable($type['to_xml'])) {
                throw new InvalidArgumentException('Invalid to_xml callback for type: '.$type['type_name']);
            }
        }

        $this->typemap = $typeMap;

        return $this;
    }

    /**
     * Retrieve typemap
     */
    public function getTypemap(): ?array
    {
        return $this->typemap;
    }

    /**
     * Set wsdl
     *
     * @param string $wsdl URI or path to a WSDL
     */
    public function setWSDL(string $wsdl): static
    {
        $this->wsdl = $wsdl;

        return $this;
    }

    /**
     * Retrieve wsdl
     */
    public function getWSDL(): ?string
    {
        return $this->wsdl;
    }

    /**
     * Set the SOAP Feature options.
     */
    public function setSoapFeatures(int|string $feature): static
    {
        $this->features = $feature;

        return $this;
    }

    /**
     * Return current SOAP Features options
     */
    public function getSoapFeatures(): ?int
    {
        return $this->features;
    }

    /**
     * Set the SOAP WSDL Caching Options
     */
    public function setWSDLCache(bool|int|string $options): static
    {
        $this->wsdlCache = $options;

        return $this;
    }

    /**
     * Get current SOAP WSDL Caching option
     */
    public function getWSDLCache(): bool|int|string|null
    {
        return $this->wsdlCache;
    }

    /**
     * Set the SOAP send_errors Option
     */
    public function setSendErrors(bool $sendErrors): static
    {
        $this->sendErrors = $sendErrors;

        return $this;
    }

    /**
     * Get current SOAP send_errors option
     */
    public function getSendErrors(): ?bool
    {
        return $this->sendErrors;
    }

    /**
     * Set flag to allow DOMDocument->loadXML() to parse huge nodes
     */
    public function setParseHuge(bool $parseHuge): static
    {
        $this->parseHuge = $parseHuge;

        return $this;
    }

    /**
     * Get flag to allow DOMDocument->loadXML() to parse huge nodes
     */
    public function getParseHuge(): ?bool
    {
        return $this->parseHuge;
    }

    /**
     * Attach a function as a server method
     *
     * @param  array|string             $function  Function name, array of function names to attach,
     *                                             or SOAP_FUNCTIONS_ALL to attach all functions
     * @param  string                   $namespace Ignored
     * @throws InvalidArgumentException On invalid functions.
     */
    public function addFunction(array|int|string $function, string $namespace = ''): static
    {
        // Bail early if set to SOAP_FUNCTIONS_ALL
        if ($this->functions === SOAP_FUNCTIONS_ALL) {
            return $this;
        }

        if (is_array($function)) {
            foreach ($function as $func) {
                throw_if(!is_string($func) || !function_exists($func), InvalidArgumentException::class, 'One or more invalid functions specified in array');

                $this->functions[] = $func;
            }
        } elseif (is_string($function) && function_exists($function)) {
            $this->functions[] = $function;
        } elseif ($function === SOAP_FUNCTIONS_ALL) {
            $this->functions = SOAP_FUNCTIONS_ALL;
        } else {
            throw new InvalidArgumentException('Invalid function specified');
        }

        if (is_array($this->functions)) {
            $this->functions = array_unique($this->functions);
        }

        return $this;
    }

    /**
     * Attach a class to a server
     *
     * Accepts a class name to use when handling requests. Any additional
     * arguments will be passed to that class' constructor when instantiated.
     *
     * See {@link setObject()} to set pre-configured object instances as request handlers.
     *
     * @param  object|string            $class Class name or object instance which executes
     *                                         SOAP Requests at endpoint.
     * @param  null|array               $argv
     * @throws InvalidArgumentException If called more than once, or if class does not exist.
     */
    public function setClass(object|string $class, string $namespace = '', mixed $argv = null): static
    {
        throw_if($this->class !== null, InvalidArgumentException::class, 'A class has already been registered with this soap server instance');

        if (is_object($class)) {
            return $this->setObject($class);
        }

        if (!is_string($class)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid class argument (%s)',
                gettype($class),
            ));
        }

        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf(
                'Class "%s" does not exist',
                $class,
            ));
        }

        $this->class = $class;

        if (2 < func_num_args()) {
            $argv = func_get_args();
            $this->classArgs = array_slice($argv, 2);
        }

        return $this;
    }

    /**
     * Attach an object to a server
     *
     * Accepts an instantiated object to use when handling requests.
     *
     * @throws InvalidArgumentException
     */
    public function setObject(object $object): static
    {
        throw_if($this->object !== null, InvalidArgumentException::class, 'An object has already been registered with this soap server instance');

        $this->object = $object;

        return $this;
    }

    /**
     * Return a server definition array
     *
     * Returns a list of all functions registered with {@link addFunction()},
     * merged with all public methods of the class set with {@link setClass()}
     * (if any).
     */
    public function getFunctions(): array
    {
        $functions = [];

        if (null !== $this->class) {
            $functions = get_class_methods($this->class);
        } elseif (null !== $this->object) {
            $functions = get_class_methods($this->object);
        }

        return array_merge((array) $this->functions, $functions);
    }

    /**
     * Unimplemented: Load server definition
     *
     * @param  array            $definition
     * @throws RuntimeException Unimplemented.
     */
    public function loadFunctions($definition): void
    {
        throw new RuntimeException('Unimplemented method.');
    }

    /**
     * Set server persistence
     *
     * @param  int                      $mode SOAP_PERSISTENCE_SESSION or SOAP_PERSISTENCE_REQUEST constants
     * @throws InvalidArgumentException
     */
    public function setPersistence(int $mode): static
    {
        throw_unless(in_array($mode, [SOAP_PERSISTENCE_SESSION, SOAP_PERSISTENCE_REQUEST], true), InvalidArgumentException::class, 'Invalid persistence mode specified');

        $this->persistence = $mode;

        return $this;
    }

    /**
     * Get server persistence
     */
    public function getPersistence(): ?int
    {
        return $this->persistence;
    }

    /**
     * Retrieve request XML
     */
    public function getLastRequest(): ?string
    {
        return $this->request;
    }

    /**
     * Set return response flag
     *
     * If true, {@link handle()} will return the response instead of
     * automatically sending it back to the requesting client.
     *
     * The response is always available via {@link getResponse()}.
     */
    public function setReturnResponse(bool $flag = true): static
    {
        $this->returnResponse = $flag;

        return $this;
    }

    /**
     * Retrieve return response flag
     */
    public function getReturnResponse(): bool
    {
        return $this->returnResponse;
    }

    /**
     * Get response XML
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * Get SoapServer object
     *
     * Uses {@link $wsdl} and return value of {@link getOptions()} to instantiate
     * SoapServer object, and then registers any functions or class with it, as
     * well as persistence.
     */
    public function getSoap(): SoapServer
    {
        if ($this->server instanceof SoapServer) {
            return $this->server;
        }

        $options = $this->getOptions();
        $server = new SoapServer($this->wsdl, $options);

        if ($this->functions !== 0 && $this->functions !== []) {
            $server->addFunction($this->functions);
        }

        if (!in_array($this->class, [null, '', '0'], true)) {
            $args = $this->classArgs;
            array_unshift($args, $this->class);
            $server->setClass(...$args);
        }

        if (!empty($this->object)) {
            $server->setObject($this->object);
        }

        if (null !== $this->persistence) {
            $server->setPersistence($this->persistence);
        }

        $this->server = $server;

        return $this->server;
    }

    /**
     * Proxy for _getSoap method
     *
     * @see _getSoap
    }
     * @return SoapServer the soapServer instance
     */

    /**
     * Handle a request
     *
     * Instantiates SoapServer object with options set in object, and
     * dispatches its handle() method.
     *
     * $request may be any of:
     * - DOMDocument; if so, then cast to XML
     * - DOMNode; if so, then grab owner document and cast to XML
     * - SimpleXMLElement; if so, then cast to XML
     * - stdClass; if so, calls __toString() and verifies XML
     * - string; if so, verifies XML
     *
     * If no request is passed, pulls request using php:://input (for
     * cross-platform compatibility purposes).
     *
     * @param  DOMDocument|DOMNode|SimpleXMLElement|stdClass|string $request Optional request
     * @return string|void
     */
    public function handle(mixed $request = null): mixed
    {
        if (null === $request) {
            $request = file_get_contents('php://input');
        }

        // Set Server error handler
        $displayErrorsOriginalState = $this->initializeSoapErrorContext();

        $setRequestException = null;

        try {
            $this->setRequest($request);
        } catch (Exception $exception) {
            $setRequestException = $exception;
        }

        $soap = $this->getSoap();

        $fault = false;
        $this->response = '';

        if ($setRequestException instanceof Exception) {
            // Create SOAP fault message if we've caught a request exception
            $fault = $this->fault($setRequestException->getMessage(), 'Sender');
        } else {
            ob_start();

            try {
                $soap->handle($this->request);
            } catch (Exception $e) {
                $fault = $this->fault($e);
            }

            $this->response = ob_get_clean();
        }

        // Restore original error handler
        restore_error_handler();
        ini_set('display_errors', $displayErrorsOriginalState);

        // Send a fault, if we have one
        if ($fault instanceof SoapFault && !$this->returnResponse) {
            $soap->fault($fault->faultcode, $fault->getMessage());

            return null;
        }

        // Echo the response, if we're not returning it
        if (!$this->returnResponse) {
            echo $this->response;

            return null;
        }

        // Return a fault, if we have it
        if ($fault instanceof SoapFault) {
            return $fault;
        }

        // Return the response
        return $this->response;
    }

    /**
     * Set the debug mode.
     * In debug mode, all exceptions are send to the client.
     */
    public function setDebugMode(bool $debug): static
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Validate and register fault exception
     *
     * @param  array|string             $class Exception class or array of exception classes
     * @throws InvalidArgumentException
     */
    public function registerFaultException(array|string $class): static
    {
        if (is_array($class)) {
            foreach ($class as $row) {
                $this->registerFaultException($row);
            }
        } elseif (
            is_string($class)
            && class_exists($class)
            && (is_subclass_of($class, 'Exception') || 'Exception' === $class)
        ) {
            $ref = new ReflectionClass($class);

            $this->faultExceptions[] = $ref->getName();
            $this->faultExceptions = array_unique($this->faultExceptions);
        } else {
            throw new InvalidArgumentException(
                'Argument for '.self::class.'::registerFaultException should be'
                .' string or array of strings with valid exception names',
            );
        }

        return $this;
    }

    /**
     * Checks if provided fault name is registered as valid in this server.
     *
     * @param string $fault Name of a fault class
     */
    public function isRegisteredAsFaultException(object|string $fault): bool
    {
        if ($this->debug) {
            return true;
        }

        $ref = new ReflectionClass($fault);
        $classNames = $ref->getName();

        return in_array($classNames, $this->faultExceptions, true);
    }

    /**
     * Deregister a fault exception from the fault exception stack
     */
    public function deregisterFaultException(string $class): bool
    {
        if (in_array($class, $this->faultExceptions, true)) {
            $index = array_search($class, $this->faultExceptions, true);
            unset($this->faultExceptions[$index]);

            return true;
        }

        return false;
    }

    /**
     * Return fault exceptions list
     */
    public function getFaultExceptions(): array
    {
        return $this->faultExceptions;
    }

    /**
     * Return caught exception during business code execution
     *
     * @return null|Exception caught exception
     */
    public function getException(): ?Exception
    {
        return $this->caughtException;
    }

    /**
     * Generate a server fault
     *
     * Note that the arguments are reverse to those of SoapFault.
     *
     * If an exception is passed as the first argument, its message and code
     * will be used to create the fault object if it has been registered via
     * {@Link registerFaultException()}.
     *
     * @see   http://www.w3.org/TR/soap12-part1/#faultcodes
     *
     * @param Exception|string $fault
     * @param string           $code  SOAP Fault Codes
     */
    public function fault(Exception|string|null $fault = null, string $code = 'Receiver'): SoapFault
    {
        $this->caughtException = is_string($fault) ? new Exception($fault) : $fault;

        if ($fault instanceof Exception) {
            if ($this->isRegisteredAsFaultException($fault)) {
                $message = $fault->getMessage();
                $eCode = $fault->getCode();
                $code = empty($eCode) ? $code : $eCode;
            } else {
                $message = 'Unknown error';
            }
        } elseif (is_string($fault)) {
            $message = $fault;
        } else {
            $message = 'Unknown error';
        }

        $allowedFaultModes = [
            'VersionMismatch',
            'MustUnderstand',
            'DataEncodingUnknown',
            'Sender',
            'Receiver',
            'Server',
        ];

        if (!in_array($code, $allowedFaultModes, true)) {
            $code = 'Receiver';
        }

        return new SoapFault($code, $message);
    }

    /**
     * Throw PHP errors as SoapFaults
     *
     * @throws SoapFault
     */
    public function handlePhpErrors(int $errno, string $errstr): never
    {
        throw $this->fault($errstr, 'Receiver');
    }

    /**
     * Set request
     *
     * $request may be any of:
     * - DOMDocument; if so, then cast to XML
     * - DOMNode; if so, then grab owner document and cast to XML
     * - SimpleXMLElement; if so, then cast to XML
     * - stdClass; if so, calls __toString() and verifies XML
     * - string; if so, verifies XML
     *
     * @param  DOMDocument|DOMNode|SimpleXMLElement|stdClass|string $request
     * @throws InvalidArgumentException
     */
    private function setRequest(mixed $request): static
    {
        $xml = null;

        if ($request instanceof DOMDocument) {
            $xml = $request->saveXML();
        } elseif ($request instanceof DOMNode) {
            $xml = $request->ownerDocument->saveXML();
        } elseif ($request instanceof SimpleXMLElement) {
            $xml = $request->asXML();
        } elseif (is_object($request) || is_string($request)) {
            $xml = is_object($request) ? $request->__toString() : $request;

            $xml = mb_trim($xml);
            throw_if($xml === '', InvalidArgumentException::class, 'Empty request');
            $loadEntities = $this->disableEntityLoader(true);
            $dom = new DOMDocument();
            $loadStatus = true === $this->parseHuge ? $dom->loadXML($xml, LIBXML_PARSEHUGE) : $dom->loadXML($xml);
            $this->disableEntityLoader($loadEntities);
            // @todo check libxml errors ? validate document ?
            throw_unless($loadStatus, InvalidArgumentException::class, 'Invalid XML');

            foreach ($dom->childNodes as $child) {
                throw_if($child->nodeType === XML_DOCUMENT_TYPE_NODE, InvalidArgumentException::class, 'Invalid XML: Detected use of illegal DOCTYPE');
            }
        }

        $this->request = $xml;

        return $this;
    }

    /**
     * Method initializes the error context that the SOAPServer environment will run in.
     *
     * @return bool display_errors original value
     */
    private function initializeSoapErrorContext(): string
    {
        $displayErrorsOriginalState = ini_get('display_errors');
        ini_set('display_errors', '0');
        set_error_handler($this->handlePhpErrors(...), E_USER_ERROR);

        return $displayErrorsOriginalState;
    }

    /**
     * Disable the ability to load external XML entities based on libxml version
     *
     * If we are using libxml < 2.9, unsafe XML entity loading must be
     * disabled with a flag.
     *
     * If we are using libxml >= 2.9, XML entity loading is disabled by default.
     */
    private function disableEntityLoader(bool $flag = true): bool
    {
        if (LIBXML_VERSION < 20_900) {
            return libxml_disable_entity_loader($flag);
        }

        return $flag;
    }
}
