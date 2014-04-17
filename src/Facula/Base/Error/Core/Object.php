<?php

/**
 * Errors of Object function core
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
 * Errors for Object function core
 */
class Object extends Base
{
    /** Error code to error message */
    protected static $errorStrings = array(
        'OBJECT_CREATE_FAILED' => '
            Failed on creating new instance of "%s".
        ',

        'OBJECT_MAXPARAM_EXCEEDED' => '
            Can\'t create new instance of "%s",
            The parameters has exceeded the max limit.
        ',

        'OBJECT_INIT_FAILED' => '
            Can\'t initialize "%s".
            The Initializer method returned false result.
        ',

        'OBJECT_NOTFOUND' => '
            Can\'t create "%s".
            The class of this object can\'t be found.
        ',
    );
}
