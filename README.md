[![GitHub Workflow Status][ico-tests]][link-tests]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

------

# Soap

A SOAP client and server library with WSDL generation capabilities for PHP 8.5+.

## Acknowledgments

This package is a modernized fork of [laminas/laminas-soap][link-original], originally created by [Zend Technologies USA Inc.][link-zend] and maintained by the [Laminas Project contributors][link-original-contributors].

The original laminas-soap library was licensed under the BSD-3-Clause license.

## Requirements

> **Requires [PHP 8.5+](https://php.net/releases/)**, ext-soap, ext-dom

## Installation

```bash
composer require cline/soap
```

## Usage

### SOAP Client

```php
use Cline\Soap\Client;

$client = new Client('http://example.com/service.wsdl');
$result = $client->someMethod('parameter');
```

### SOAP Server

```php
use Cline\Soap\Server;

$server = new Server('service.wsdl');
$server->setClass(MyServiceClass::class);
$server->handle();
```

### AutoDiscover (WSDL Generation)

```php
use Cline\Soap\AutoDiscover;

$autodiscover = new AutoDiscover();
$autodiscover->setServiceName('MyService');
$autodiscover->setUri('http://example.com/soap');
$autodiscover->setClass(MyServiceClass::class);

$wsdl = $autodiscover->generate();
$wsdl->dump('/path/to/service.wsdl');
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please use the [GitHub security reporting form][link-security] rather than the issue queue.

## Credits

- [Brian Faust][link-maintainer]
- [Zend Technologies USA Inc.][link-zend] (Original authors)
- [Laminas Project Contributors][link-original-contributors]
- [All Contributors][link-contributors]

## License

The MIT License. Please see [License File](LICENSE.md) for more information.

This package contains code derived from laminas/laminas-soap which is licensed under the BSD-3-Clause License.

[ico-tests]: https://git.cline.sh/faustbrian/soap/actions/workflows/quality-assurance.yaml/badge.svg
[ico-version]: https://img.shields.io/packagist/v/cline/soap.svg
[ico-license]: https://img.shields.io/badge/License-MIT-green.svg
[ico-downloads]: https://img.shields.io/packagist/dt/cline/soap.svg

[link-tests]: https://git.cline.sh/faustbrian/soap/actions
[link-packagist]: https://packagist.org/packages/cline/soap
[link-downloads]: https://packagist.org/packages/cline/soap
[link-security]: https://git.cline.sh/faustbrian/soap/security
[link-maintainer]: https://git.cline.sh/faustbrian
[link-contributors]: ../../contributors
[link-original]: https://github.com/laminas/laminas-soap
[link-original-contributors]: https://github.com/laminas/laminas-soap/graphs/contributors
[link-zend]: https://www.zend.com
