<?php

/**
 * String Result
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

namespace Facula\Unit\Input\Resulting;

use Facula\Unit\Input\Base\Resulting;
use Facula\Unit\Strings as Stringer;

/**
 * String Result
 */
class Strings extends Resulting
{
    /** The data type of current result */
    protected static $dataType = 'Data';

    /**
     * Return a sub string
     *
     * @param integer $start The starting point of the sub string in current string
     * @param integer $len The length of sub string
     * @param boolean $apostrophe Will we display apostrophe at end when for
     *                           indicate there some content been cut out
     *
     * @return string The sub string
     */
    public function cut($start, $len, $apostrophe = false)
    {
        return Stringer::substr($this->value, $start, $len, $apostrophe);
    }

    /**
     * Return the length of current string
     *
     * @return integer The length of string
     */
    public function len()
    {
        return Stringer::strlen($this->value);
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
        return Stringer::str_replace($search, $replace, $this->value);
    }
}
