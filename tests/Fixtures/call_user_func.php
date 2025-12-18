<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Soap;

use Tests\Fixtures\MockCallUserFunc;

use function func_get_args;

/**
 * Function interceptor for call_user_func.
 *
 * @return mixed Return value.
 */
function call_user_func(...$args)
{
    if (!MockCallUserFunc::$mock) {
        return \call_user_func(...func_get_args());
    }

    MockCallUserFunc::$params = $args;

    $result = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">'
        .'<s:Body>';

    $result .= '<TestMethodResponse xmlns="http://unit/test">'
        .'<TestMethodResult>'
        .'<TestMethodResult><dummy></dummy></TestMethodResult>'
        .'</TestMethodResult>'
        .'</TestMethodResponse>';

    return $result . ('</s:Body>' . '</s:Envelope>');
}
