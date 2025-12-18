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
use Cline\Soap\Reflection\AbstractFunction;
use Cline\Soap\Reflection\Reflection;
use Cline\Soap\Wsdl;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ComplexTypeStrategyInterface as ComplexTypeStrategy;
use DOMElement;
use Uri\Rfc3986\Uri;

use const ENT_QUOTES;

use function array_unique;
use function count;
use function function_exists;
use function get_debug_type;
use function header;
use function htmlspecialchars;
use function is_array;
use function is_string;
use function is_subclass_of;
use function mb_trim;
use function preg_match;
use function sprintf;
use function throw_if;
use function throw_unless;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class AutoDiscover
{
    private ?string $serviceName = null;

    /**
     * Service function names
     */
    private array $functions = [];

    /**
     * Service class name
     */
    private ?string $class = null;

    private ComplexTypeStrategy|bool|null $strategy = null;

    /**
     * Url where the WSDL file will be available at.
     */
    private Uri|string|null $uri = null;

    /**
     * soap:body operation style options
     */
    private array $operationBodyStyle = [
        'use' => 'encoded',
        'encodingStyle' => 'http://schemas.xmlsoap.org/soap/encoding/',
    ];

    /**
     * soap:operation style
     */
    private array $bindingStyle = [
        'style' => 'rpc',
        'transport' => 'http://schemas.xmlsoap.org/soap/http',
    ];

    /**
     * Name of the class to handle the WSDL creation.
     */
    private string $wsdlClass = Wsdl::class;

    /**
     * Class Map of PHP to WSDL types.
     */
    private array $classMap = [];

    /**
     * Discovery strategy for types and other method details.
     */
    private DiscoveryStrategy $discoveryStrategy;

    private readonly Reflection $reflection;

    /**
     * Constructor
     */
    public function __construct(
        ?ComplexTypeStrategy $strategy = null,
        mixed $endpointUri = null,
        mixed $wsdlClass = null,
        array $classMap = [],
    ) {
        $this->reflection = new Reflection();
        $this->setDiscoveryStrategy(
            new ReflectionDiscovery(),
        );

        if ($strategy instanceof ComplexTypeStrategy) {
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
     */
    public function setDiscoveryStrategy(DiscoveryStrategy $discoveryStrategy): self
    {
        $this->discoveryStrategy = $discoveryStrategy;

        return $this;
    }

    /**
     * Get the discovery strategy.
     */
    public function getDiscoveryStrategy(): DiscoveryStrategy
    {
        return $this->discoveryStrategy;
    }

    /**
     * Get the class map of php to wsdl mappings.
     */
    public function getClassMap(): array
    {
        return $this->classMap;
    }

    /**
     * Set the class map of php to wsdl mappings.
     *
     * @throws InvalidArgumentException
     */
    public function setClassMap(mixed $classMap): self
    {
        if (!is_array($classMap)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects an array; received "%s"',
                __METHOD__,
                get_debug_type($classMap),
            ));
        }

        $this->classMap = $classMap;

        return $this;
    }

    /**
     * Set service name
     *
     * @throws InvalidArgumentException
     */
    public function setServiceName(string $serviceName): self
    {
        $matches = [];

        // first character must be letter or underscore {@see http://www.w3.org/TR/wsdl#_document-n}
        $i = preg_match('/^[a-z\_]/ims', $serviceName, $matches);

        throw_if($i !== 1, InvalidArgumentException::class, 'Service Name must start with letter or _');

        $this->serviceName = $serviceName;

        return $this;
    }

    /**
     * Get service name
     *
     * @throws RuntimeException
     */
    public function getServiceName(): string
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
     * @throws InvalidArgumentException
     */
    public function setUri(mixed $uri): self
    {
        throw_if(!is_string($uri) && !$uri instanceof Uri, InvalidArgumentException::class, 'Argument to '.self::class.'::setUri should be string or \Uri\Rfc3986\Uri instance.');

        if ($uri instanceof Uri) {
            $this->uri = $uri;

            return $this;
        }

        $uri = mb_trim($uri);
        $uri = htmlspecialchars($uri, ENT_QUOTES, 'UTF-8', false);

        throw_if($uri === '' || $uri === '0', InvalidArgumentException::class, 'Uri contains invalid characters or is empty');

        $this->uri = $uri;

        return $this;
    }

    /**
     * Return the current Uri that the SOAP WSDL Service will be located at.
     *
     * @throws RuntimeException
     */
    public function getUri(): Uri
    {
        throw_if($this->uri === null, RuntimeException::class, 'Missing uri. You have to explicitly configure the Endpoint Uri by calling AutoDiscover::setUri().');

        if (is_string($this->uri)) {
            $this->uri = new Uri($this->uri);
        }

        return $this->uri;
    }

    /**
     * Set the name of the WSDL handling class.
     *
     * @throws InvalidArgumentException
     */
    public function setWsdlClass(mixed $wsdlClass): self
    {
        throw_if(!is_string($wsdlClass) && !is_subclass_of($wsdlClass, Wsdl::class), InvalidArgumentException::class, 'No \Cline\Soap\Wsdl subclass given to '.self::class.'::setWsdlClass as string.');

        $this->wsdlClass = $wsdlClass;

        return $this;
    }

    /**
     * Return the name of the WSDL handling class.
     */
    public function getWsdlClass(): string
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
     */
    public function setOperationBodyStyle(array $operationStyle = []): self
    {
        throw_unless(isset($operationStyle['use']), InvalidArgumentException::class, 'Key "use" is required in Operation soap:body style.');

        $this->operationBodyStyle = $operationStyle;

        return $this;
    }

    /**
     * Set Binding soap:binding style.
     *
     * By default 'style' is 'rpc' and 'transport' is 'http://schemas.xmlsoap.org/soap/http'.
     */
    public function setBindingStyle(array $bindingStyle = []): self
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
     */
    public function setComplexTypeStrategy(ComplexTypeStrategy $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Set the Class the SOAP server will use
     */
    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Add a Single or Multiple Functions to the WSDL
     *
     * @throws InvalidArgumentException
     */
    public function addFunction(mixed $function): self
    {
        if (is_array($function)) {
            foreach ($function as $row) {
                $this->addFunction($row);
            }
        } elseif (is_string($function)) {
            throw_unless(function_exists($function), InvalidArgumentException::class, 'Argument to '.self::class.'::addFunction should be a valid function name.');
            $this->functions[] = $function;
        } else {
            throw new InvalidArgumentException(
                'Argument to '.self::class.'::addFunction should be string or array of strings.',
            );
        }

        return $this;
    }

    /**
     * Generate the WSDL file from the configured input.
     *
     * @throws RuntimeException
     */
    public function generate(): Wsdl
    {
        throw_if($this->class && $this->functions, RuntimeException::class, 'Can either dump functions or a class as a service, not both.');

        if ($this->class) {
            return $this->generateClass();
        }

        return $this->generateFunctions();
    }

    /**
     * Proxy to WSDL dump function
     *
     * @throws RuntimeException
     */
    public function dump(string $filename): bool
    {
        return $this->generate()->dump($filename);
    }

    /**
     * Proxy to WSDL toXml() function
     *
     * @throws RuntimeException
     */
    public function toXml(): string
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
     */
    private function generateClass(): Wsdl
    {
        return $this->generateWsdl($this->reflection->reflectClass($this->class)->getMethods());
    }

    /**
     * Generate the WSDL for a set of functions.
     */
    private function generateFunctions(): Wsdl
    {
        $methods = [];

        foreach (array_unique($this->functions) as $func) {
            $methods[] = $this->reflection->reflectFunction($func);
        }

        return $this->generateWsdl($methods);
    }

    /**
     * Generate the WSDL for a set of reflection method instances.
     */
    private function generateWsdl(array $reflectionMethods): Wsdl
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
     * @param  AbstractFunction         $function function to add
     * @param  Wsdl                     $wsdl     WSDL document
     * @param  DOMElement               $port     wsdl:portType
     * @param  DOMElement               $binding  wsdl:binding
     * @throws InvalidArgumentException
     */
    private function addFunctionToWsdl(AbstractFunction $function, Wsdl $wsdl, DOMElement $port, DOMElement $binding): void
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
