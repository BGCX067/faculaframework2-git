<?php

/**
 * Integer Result
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
 * Integer Result
 */
class Integers extends Resulting
{
    /** The data type of current result */
    protected static $dataType = 'Integer';

    /**
     * Convert integer into string
     *
     * @return string Stringlized integer
     */
    public function toString()
    {
        return (string)$this->value;
    }

    /**
     * Get a read friendly number
     *
     * @return string The read friendly number
     */
    public function notation()
    {
        return number_format($this->value, 0, '.', ',');
    }

    /**
     * Get a read friendly number
     *
     * @return string The read friendly number
     */
    public function max($max)
    {
        if ($this->value > $max) {
            return (string)$max . '+';
        }

        return (string)$max;
    }

    /**
     * Compare the value and inputed number, and get the max one
     *
     * @return integer The max number
     */
    public function maxTo($max)
    {
        if ($this->value > $max) {
            return $max;
        }

        return $this->value;
    }

    /**
     * Compare the value and inputed number, and get the min one
     *
     * @return integer The min number
     */
    public function minTo($max)
    {
        if ($this->value > $max) {
            return $max;
        }

        return $this->value;
    }
}
