<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap;

use Cline\Soap\AutoDiscover\DiscoveryStrategy\DiscoveryStrategyInterface as DiscoveryStrategy;
use Cline\Soap\AutoDiscover\DiscoveryStrategy\ReflectionDiscovery;
use Cline\Soap\Exception\InvalidArgumentException;
use Cline\Soap\Exception\RuntimeException;
use Cline\Soap\Reflection\Reflection;
use Cline\Soap\Wsdl;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ComplexTypeStrategyInterface as ComplexTypeStrategy;
use DOMElement;
use Uri\Rfc3986\Uri;

use const ENT_QUOTES;

use function array_unique;
use function count;
use function function_exists;
use function get_class;
use function gettype;
use function header;
use function htmlspecialchars;
use function is_array;
use function is_object;
use function is_string;
use function is_subclass_of;
use function mb_strlen;
use function mb_trim;
use function preg_match;
use function sprintf;

final class AutoDiscover
{
    /** @var string */
    protected $serviceName;

    /** @var Reflection */
    protected $reflection;

    /**
     * Service function names
     *
     * @var array
     */
    protected $functions = [];

    /**
     * Service class name
     *
     * @var string
     */
    protected $class;

    /** @var bool */
    protected $strategy;

    /**
     * Url where the WSDL file will be available at.
     *
     * @var Wsdl Uri
     */
    protected $uri;

    /**
     * soap:body operation style options
     *
     * @var array
     */
    protected $operationBodyStyle = [
        'use' => 'encoded',
        'encodingStyle' => 'http://schemas.xmlsoap.org/soap/encoding/',
    ];

    /**
     * soap:operation style
     *
     * @var array
     */
    protected $bindingStyle = [
        'style' => 'rpc',
        'transport' => 'http://schemas.xmlsoap.org/soap/http',
    ];

    /**
     * Name of the class to handle the WSDL creation.
     *
     * @var string
     */
    protected $wsdlClass = Wsdl::class;

    /**
     * Class Map of PHP to WSDL types.
     *
     * @var array
     */
    protected $classMap = [];

    /**
     * Discovery strategy for types and other method details.
     *
     * @var DiscoveryStrategy
     */
    protected $discoveryStrategy;

    /**
     * Constructor
     *
     * @param null|Rfc3986\Uri|string $endpointUri
     * @param null|string             $wsdlClass
     * @param null|array              $classMap
     */
    public function __construct(
        ?ComplexTypeStrategy $strategy = null,
        $endpointUri = null,
        $wsdlClass = null,
        array $classMap = [],
    ) {
        $this->reflection = new Reflection();
        $this->setDiscoveryStrategy(
            new ReflectionDiscovery(),
        );

        if (null !== $strategy) {
            $this->setComplexTypeStrategy($strategy);
        }

        if (null !== $endpointUri) {
            $this->setUri($endpointUri);
        }

        if (null !== $wsdlClass) {
            $this->setWsdlClass($wsdlClass);
        }
        $this->setClassMap($classMap);
    }

    /**
     * Set the discovery strategy for method type and other information.
     *
     * @return self
     */
    public function setDiscoveryStrategy(DiscoveryStrategy $discoveryStrategy)
    {
        $this->discoveryStrategy = $discoveryStrategy;

        return $this;
    }

    /**
     * Get the discovery strategy.
     *
     * @return DiscoveryStrategy
     */
    public function getDiscoveryStrategy()
    {
        return $this->discoveryStrategy;
    }

    /**
     * Get the class map of php to wsdl mappings.
     *
     * @return array
     */
    public function getClassMap()
    {
        return $this->classMap;
    }

    /**
     * Set the class map of php to wsdl mappings.
     *
     * @param  array                    $classMap
     * @throws InvalidArgumentException
     * @return self
     */
    public function setClassMap($classMap)
    {
        if (!is_array($classMap)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects an array; received "%s"',
                __METHOD__,
                is_object($classMap) ? $classMap::class : gettype($classMap),
            ));
        }

        $this->classMap = $classMap;

        return $this;
    }

    /**
     * Set service name
     *
     * @param  string                   $serviceName
     * @throws InvalidArgumentException
     * @return self
     */
    public function setServiceName($serviceName)
    {
        $matches = [];

        // first character must be letter or underscore {@see http://www.w3.org/TR/wsdl#_document-n}
        $i = preg_match('/^[a-z\_]/ims', $serviceName, $matches);

        if ($i !== 1) {
            throw new InvalidArgumentException('Service Name must start with letter or _');
        }

        $this->serviceName = $serviceName;

        return $this;
    }

    /**
     * Get service name
     *
     * @throws RuntimeException
     * @return string
     */
    public function getServiceName()
    {
        if (!$this->serviceName) {
            if ($this->class) {
                return $this->reflection->reflectClass($this->class)->getShortName();
            }

            throw new RuntimeException('No service name given. Call AutoDiscover::setServiceName().');
        }

        return $this->serviceName;
    }

    /**
     * Set the location at which the WSDL file will be available.
     *
     * @param  Rfc3986\Uri|string       $uri
     * @throws InvalidArgumentException
     * @return self
     */
    public function setUri($uri)
    {
        if (!is_string($uri) && !$uri instanceof Rfc3986\Uri) {
            throw new InvalidArgumentException(
                'Argument to \Cline\Soap\AutoDiscover::setUri should be string or \Uri\Rfc3986\Uri instance.',
            );
        }

        if ($uri instanceof Rfc3986\Uri) {
            $this->uri = $uri;

            return $this;
        }

        $uri = mb_trim($uri);
        $uri = htmlspecialchars($uri, ENT_QUOTES, 'UTF-8', false);

        if (empty($uri)) {
            throw new InvalidArgumentException('Uri contains invalid characters or is empty');
        }

        $this->uri = $uri;

        return $this;
    }

    /**
     * Return the current Uri that the SOAP WSDL Service will be located at.
     *
     * @throws RuntimeException
     * @return Rfc3986\Uri
     */
    public function getUri()
    {
        if ($this->uri === null) {
            throw new RuntimeException(
                'Missing uri. You have to explicitly configure the Endpoint Uri by calling AutoDiscover::setUri().',
            );
        }

        if (is_string($this->uri)) {
            $this->uri = new Uri($this->uri);
        }

        return $this->uri;
    }

    /**
     * Set the name of the WSDL handling class.
     *
     * @param  string                   $wsdlClass
     * @throws InvalidArgumentException
     * @return self
     */
    public function setWsdlClass($wsdlClass)
    {
        if (!is_string($wsdlClass) && !is_subclass_of($wsdlClass, Wsdl::class)) {
            throw new InvalidArgumentException(
                'No \Cline\Soap\Wsdl subclass given to Cline\Soap\AutoDiscover::setWsdlClass as string.',
            );
        }

        $this->wsdlClass = $wsdlClass;

        return $this;
    }

    /**
     * Return the name of the WSDL handling class.
     *
     * @return string
     */
    public function getWsdlClass()
    {
        return $this->wsdlClass;
    }

    /**
     * Set options for all the binding operations soap:body elements.
     *
     * By default the options are set to 'use' => 'encoded' and
     * 'encodingStyle' => "http://schemas.xmlsoap.org/soap/encoding/".
     *
     * @throws InvalidArgumentException
     * @return self
     */
    public function setOperationBodyStyle(array $operationStyle = [])
    {
        if (!isset($operationStyle['use'])) {
            throw new InvalidArgumentException('Key "use" is required in Operation soap:body style.');
        }
        $this->operationBodyStyle = $operationStyle;

        return $this;
    }

    /**
     * Set Binding soap:binding style.
     *
     * By default 'style' is 'rpc' and 'transport' is 'http://schemas.xmlsoap.org/soap/http'.
     *
     * @return self
     */
    public function setBindingStyle(array $bindingStyle = [])
    {
        if (isset($bindingStyle['style'])) {
            $this->bindingStyle['style'] = $bindingStyle['style'];
        }

        if (isset($bindingStyle['transport'])) {
            $this->bindingStyle['transport'] = $bindingStyle['transport'];
        }

        return $this;
    }

    /**
     * Set the strategy that handles functions and classes that are added AFTER this call.
     *
     * @return self
     */
    public function setComplexTypeStrategy(ComplexTypeStrategy $strategy)
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Set the Class the SOAP server will use
     *
     * @param  string $class Class Name
     * @return self
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Add a Single or Multiple Functions to the WSDL
     *
     * @param  string                   $function Function Name
     * @throws InvalidArgumentException
     * @return self
     */
    public function addFunction($function)
    {
        if (is_array($function)) {
            foreach ($function as $row) {
                $this->addFunction($row);
            }
        } elseif (is_string($function)) {
            if (!function_exists($function)) {
                throw new InvalidArgumentException(
                    'Argument to Cline\Soap\AutoDiscover::addFunction should be a valid function name.',
                );
            }

            $this->functions[] = $function;
        } else {
            throw new InvalidArgumentException(
                'Argument to Cline\Soap\AutoDiscover::addFunction should be string or array of strings.',
            );
        }

        return $this;
    }

    /**
     * Generate the WSDL file from the configured input.
     *
     * @throws RuntimeException
     * @return Wsdl
     */
    public function generate()
    {
        if ($this->class && $this->functions) {
            throw new RuntimeException('Can either dump functions or a class as a service, not both.');
        }

        if ($this->class) {
            $wsdl = $this->generateClass();
        } else {
            $wsdl = $this->generateFunctions();
        }

        return $wsdl;
    }

    /**
     * Proxy to WSDL dump function
     *
     * @param  string           $filename
     * @throws RuntimeException
     * @return bool
     */
    public function dump($filename)
    {
        return $this->generate()->dump($filename);
    }

    /**
     * Proxy to WSDL toXml() function
     *
     * @throws RuntimeException
     * @return string
     */
    public function toXml()
    {
        return $this->generate()->toXml();
    }

    /**
     * Handle WSDL document.
     */
    public function handle(): void
    {
        header('Content-Type: text/xml');
        echo $this->toXml();
    }

    /**
     * Generate the WSDL for a service class.
     *
     * @return Wsdl
     */
    protected function generateClass()
    {
        return $this->generateWsdl($this->reflection->reflectClass($this->class)->getMethods());
    }

    /**
     * Generate the WSDL for a set of functions.
     *
     * @return Wsdl
     */
    protected function generateFunctions()
    {
        $methods = [];

        foreach (array_unique($this->functions) as $func) {
            $methods[] = $this->reflection->reflectFunction($func);
        }

        return $this->generateWsdl($methods);
    }

    /**
     * Generate the WSDL for a set of reflection method instances.
     *
     * @return Wsdl
     */
    protected function generateWsdl(array $reflectionMethods)
    {
        $uri = $this->getUri();

        $serviceName = $this->getServiceName();

        $wsdl = new $this->wsdlClass($serviceName, $uri, $this->strategy, $this->classMap);

        // The wsdl:types element must precede all other elements (WS-I Basic Profile 1.1 R2023)
        $wsdl->addSchemaTypeSection();

        $port = $wsdl->addPortType($serviceName.'Port');
        $binding = $wsdl->addBinding($serviceName.'Binding', Wsdl::TYPES_NS.':'.$serviceName.'Port');

        $wsdl->addSoapBinding($binding, $this->bindingStyle['style'], $this->bindingStyle['transport']);
        $wsdl->addService(
            $serviceName.'Service',
            $serviceName.'Port',
            Wsdl::TYPES_NS.':'.$serviceName.'Binding',
            $uri,
        );

        foreach ($reflectionMethods as $method) {
            $this->addFunctionToWsdl($method, $wsdl, $port, $binding);
        }

        return $wsdl;
    }

    /**
     * Add a function to the WSDL document.
     *
     * @param  Reflection\AbstractFunction $function function to add
     * @param  Wsdl                        $wsdl     WSDL document
     * @param  DOMElement                  $port     wsdl:portType
     * @param  DOMElement                  $binding  wsdl:binding
     * @throws InvalidArgumentException
     */
    protected function addFunctionToWsdl($function, $wsdl, $port, $binding): void
    {
        $uri = $this->getUri()->toString();

        // We only support one prototype: the one with the maximum number of arguments
        $prototype = null;
        $maxNumArgumentsOfPrototype = -1;

        foreach ($function->getPrototypes() as $tmpPrototype) {
            $numParams = count($tmpPrototype->getParameters());

            if ($numParams <= $maxNumArgumentsOfPrototype) {
                continue;
            }

            $maxNumArgumentsOfPrototype = $numParams;
            $prototype = $tmpPrototype;
        }

        if ($prototype === null) {
            throw new InvalidArgumentException(sprintf(
                'No prototypes could be found for the "%s" function',
                $function->getName(),
            ));
        }

        $functionName = $wsdl->translateType($function->getName());

        // Add the input message (parameters)
        $args = [];

        if ($this->bindingStyle['style'] === 'document') {
            // Document style: wrap all parameters in a sequence element
            $sequence = [];

            foreach ($prototype->getParameters() as $param) {
                $sequenceElement = [
                    'name' => $param->getName(),
                    'type' => $wsdl->getType($this->discoveryStrategy->getFunctionParameterType($param)),
                ];

                if ($param->isOptional()) {
                    $sequenceElement['nillable'] = 'true';
                }
                $sequence[] = $sequenceElement;
            }

            $element = [
                'name' => $functionName,
                'sequence' => $sequence,
            ];

            // Add the wrapper element part, which must be named 'parameters'
            $args['parameters'] = ['element' => $wsdl->addElement($element)];
        } else {
            // RPC style: add each parameter as a typed part
            foreach ($prototype->getParameters() as $param) {
                $args[$param->getName()] = [
                    'type' => $wsdl->getType($this->discoveryStrategy->getFunctionParameterType($param)),
                ];
            }
        }
        $wsdl->addMessage($functionName.'In', $args);

        $isOneWayMessage = $this->discoveryStrategy->isFunctionOneWay($function, $prototype);

        if ($isOneWayMessage === false) {
            // Add the output message (return value)
            $args = [];

            if ($this->bindingStyle['style'] === 'document') {
                // Document style: wrap the return value in a sequence element
                $sequence = [];

                if ($prototype->getReturnType() !== 'void') {
                    $sequence[] = [
                        'name' => $functionName.'Result',
                        'type' => $wsdl->getType(
                            $this->discoveryStrategy->getFunctionReturnType($function, $prototype),
                        ),
                    ];
                }

                $element = [
                    'name' => $functionName.'Response',
                    'sequence' => $sequence,
                ];

                // Add the wrapper element part, which must be named 'parameters'
                $args['parameters'] = ['element' => $wsdl->addElement($element)];
            } elseif ($prototype->getReturnType() !== 'void') {
                // RPC style: add the return value as a typed part
                $args['return'] = [
                    'type' => $wsdl->getType($this->discoveryStrategy->getFunctionReturnType($function, $prototype)),
                ];
            }

            $wsdl->addMessage($functionName.'Out', $args);
        }

        // Add the portType operation
        if ($isOneWayMessage === false) {
            $portOperation = $wsdl->addPortOperation(
                $port,
                $functionName,
                Wsdl::TYPES_NS.':'.$functionName.'In',
                Wsdl::TYPES_NS.':'.$functionName.'Out',
            );
        } else {
            $portOperation = $wsdl->addPortOperation(
                $port,
                $functionName,
                Wsdl::TYPES_NS.':'.$functionName.'In',
                false,
            );
        }
        $desc = $this->discoveryStrategy->getFunctionDocumentation($function);

        if ($desc !== '') {
            $wsdl->addDocumentation($portOperation, $desc);
        }

        // When using the RPC style, make sure the operation style includes a 'namespace'
        // attribute (WS-I Basic Profile 1.1 R2717)
        $operationBodyStyle = $this->operationBodyStyle;

        if ($this->bindingStyle['style'] === 'rpc' && !isset($operationBodyStyle['namespace'])) {
            $operationBodyStyle['namespace'] = $uri;
        }

        // Add the binding operation
        if ($isOneWayMessage === false) {
            $operation = $wsdl->addBindingOperation($binding, $functionName, $operationBodyStyle, $operationBodyStyle);
        } else {
            $operation = $wsdl->addBindingOperation($binding, $functionName, $operationBodyStyle);
        }
        $wsdl->addSoapOperation($operation, $uri.'#'.$functionName);
    }
}
