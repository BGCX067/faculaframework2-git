<?php

/**
 * Base of Page Compiler Operator
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

namespace Facula\Unit\Paging\Compiler;

use Facula\Unit\Paging\Compiler\Exception\Compiler\Operator as Exception;
use Facula\Base\Factory\Operator as Base;

/**
 * Base of operators
 */
abstract class OperatorBase extends Base
{
    /** Preset a empty operator array */
    protected static $operators = array();

    /** Mutex tags */
    private static $mutex = array();

    /**
     * Static init of operator base
     *
     * @return void
     */
    final public static function init()
    {

    }

    /**
     * Static data release of operator base
     *
     * @return void
     */
    final public static function flush()
    {
        self::$mutex = array();
    }

    /**
     * Set a tag for mutex
     *
     * @param string $name The name of the mutex
     *
     * @return bool Return true when succeed, false otherwise
     */
    final protected function setMutex($name)
    {
        if (isset(self::$mutex[$name])) {
            throw new Exception\MutexExisted($name);

            return false;
        }

        self::$mutex[$name] = true;

        return true;
    }
}
