<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Client;

use Cline\Soap\Client as SOAPClient;
use Cline\Soap\Server as SOAPServer;

use function ob_get_clean;
use function ob_start;

/**
 * Class is intended to be used as local SOAP client which works
 * with a provided Server object.
 *
 * Could be used for development or testing purposes.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Local extends SOAPClient
{
    /**
     * Local client constructor
     *
     * @param null|array<string, mixed> $options
     */
    public function __construct(
        protected readonly SOAPServer $server,
        ?string $wsdl,
        ?array $options = null,
    ) {
        // Use Server specified SOAP version as default
        $this->setSoapVersion($server->getSoapVersion());

        parent::__construct($wsdl, $options);
    }

    /** @codingStandardsIgnoreStart */
    /**
     * Actual "do request" method.
     */
    public function _doRequest(Common $client, string $request, string $location, string $action, int $version, bool $oneWay = false): mixed
    {
        // Perform request as is
        ob_start();
        $this->server->handle($request);
        $response = ob_get_clean();

        if ($response === null || $response === '') {
            $serverResponse = $this->server->getResponse();

            if ($serverResponse !== null) {
                $response = $serverResponse;
            }
        }

        return $response;
    }
    // @codingStandardsIgnoreEnd
}
