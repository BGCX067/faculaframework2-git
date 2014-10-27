<?php

/**
 * Errors of Request function core
 *
 * Facula Framework 2014 (C) Rain Lee
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
 * @copyright  2014 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Base\Error\Core;

use Facula\Base\Prototype\Error as Base;

/**
 * Errors for Request function core
 */
class Request extends Base
{
    /** Error code to error message */
    protected static $errorStrings = array(
        'REWARMING_NOTALLOWED' => '
            You are attempt to re-warming Request function core.
            Which is not allowed due to data integrity reason.
        ',

        'BLOCKS_OVERLIMIT' => '
            Request block has exceeded the limit.
            You can only send less than %d blocks in your total request,
            but got %d.
        ',

        'LENGTH_OVERLIMIT' => '
            Request body has exceeded the length limit.
            You can only send less than %d bytes in request body,
            but there are %d bytes sent by your client.
        ',

        'HEADERITEM_OVERLIMIT' => '
            Header data "%s" has exceeded the header length limit.
            A header item must shorter than %d character.
        ',

        'PROXYADDR_INVALID' => '
            The IP address "%s" seems not a valid address.
        ',
    );
}
