<?php

/**
 * Errors of Response function core
 *
 * Facula Framework 2015 (C) Rain Lee
 *
 * Facula Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, version 3.
 *
 * Facula Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Facula Framework. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Rain Lee <raincious@gmail.com>
 * @copyright  2015 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Base\Error\Core;

use Facula\Base\Prototype\Error as Base;

/**
 * Errors for Response function core
 */
class Response extends Base
{
    /** Error code to error message */
    protected static $errorStrings = array(
        'REWARMING_NOTALLOWED' => '
            You are attempt to re-warming Response function core.
            Which is not allowed due to data integrity reason.
        ',

        'RESPONSE_OVERSEND' => '
            The application already responded to the request in file %s (line %d).
            Client connection status can be change due to the response,
            so application denied to send another response.
        ',

        'BUFFER_POLLUTED' => '
            Level %d output buffer has been polluted with content: "%s".
            This buffer will be discard.
        ',
    );
}
