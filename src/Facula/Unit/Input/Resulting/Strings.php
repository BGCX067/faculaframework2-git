<?php

/**
 * String Result
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

namespace Facula\Unit\Input\Resulting;

use Facula\Unit\Input\Base\Resulting;

/**
 * String Result
 */
class Strings extends Resulting
{
    /** The data type of current result */
    protected static $dataType = 'String';

    /**
     * Return a sub string
     *
     * @param integer $start The starting point of the sub string in current string
     * @param integer $len The length of sub string
     *
     * @return string The sub string
     */
    public function cut($start, $len)
    {
        return substr($this->value, $start, $len);
    }

    /**
     * Return the length of current string
     *
     * @return integer The length of string
     */
    public function len()
    {
        return strlen($this->value);
    }

    /**
     * Return replaced the string according to parameter
     *
     * @param mixed $search The key to search
     * @param mixed $replace The value to search
     *
     * @return string The replaced string
     */
    public function replace($search, $replace)
    {
        return str_replace($search, $replace, $this->value);
    }
}
