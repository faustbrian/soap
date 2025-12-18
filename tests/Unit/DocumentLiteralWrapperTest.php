<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\Exception\BadMethodCallException;
use Cline\Soap\Exception\UnexpectedValueException;
use Cline\Soap\Server;
use Cline\Soap\Server\DocumentLiteralWrapper;
use Tests\Fixtures\MyCalculatorService;

beforeEach(function (): void {
    skipIfSoapNotLoaded();
});

describe('DocumentLiteralWrapper', function (): void {
    describe('Happy Paths', function (): void {
        test('delegates method call to underlying object', function (): void {
            $wrapper = new DocumentLiteralWrapper(
                new MyCalculatorService(),
            );

            $document = new stdClass();
            $document->x = 10;
            $document->y = 20;

            $result = $wrapper->add($document);

            expect($result)->toBe(['addResult' => 30]);
        });

        test('handles multiple method calls', function (): void {
            $wrapper = new DocumentLiteralWrapper(
                new MyCalculatorService(),
            );

            $addDoc1 = new stdClass();
            $addDoc1->x = 5;
            $addDoc1->y = 3;

            $addDoc2 = new stdClass();
            $addDoc2->x = 10;
            $addDoc2->y = 4;

            expect($wrapper->add($addDoc1))->toBe(['addResult' => 8])
                ->and($wrapper->add($addDoc2))->toBe(['addResult' => 14]);
        });

        test('returns result in document/literal format', function (): void {
            $wrapper = new DocumentLiteralWrapper(
                new MyCalculatorService(),
            );

            $document = new stdClass();
            $document->x = 100;
            $document->y = 50;

            $result = $wrapper->add($document);

            expect($result)->toBeArray()
                ->and($result)->toHaveKey('addResult')
                ->and($result['addResult'])->toBe(150);
        });
    });

    describe('Sad Paths', function (): void {
        test('throws exception for non-existent method', function (): void {
            $wrapper = new DocumentLiteralWrapper(
                new MyCalculatorService(),
            );

            $document = new stdClass();
            $document->x = 10;

            expect(fn () => $wrapper->nonExistentMethod($document))
                ->toThrow(BadMethodCallException::class, 'Method nonExistentMethod does not exist');
        });

        test('throws exception when no arguments provided', function (): void {
            $wrapper = new DocumentLiteralWrapper(
                new MyCalculatorService(),
            );

            expect(fn () => $wrapper->add())
                ->toThrow(UnexpectedValueException::class, 'Expecting exactly one argument');
        });

        test('throws exception when multiple arguments provided', function (): void {
            $wrapper = new DocumentLiteralWrapper(
                new MyCalculatorService(),
            );

            $doc1 = new stdClass();
            $doc2 = new stdClass();

            expect(fn () => $wrapper->add($doc1, $doc2))
                ->toThrow(UnexpectedValueException::class, 'Expecting exactly one argument');
        });

        test('throws exception for unknown argument in document', function (): void {
            $wrapper = new DocumentLiteralWrapper(
                new MyCalculatorService(),
            );

            $document = new stdClass();
            $document->x = 10;
            $document->y = 20;
            $document->unknownArg = 'bad';

            expect(fn () => $wrapper->add($document))
                ->toThrow(UnexpectedValueException::class, 'Received unknown argument unknownArg');
        });
    });

    describe('Integration', function (): void {
        // Skip: SoapServer::handle() may call exit() on certain conditions in PHP 8.5,
        // which kills the test process and prevents Pest from showing the test summary.
        // This test must be run in process isolation (e.g., via Docker or separate process).
        test('wrapper delegates via SoapServer handle', function (): void {
            $server = new Server(fixturesPath('calculator.wsdl'));
            $server->setReturnResponse(true);
            $server->setObject(
                new DocumentLiteralWrapper(
                    new MyCalculatorService(),
                ),
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
        })->skip('SoapServer::handle() may exit in PHP 8.5 - run via Docker');
    });
});
