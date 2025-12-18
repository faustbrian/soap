<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Exception;

use UnexpectedValueException as SPLUnexpectedValueException;

/**
 * Exception thrown when provided arguments are invalid
 * @author Brian Faust <brian@cline.sh>
 */
final class UnexpectedValueException extends SPLUnexpectedValueException implements ExceptionInterface {}
