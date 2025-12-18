<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\AutoDiscover;
use Cline\Soap\Client;

beforeEach(function (): void {
    skipIfSoapNotLoaded();
});

describe('Online Integration', function (): void {
    test('AutoDiscover generates valid WSDL for online service', function (): void {
        $server = new AutoDiscover();
        $server->setUri('http://example.com/soap');
        $server->setClass(OnlineTestClass::class);

        $wsdl = $server->toXml();

        expect($wsdl)->toContain('definitions')
            ->and($wsdl)->toContain('OnlineTestClass');
    })->skip('Online test requires external server');

    test('Client connects to online SOAP service', function (): void {
        // This test requires a live SOAP service to be available
        // Skip unless explicitly enabled via environment variable
        if (!getenv('RUN_ONLINE_TESTS')) {
            $this->markTestSkipped('Online tests disabled. Set RUN_ONLINE_TESTS=1 to enable.');
        }

        $client = new Client('http://www.dneonline.com/calculator.asmx?wsdl');

        $result = $client->Add(['intA' => 1, 'intB' => 2]);

        expect($result->AddResult)->toBe(3);
    })->skip('Online test requires external server');
});

/**
 * Test class for online AutoDiscover tests.
 * @author Brian Faust <brian@cline.sh>
 */
final class OnlineTest
{
    /**
     * Simple test method.
     *
     * @param  string $input
     * @return string
     */
    public function testMethod($input)
    {
        return 'Response: '.$input;
    }
}
