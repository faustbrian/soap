<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\Server;
use Cline\Soap\Server\DocumentLiteralWrapper;
use Tests\Fixtures\MyCalculatorService;

beforeEach(function (): void {
    skipIfSoapNotLoaded();
});

describe('DocumentLiteralWrapper', function (): void {
    test('wrapper delegates to underlying object', function (): void {
        $server = new Server(fixturesPath('calculator.wsdl'));
        $server->setObject(
            new DocumentLiteralWrapper(
                new MyCalculatorService(),
            ),
        );

        $client = new SoapClient(
            fixturesPath('calculator.wsdl'),
            [
                'location' => 'test://test',
                'uri' => 'http://framework.zend.com',
            ],
        );

        $request = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<env:Body>'
            .'<env:add xmlns:env="http://framework.zend.com">'
            .'<x xsi:type="xsd:int">10</x>'
            .'<y xsi:type="xsd:int">20</y>'
            .'</env:add>'
            .'</env:Body>'
            .'</env:Envelope>';

        $response = $server->handle($request);

        expect($response)->toContain('addResponse')
            ->and($response)->toContain('addReturn')
            ->and($response)->toContain('30');
    });
});
