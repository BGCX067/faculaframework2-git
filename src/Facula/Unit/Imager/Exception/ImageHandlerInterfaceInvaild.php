<?php

/**
 * ImageHandlerInterfaceInvaild Exception
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

namespace Facula\Unit\Imager\Exception;

use Facula\Base\Prototype\Exception as Base;

/**
 * ImageHandlerInterfaceInvaild Exception
 */
class ImageHandlerInterfaceInvaild extends Base
{
    protected static $exceptionMessage = '
        Imager handler must implement interface "Facula\Unit\Imager\OperatorImplement".
    ';
}
