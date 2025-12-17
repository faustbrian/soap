<?php

namespace Cline\Soap\Exception;

use RuntimeException;

/**
 * Exception thrown when SOAP PHP extension is not loaded
 */
class ExtensionNotLoadedException extends RuntimeException
{
}
