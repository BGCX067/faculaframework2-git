<?php

/**
 * Oneof Limit
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

namespace Facula\Unit\Input\Limit;

use Facula\Unit\Input\Base\Limit as Base;
use Facula\Unit\Input\Base\Field\Error as Error;

/**
 * Oneof Limit
 */
class Oneof extends Base
{
    /** The elements */
    protected $elements = array();

    /**
     * Check if the input is valid
     *
     * @param mixed $value The value to check
     * @param Error $error The reference for getting error feedback
     *
     * @return bool Return True when it's qualified, false otherwise
     */
    public function qualified(&$value, &$error)
    {
        if (!isset($this->elements[$value])) {
            $error = new Error('INVALID', 'CASEUNKNOWN', array($value));

            return false;
        }

        return true;
    }

    /**
     * Add element condition
     *
     * @param mixed $name The name of the element
     *
     * @return Current instance of limit object
     */
    public function add($name)
    {
        $this->elements[$name] = true;

        return $this;
    }
}
