<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Exception;

use BadMethodCallException as SPLBadMethodCallException;

/**
 * Exception thrown when unrecognized method is called via overloading
 */
final class BadMethodCallException extends SPLBadMethodCallException implements ExceptionInterface {}
