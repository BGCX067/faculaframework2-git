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

class Request extends Base
{
    /** Error code to error message */
    protected static $errorStrings = array(
        'BLOCKS_OVERLIMIT' => '
            The size limit of request block has been exceeded.
            You can only send less than %d blocks,
            We currently got totally %d.
        ',

        'LENGTH_OVERLIMIT' => '
            The size limit of request body has been exceeded.
            You can only send less than %d bytes,
            but you are sending %d bytes.
        ',

        'HEADERITEM_OVERLIMIT' => '
            The size limit of header item %s has been exceeded.
            The header item must less than %d characters.
        ',
    );
}
