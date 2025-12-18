<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Exception;

use RuntimeException;

/**
 * Exception thrown when SOAP PHP extension is not loaded
 */
final class ExtensionNotLoadedException extends RuntimeException {}
