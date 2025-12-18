<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap;

use Cline\Soap\Client\Common;
use Cline\Soap\Contract\ClientInterface;
use Cline\Soap\Exception\ExtensionNotLoadedException;
use Cline\Soap\Exception\InvalidArgumentException;
use Cline\Soap\Exception\UnexpectedValueException;
use SoapClient;
use SoapHeader;
use Traversable;

use const PHP_URL_SCHEME;
use const SOAP_1_1;
use const SOAP_1_2;
use const SOAP_DOCUMENT;
use const SOAP_ENCODED;
use const SOAP_LITERAL;
use const SOAP_RPC;

use function array_merge;
use function call_user_func_array;
use function class_exists;
use function count;
use function extension_loaded;
use function get_resource_type;
use function in_array;
use function is_array;
use function is_callable;
use function is_readable;
use function is_resource;
use function is_string;
use function iterator_to_array;
use function mb_strtolower;
use function parse_url;
use function sprintf;

/**
 * @phpstan-consistent-constructor
 *
 * @author Brian Faust <brian@cline.sh>
 */
class Client implements ClientInterface
{
    /**
     * Array of SOAP type => PHP class pairings for handling return/incoming values
     */
    protected ?array $classmap = null;

    /**
     * Encoding
     */
    protected string $encoding = 'UTF-8';

    /**
     * Registered fault exceptions
     */
    protected array $faultExceptions = [];

    /**
     * Last invoked method
     */
    protected string $lastMethod = '';

    /**
     * Permanent SOAP request headers (shared between requests).
     */
    protected array $permanentSoapInputHeaders = [];

    /**
     * SoapClient object
     */
    protected ?SoapClient $soapClient = null;

    /**
     * Array of SoapHeader objects
     *
     * @var array<SoapHeader>
     */
    protected array $soapInputHeaders = [];

    /**
     * Array of SoapHeader objects
     */
    protected array $soapOutputHeaders = [];

    /**
     * SOAP version to use; SOAP_1_2 by default, to allow processing of headers
     */
    protected int $soapVersion = SOAP_1_2;

    protected ?array $typemap = null;

    /**
     * WSDL used to access server
     * It also defines Client working mode (WSDL vs non-WSDL)
     */
    protected ?string $wsdl = null;

    /**
     * Whether to send the "Connection: Keep-Alive" header (true) or "Connection: close" header (false)
     * Available since PHP 5.4.0
     */
    protected ?bool $keepAlive = null;

    /**
     * One of SOAP_SSL_METHOD_TLS, SOAP_SSL_METHOD_SSLv2, SOAP_SSL_METHOD_SSLv3 or SOAP_SSL_METHOD_SSLv23
     * Available since PHP 5.5.0
     */
    protected ?int $sslMethod = null;

    /** @var string */
    protected string|int|null $connectionTimeout = null;

    protected ?string $localCert = null;

    protected ?string $location = null;

    protected ?string $login = null;

    protected ?string $passphrase = null;

    protected ?string $password = null;

    protected ?string $proxyHost = null;

    protected ?string $proxyLogin = null;

    protected ?string $proxyPassword = null;

    /** @var string */
    protected ?int $proxyPort = null;

    protected mixed $streamContext = null;

    /** @var string */
    protected ?int $style = null;

    protected ?string $uri = null;

    /** @var string */
    protected ?int $use = null;

    protected ?string $userAgent = null;

    protected ?int $cacheWsdl = null;

    protected ?int $compression = null;

    /** @var int */
    protected int|string|null $features = null;

    /**
     * @param  array|Traversable           $options
     * @throws ExtensionNotLoadedException
     */
    public function __construct(?string $wsdl = null, array|Traversable|null $options = null)
    {
        if (!extension_loaded('soap')) {
            throw new ExtensionNotLoadedException('SOAP extension is not loaded.');
        }

        if ($wsdl !== null) {
            $this->setWSDL($wsdl);
        }

        if ($options === null) {
            return;
        }

        $this->setOptions($options);
    }

    /**
     * Perform a SOAP call
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!is_array($arguments)) {
            $arguments = [$arguments];
        }
        $soapClient = $this->getSoapClient();

        $this->lastMethod = $name;

        $soapHeaders = array_merge($this->permanentSoapInputHeaders, $this->soapInputHeaders);
        $result = $soapClient->__soapCall(
            $name,
            $this->_preProcessArguments($arguments),
            [], /* Options are already set to the SOAP client object */
            count($soapHeaders) > 0 ? $soapHeaders : [],
            $this->soapOutputHeaders,
        );

        // Reset non-permanent input headers
        $this->soapInputHeaders = [];

        return $this->_preProcessResult($result);
    }

    /**
     * Set wsdl
     */
    public function setWSDL(string $wsdl): self
    {
        $this->wsdl = $wsdl;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Get wsdl
     */
    public function getWSDL(): ?string
    {
        return $this->wsdl;
    }

    /**
     * Set Options
     *
     * Allows setting options as an associative array of option => value pairs.
     *
     * @throws InvalidArgumentException
     */
    public function setOptions(array|Traversable $options): self
    {
        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }

        foreach ($options as $key => $value) {
            switch (mb_strtolower($key)) {
                case 'classmap':
                case 'class_map':
                    $this->setClassmap($value);

                    break;

                case 'encoding':
                    $this->setEncoding($value);

                    break;

                case 'soapversion':
                case 'soap_version':
                    $this->setSoapVersion($value);

                    break;

                case 'wsdl':
                    $this->setWSDL($value);

                    break;

                case 'uri':
                    $this->setUri($value);

                    break;

                case 'location':
                    $this->setLocation($value);

                    break;

                case 'style':
                    $this->setStyle($value);

                    break;

                case 'use':
                    $this->setEncodingMethod($value);

                    break;

                case 'login':
                    $this->setHttpLogin($value);

                    break;

                case 'password':
                    $this->setHttpPassword($value);

                    break;

                case 'proxyhost':
                case 'proxy_host':
                    $this->setProxyHost($value);

                    break;

                case 'proxyport':
                case 'proxy_port':
                    $this->setProxyPort($value);

                    break;

                case 'proxylogin':
                case 'proxy_login':
                    $this->setProxyLogin($value);

                    break;

                case 'proxypassword':
                case 'proxy_password':
                    $this->setProxyPassword($value);

                    break;

                case 'localcert':
                case 'local_cert':
                    $this->setHttpsCertificate($value);

                    break;

                case 'passphrase':
                    $this->setHttpsCertPassphrase($value);

                    break;

                case 'compression':
                    $this->setCompressionOptions($value);

                    break;

                case 'streamcontext':
                case 'stream_context':
                    $this->setStreamContext($value);

                    break;

                case 'features':
                    $this->setSoapFeatures($value);

                    break;

                case 'cachewsdl':
                case 'cache_wsdl':
                    $this->setWSDLCache($value);

                    break;

                case 'useragent':
                case 'user_agent':
                    $this->setUserAgent($value);

                    break;

                case 'typemap':
                case 'type_map':
                    $this->setTypemap($value);

                    break;

                case 'connectiontimeout':
                case 'connection_timeout':
                    $this->connectionTimeout = $value;

                    break;

                case 'keepalive':
                case 'keep_alive':
                    $this->setKeepAlive($value);

                    break;

                case 'sslmethod':
                case 'ssl_method':
                    $this->setSslMethod($value);

                    break;

                default:
                    throw new InvalidArgumentException('Unknown SOAP client option');
            }
        }

        return $this;
    }

    /**
     * Return array of options suitable for using with SoapClient constructor
     */
    public function getOptions(): array
    {
        $options = [];

        $options['classmap'] = $this->getClassmap();
        $options['typemap'] = $this->getTypemap();
        $options['encoding'] = $this->getEncoding();
        $options['soap_version'] = $this->getSoapVersion();
        $options['wsdl'] = $this->getWSDL();
        $options['uri'] = $this->getUri();
        $options['location'] = $this->getLocation();
        $options['style'] = $this->getStyle();
        $options['use'] = $this->getEncodingMethod();
        $options['login'] = $this->getHttpLogin();
        $options['password'] = $this->getHttpPassword();
        $options['proxy_host'] = $this->getProxyHost();
        $options['proxy_port'] = $this->getProxyPort();
        $options['proxy_login'] = $this->getProxyLogin();
        $options['proxy_password'] = $this->getProxyPassword();
        $options['local_cert'] = $this->getHttpsCertificate();
        $options['passphrase'] = $this->getHttpsCertPassphrase();
        $options['compression'] = $this->getCompressionOptions();
        $options['connection_timeout'] = $this->connectionTimeout;
        $options['stream_context'] = $this->getStreamContext();
        $options['cache_wsdl'] = $this->getWSDLCache();
        $options['features'] = $this->getSoapFeatures();
        $options['user_agent'] = $this->getUserAgent();
        $options['keep_alive'] = $this->getKeepAlive();
        $options['ssl_method'] = $this->getSslMethod();

        foreach ($options as $key => $value) {
            /*
             * ugly hack as I don't know if checking for '=== null'
             * breaks some other option
             */
            if (in_array($key, ['user_agent', 'cache_wsdl', 'compression'], true)) {
                if ($value === null) {
                    unset($options[$key]);
                }
            } else {
                if ($value === null) {
                    unset($options[$key]);
                }
            }
        }

        return $options;
    }

    /**
     * Set SOAP version
     *
     * @param  int                      $version One of the SOAP_1_1 or SOAP_1_2 constants
     * @throws InvalidArgumentException With invalid soap version argument.
     */
    public function setSoapVersion(int $version): self
    {
        if (!in_array($version, [SOAP_1_1, SOAP_1_2], true)) {
            throw new InvalidArgumentException(
                'Invalid soap version specified. Use SOAP_1_1 or SOAP_1_2 constants.',
            );
        }

        $this->soapVersion = $version;
        $this->soapClient = null;

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
     * Set classmap
     *
     * @throws InvalidArgumentException For any invalid class in the class map.
     */
    public function setClassmap(array $classmap): self
    {
        foreach ($classmap as $class) {
            if (!class_exists($class)) {
                throw new InvalidArgumentException('Invalid class in class map: '.$class);
            }
        }

        $this->classmap = $classmap;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Retrieve classmap
     *
     * @return mixed
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
    public function setTypemap(array $typeMap): self
    {
        foreach ($typeMap as $type) {
            if (!is_callable($type['from_xml'])) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid from_xml callback for type: %s',
                    $type['type_name'],
                ));
            }

            if (!is_callable($type['to_xml'])) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid to_xml callback for type: %s',
                    $type['type_name'],
                ));
            }
        }

        $this->typemap = $typeMap;
        $this->soapClient = null;

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
     * Set encoding
     *
     * @throws InvalidArgumentException With invalid encoding argument.
     */
    public function setEncoding(string $encoding): self
    {
        if (!is_string($encoding)) {
            throw new InvalidArgumentException('Invalid encoding specified');
        }

        $this->encoding = $encoding;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Get encoding
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * Check for valid URN
     *
     * @throws InvalidArgumentException On invalid URN.
     */
    public function validateUrn(string $urn): bool
    {
        $scheme = parse_url($urn, PHP_URL_SCHEME);

        if ($scheme === false || $scheme === null) {
            throw new InvalidArgumentException('Invalid URN');
        }

        return true;
    }

    /**
     * Set URI
     *
     * URI in Web Service the target namespace
     *
     * @throws InvalidArgumentException With invalid uri argument.
     */
    public function setUri(string $uri): self
    {
        $this->validateUrn($uri);
        $this->uri = $uri;
        $this->soapClient = null;

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
     * Set Location
     *
     * URI in Web Service the target namespace
     *
     * @throws InvalidArgumentException With invalid uri argument.
     */
    public function setLocation(string $location): self
    {
        $this->validateUrn($location);
        $this->location = $location;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Retrieve URI
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * Set request style
     *
     * @param  int                      $style One of the SOAP_RPC or SOAP_DOCUMENT constants
     * @throws InvalidArgumentException With invalid style argument.
     */
    public function setStyle(int $style): self
    {
        if (!in_array($style, [SOAP_RPC, SOAP_DOCUMENT], true)) {
            throw new InvalidArgumentException(
                'Invalid request style specified. Use SOAP_RPC or SOAP_DOCUMENT constants.',
            );
        }

        $this->style = $style;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Get request style
     */
    public function getStyle(): ?int
    {
        return $this->style;
    }

    /**
     * Set message encoding method
     *
     * @param  int                      $use One of the SOAP_ENCODED or SOAP_LITERAL constants
     * @throws InvalidArgumentException With invalid message encoding method argument.
     */
    public function setEncodingMethod(int $use): self
    {
        if (!in_array($use, [SOAP_ENCODED, SOAP_LITERAL], true)) {
            throw new InvalidArgumentException(
                'Invalid message encoding method. Use SOAP_ENCODED or SOAP_LITERAL constants.',
            );
        }

        $this->use = $use;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Get message encoding method
     */
    public function getEncodingMethod(): ?int
    {
        return $this->use;
    }

    /**
     * Set HTTP login
     */
    public function setHttpLogin(string $login): self
    {
        $this->login = $login;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Retrieve HTTP Login
     */
    public function getHttpLogin(): ?string
    {
        return $this->login;
    }

    /**
     * Set HTTP password
     */
    public function setHttpPassword(string $password): self
    {
        $this->password = $password;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Retrieve HTTP Password
     */
    public function getHttpPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set proxy host
     */
    public function setProxyHost(string $proxyHost): self
    {
        $this->proxyHost = $proxyHost;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Retrieve proxy host
     */
    public function getProxyHost(): ?string
    {
        return $this->proxyHost;
    }

    /**
     * Set proxy port
     */
    public function setProxyPort(int $proxyPort): self
    {
        $this->proxyPort = (int) $proxyPort;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Retrieve proxy port
     */
    public function getProxyPort(): ?int
    {
        return $this->proxyPort;
    }

    /**
     * Set proxy login
     */
    public function setProxyLogin(string $proxyLogin): self
    {
        $this->proxyLogin = $proxyLogin;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Retrieve proxy login
     */
    public function getProxyLogin(): ?string
    {
        return $this->proxyLogin;
    }

    /**
     * Set proxy password
     */
    public function setProxyPassword(string $proxyPassword): self
    {
        $this->proxyPassword = $proxyPassword;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Set HTTPS client certificate path
     *
     * @param  string                   $localCert local certificate path
     * @throws InvalidArgumentException With invalid local certificate path argument.
     */
    public function setHttpsCertificate(string $localCert): self
    {
        if (!is_readable($localCert)) {
            throw new InvalidArgumentException('Invalid HTTPS client certificate path.');
        }

        $this->localCert = $localCert;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Get HTTPS client certificate path
     */
    public function getHttpsCertificate(): ?string
    {
        return $this->localCert;
    }

    /**
     * Set HTTPS client certificate passphrase
     */
    public function setHttpsCertPassphrase(string $passphrase): self
    {
        $this->passphrase = $passphrase;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Get HTTPS client certificate passphrase
     */
    public function getHttpsCertPassphrase(): ?string
    {
        return $this->passphrase;
    }

    /**
     * Set compression options
     */
    public function setCompressionOptions(?int $compressionOptions): self
    {
        if ($compressionOptions === null) {
            $this->compression = null;
        } else {
            $this->compression = (int) $compressionOptions;
        }
        $this->soapClient = null;

        return $this;
    }

    /**
     * Get Compression options
     */
    public function getCompressionOptions(): ?int
    {
        return $this->compression;
    }

    /**
     * Retrieve proxy password
     */
    public function getProxyPassword(): ?string
    {
        return $this->proxyPassword;
    }

    /**
     * Set Stream Context
     *
     * @param  resource                 $context
     * @throws InvalidArgumentException
     */
    public function setStreamContext(mixed $context): self
    {
        if (!is_resource($context) || get_resource_type($context) !== 'stream-context') {
            throw new InvalidArgumentException('Invalid stream context resource given.');
        }

        $this->streamContext = $context;

        return $this;
    }

    /**
     * Get Stream Context
     *
     * @return resource
     */
    public function getStreamContext(): mixed
    {
        return $this->streamContext;
    }

    /**
     * Set the SOAP Feature options.
     */
    public function setSoapFeatures(int|string $feature): self
    {
        $this->features = $feature;
        $this->soapClient = null;

        return $this;
    }

    /**
     * Return current SOAP Features options
     *
     * @return int
     */
    public function getSoapFeatures(): int|string|null
    {
        return $this->features;
    }

    /**
     * Set the SOAP WSDL Caching Options
     */
    public function setWSDLCache(null|bool|int|string $caching): self
    {
        // @todo check WSDL_CACHE_* constants?
        if ($caching === null) {
            $this->cacheWsdl = null;
        } else {
            $this->cacheWsdl = (int) $caching;
        }

        return $this;
    }

    /**
     * Get current SOAP WSDL Caching option
     */
    public function getWSDLCache(): ?int
    {
        return $this->cacheWsdl;
    }

    /**
     * Set the string to use in User-Agent header
     */
    public function setUserAgent(?string $userAgent): self
    {
        if ($userAgent === null) {
            $this->userAgent = null;
        } else {
            $this->userAgent = (string) $userAgent;
        }

        return $this;
    }

    /**
     * Get current string to use in User-Agent header
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * Retrieve request XML
     */
    public function getLastRequest(): string
    {
        if ($this->soapClient !== null) {
            return $this->soapClient->__getLastRequest();
        }

        return '';
    }

    /**
     * Get response XML
     */
    public function getLastResponse(): string
    {
        if ($this->soapClient !== null) {
            return $this->soapClient->__getLastResponse();
        }

        return '';
    }

    /**
     * Retrieve request headers
     */
    public function getLastRequestHeaders(): string
    {
        if ($this->soapClient !== null) {
            return $this->soapClient->__getLastRequestHeaders();
        }

        return '';
    }

    /**
     * Retrieve response headers (as string)
     */
    public function getLastResponseHeaders(): string
    {
        if ($this->soapClient !== null) {
            return $this->soapClient->__getLastResponseHeaders();
        }

        return '';
    }

    /**
     * Retrieve last invoked method
     */
    public function getLastMethod(): string
    {
        return $this->lastMethod;
    }

    /** @codingStandardsIgnoreStart */
    /**
     * Do request proxy method.
     *
     * May be overridden in subclasses
     */
    public function _doRequest(Common $client, string $request, string $location, string $action, int $version, bool $oneWay = false): mixed
    {
        // Perform request as is

        return $client->parentDoRequest(
            $request,
            $location,
            $action,
            $version,
            $oneWay,
        );
    }
    /** @codingStandardsIgnoreEnd */

    /**
     * Add SOAP input header
     */
    public function addSoapInputHeader(SoapHeader $header, bool $permanent = false): self
    {
        if ($permanent) {
            $this->permanentSoapInputHeaders[] = $header;
        } else {
            $this->soapInputHeaders[] = $header;
        }

        return $this;
    }

    /**
     * Reset SOAP input headers
     */
    public function resetSoapInputHeaders(): self
    {
        $this->permanentSoapInputHeaders = [];
        $this->soapInputHeaders = [];

        return $this;
    }

    /**
     * Get last SOAP output headers
     */
    public function getLastSoapOutputHeaderObjects(): array
    {
        return $this->soapOutputHeaders;
    }

    /**
     * Send an RPC request to the service for a specific method.
     *
     * @param  string $method Name of the method we want to call.
     * @param  array  $params List of parameters for the method.
     * @return mixed  Returned results.
     */
    public function call(string $method, array $params = []): mixed
    {
        return call_user_func_array([$this, '__call'], [$method, $params]);
    }

    /**
     * Return a list of available functions
     *
     * @throws UnexpectedValueException
     */
    public function getFunctions(): array
    {
        if ($this->getWSDL() === null) {
            throw new UnexpectedValueException(sprintf(
                '%s method is available only in WSDL mode.',
                __METHOD__,
            ));
        }

        $soapClient = $this->getSoapClient();

        return $soapClient->__getFunctions();
    }

    /**
     * Return a list of SOAP types
     *
     * @throws UnexpectedValueException
     */
    public function getTypes(): array
    {
        if ($this->getWSDL() === null) {
            throw new UnexpectedValueException(sprintf(
                '%s method is available only in WSDL mode.',
                __METHOD__,
            ));
        }

        $soapClient = $this->getSoapClient();

        return $soapClient->__getTypes();
    }

    /**
     * Set SoapClient object
     */
    public function setSoapClient(SoapClient $soapClient): self
    {
        $this->soapClient = $soapClient;

        return $this;
    }

    /**
     * Get SoapClient object
     */
    public function getSoapClient(): SoapClient
    {
        if ($this->soapClient === null) {
            $this->initSoapClientObject();
        }

        return $this->soapClient;
    }

    /**
     * Set cookie
     */
    public function setCookie(string $cookieName, ?string $cookieValue = null): self
    {
        $soapClient = $this->getSoapClient();
        $soapClient->__setCookie($cookieName, $cookieValue);

        return $this;
    }

    public function getKeepAlive(): ?bool
    {
        return $this->keepAlive;
    }

    public function setKeepAlive(bool $keepAlive): self
    {
        $this->keepAlive = (bool) $keepAlive;

        return $this;
    }

    public function getSslMethod(): ?int
    {
        return $this->sslMethod;
    }

    public function setSslMethod(int $sslMethod): self
    {
        $this->sslMethod = $sslMethod;

        return $this;
    }
    /** @codingStandardsIgnoreEnd */

    /**
     * Initialize SOAP Client object
     *
     * @throws Exception\ExceptionInterface
     */
    protected function initSoapClientObject(): void
    {
        $wsdl = $this->getWSDL();
        $options = array_merge($this->getOptions(), ['trace' => true]);

        if ($wsdl === null) {
            if (!isset($options['location'])) {
                throw new UnexpectedValueException('"location" parameter is required in non-WSDL mode.');
            }

            if (!isset($options['uri'])) {
                throw new UnexpectedValueException('"uri" parameter is required in non-WSDL mode.');
            }
        } else {
            if (isset($options['use'])) {
                throw new UnexpectedValueException('"use" parameter only works in non-WSDL mode.');
            }

            if (isset($options['style'])) {
                throw new UnexpectedValueException('"style" parameter only works in non-WSDL mode.');
            }
        }
        unset($options['wsdl']);

        $this->soapClient = new Common([$this, '_doRequest'], $wsdl, $options);
    }

    /** @codingStandardsIgnoreStart */
    /**
     * Perform arguments pre-processing
     *
     * My be overridden in descendant classes
     */
    protected function _preProcessArguments(array $arguments): array
    {
        // Do nothing
        return $arguments;
    }

    /** @codingStandardsIgnoreEnd */

    /** @codingStandardsIgnoreStart */
    /**
     * Perform result pre-processing
     *
     * My be overridden in descendant classes
     *
     * @param  array $result
     * @return array
     */
    protected function _preProcessResult(mixed $result): mixed
    {
        // Do nothing
        return $result;
    }
}
