<?php

/**
 * Maxmin Limit
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
 * Maxmin Limit
 */
class Maxmin extends Base
{
    /** Default format */
    protected $format = '';

    /** Max length */
    protected $max = 0;

    /** Min length */
    protected $min = 0;

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
        $inputedVal = 0;

        if (is_string($value) && is_numeric($value)) {
            if (strpos($value, '.')) {
                // Check if is float
                $inputedVal = (float)$value;
            } else {
                $inputedVal = (int)$value;
            }
        } elseif (is_integer($value) || is_float($value)) {
            // Valid, do nothing
            $inputedVal = $value;
        } else {
            $error = new Error('INVALID', 'DATATYPE', array(gettype($value)));

            return false;
        }

        if ($inputedVal > $this->max) {
            $error = new Error('INVALID', 'TOOLARGE', array(
                'Max' => $this->max
            ));

            return false;
        }

        if ($inputedVal < $this->min) {
            $error = new Error('INVALID', 'TOOSMALL', array(
                'Min' => $this->min
            ));

            return false;
        }

        return true;
    }

    /**
     * Set the max limit
     *
     * @param integer $max The max value
     *
     * @return Current instance of limit object
     */
    public function max($max)
    {
        $this->max = (integer)$max;

        return $this;
    }

    /**
     * Set the min limit
     *
     * @param integer $min The min value
     *
     * @return Current instance of limit object
     */
    public function min($min)
    {
        $this->min = (integer)$min;

        return $this;
    }
}
