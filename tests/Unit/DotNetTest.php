<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\Client\DotNet;
use Cline\Soap\Exception\RuntimeException;
use Laminas\Http\Client\Adapter\Curl;

beforeEach(function (): void {
    skipIfSoapNotLoaded();

    // Skip if Laminas HTTP is not available (required for DotNet client)
    if (class_exists(Curl::class)) {
        return;
    }

    test()->markTestSkipped('Laminas HTTP client not installed');
});

describe('DotNet Client', function (): void {
    test('defaults to SOAP 1.1 version', function (): void {
        $client = new DotNet(fixturesPath('wsdl_example.wsdl'));

        expect($client->getSoapVersion())->toBe(\SOAP_1_1);
    });

    test('enables NTLM authentication when authentication option is set to ntlm', function (): void {
        $options = [
            'authentication' => 'ntlm',
            'login' => 'username',
            'password' => 'testpass',
        ];

        $client = new DotNet(fixturesPath('wsdl_example.wsdl'), $options);

        $reflection = new ReflectionClass($client);
        $property = $reflection->getProperty('useNtlm');

        expect($property->getValue($client))->toBeTrue();
    });

    test('does not enable NTLM authentication without authentication option', function (): void {
        $options = [
            'login' => 'username',
            'password' => 'testpass',
        ];

        $client = new DotNet(fixturesPath('wsdl_example.wsdl'), $options);

        $reflection = new ReflectionClass($client);
        $property = $reflection->getProperty('useNtlm');

        expect($property->getValue($client))->toBeFalse();
    });

    test('stores login and password in options', function (): void {
        $options = [
            'authentication' => 'ntlm',
            'login' => 'testuser',
            'password' => 'secret123',
        ];

        $client = new DotNet(fixturesPath('wsdl_example.wsdl'), $options);

        $reflection = new ReflectionClass($client);
        $property = $reflection->getProperty('options');

        $storedOptions = $property->getValue($client);

        expect($storedOptions['login'])->toBe('testuser')
            ->and($storedOptions['password'])->toBe('secret123');
    });

    test('can set and get custom CurlClient', function (): void {
        if (!class_exists(Curl::class)) {
            $this->markTestSkipped('Laminas HTTP client not installed');
        }

        $client = new DotNet(fixturesPath('wsdl_example.wsdl'));
        $curlAdapter = new Curl();

        $client->setCurlClient($curlAdapter);

        expect($client->getCurlClient())->toBe($curlAdapter);
    });

    test('throws RuntimeException when calling method with non-array arguments', function (): void {
        $client = new DotNet(fixturesPath('wsdl_example.wsdl'), [
            'authentication' => 'ntlm',
            'login' => 'user',
            'password' => 'pass',
        ]);

        // Access protected method via reflection
        $reflection = new ReflectionClass($client);
        $method = $reflection->getMethod('_preProcessArguments');

        expect(fn (): mixed => $method->invoke($client, ['arg1', 'arg2']))
            ->toThrow(RuntimeException::class);
    });
});
