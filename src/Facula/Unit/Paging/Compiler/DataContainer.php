<?php

/**
 * Data Container of current compiling content
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

/**
 * Base of operators
 */
class DataContainer
{
    /** Mutex tags */
    private $mutex = array();

    /**
     * Set a tag for mutex
     *
     * @param string $name The name of the mutex
     *
     * @return bool Return true when succeed, false otherwise
     */
    public function setMutex($name)
    {
        if (isset($this->mutex[$name])) {
            throw new Exception\MutexExisted($name);

            return false;
        }

        $this->mutex[$name] = true;

        return true;
    }

    /**
     * Check whatever a mutex has set
     *
     * @param string $name The name of the mutex
     *
     * @return bool Return true when setted, false otherwise
     */
    public function checkMutex($name)
    {
        if (isset($this->mutex[$name])) {
            return $this->mutex[$name];
        }

        return false;
    }
}
