<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap\Exception;

use InvalidArgumentException as SPLInvalidArgumentException;

/**
 * Exception thrown when one or more method arguments are invalid
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidArgumentException extends SPLInvalidArgumentException implements ExceptionInterface {}
