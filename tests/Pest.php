<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Soap\Wsdl;
use Cline\Soap\Wsdl\ComplexTypeStrategy\ComplexTypeStrategyInterface;
use Cline\Soap\Wsdl\ComplexTypeStrategy\DefaultComplexType;
use Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__);

/**
 * Helper function to check if SOAP extension is loaded.
 */
function skipIfSoapNotLoaded(): void
{
    if (extension_loaded('soap')) {
        return;
    }

    test()->markTestSkipped('SOAP Extension is not loaded');
}

/**
 * Helper to get the fixtures path.
 */
function fixturesPath(string $path = ''): string
{
    return __DIR__.'/Fixtures'.($path !== '' && $path !== '0' ? '/'.$path : '');
}

/**
 * Create a WSDL instance for testing.
 */
function createWsdl(
    string $serviceName = 'MyService',
    string $serviceUri = 'http://localhost/MyService.php',
    ?ComplexTypeStrategyInterface $strategy = null,
): Wsdl {
    $strategy ??= new DefaultComplexType();

    return new Wsdl($serviceName, $serviceUri, $strategy);
}

/**
 * Register WSDL namespaces on DOMDocument for XPath queries.
 */
function registerWsdlNamespaces(DOMDocument $dom, string $serviceUri = 'http://localhost/MyService.php'): DOMXPath
{
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('unittest', Wsdl::WSDL_NS_URI);
    $xpath->registerNamespace('tns', $serviceUri);
    $xpath->registerNamespace('soap', Wsdl::SOAP_11_NS_URI);
    $xpath->registerNamespace('soap12', Wsdl::SOAP_12_NS_URI);
    $xpath->registerNamespace('xsd', Wsdl::XSD_NS_URI);
    $xpath->registerNamespace('soap-enc', Wsdl::SOAP_ENC_URI);
    $xpath->registerNamespace('wsdl', Wsdl::WSDL_NS_URI);

    return $xpath;
}

/**
 * Verify all document nodes have valid namespaces.
 */
function assertDocumentNodesHaveNamespaces(DOMDocument $dom): void
{
    $element = $dom->documentElement;
    assertNodeAndChildrenHaveNamespaces($element);
}

/**
 * Recursively check node and children for namespaces.
 */
function assertNodeAndChildrenHaveNamespaces(DOMNode $element): void
{
    foreach ($element->childNodes as $node) {
        if ($node->nodeType !== \XML_ELEMENT_NODE) {
            continue;
        }

        expect($node->namespaceURI)
            ->not->toBeEmpty(sprintf('Document element: %s has no valid namespace. Line: %d', $node->nodeName, $node->getLineNo()));
        assertNodeAndChildrenHaveNamespaces($node);
    }
}
