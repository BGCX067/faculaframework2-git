<?php

/**
 * Errors of Core Factory
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

namespace Facula\Base\Error\Factory;

use Facula\Base\Prototype\Error as Base;

/**
 * Errors for Core factory
 */
class Core extends Base
{
    /** Error code to error message */
    protected static $errorStrings = array(
        'CLASS_NOTFOUND' => '
            Producing core instance for %s, but no class specified.
        ',

        'CLASS_NOTLOAD' => '
            Facula function core %s is not loadable.
            Please make sure object file has been included before preform this task.
        ',

        'CLASS_INTERFACE' => '
            Facula function core %s
            must implement interface %s.
        ',

        'CLASS_BASE' => '
            Facula function core %s
            must be extend from base class %s.
        ',
    );
}
