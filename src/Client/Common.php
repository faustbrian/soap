<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Client;

use Cline\Soap\Exception\InvalidArgumentException;
use SoapClient;

use function is_callable;
use function mb_ltrim;
use function throw_unless;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class Common extends SoapClient
{
    /**
     * doRequest() pre-processing method
     *
     * @var callable
     */
    private $doRequestCallback;

    /**
     * Common Soap Client constructor
     *
     * @param callable $doRequestCallback
     * @param string   $wsdl
     */
    public function __construct($doRequestCallback, $wsdl, array $options)
    {
        throw_unless(is_callable($doRequestCallback), InvalidArgumentException::class, '$doRequestCallback argument must be callable');

        $this->doRequestCallback = $doRequestCallback;
        parent::__construct($wsdl, $options);
    }

    /**
     * Performs SOAP request over HTTP.
     * Overridden to implement different transport layers, perform additional
     * XML processing or other purpose.
     */
    public function __doRequest(
        string $request,
        string $location,
        string $action,
        int $version,
        bool $oneWay = false,
        ?string $uriParserClass = null,
    ): ?string {
        // ltrim is a workaround for https://bugs.php.net/bug.php?id=63780
        return ($this->doRequestCallback)($this, mb_ltrim($request), $location, $action, $version, $oneWay);
    }

    /**
     * Performs SOAP request on parent class explicitly.
     * Required since PHP 8.2 due to a deprecation on call_user_func([$client, 'SoapClient::__doRequest'], ...)
     *
     * @internal
     */
    public function parentDoRequest(
        string $request,
        string $location,
        string $action,
        int $version,
        bool $oneWay = false,
    ): ?string {
        return parent::__doRequest($request, $location, $action, $version, $oneWay);
    }
}
