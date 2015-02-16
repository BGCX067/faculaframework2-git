<?php

/**
 * Check the limit on an array of data
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

namespace Facula\Unit\Input\Limit;

use Facula\Unit\Input\Base\Limit as Base;
use Facula\Unit\Input\Base\Field\Error as Error;
use Facula\Unit\Validator;

/**
 * Limit an array of data
 */
class Eachs extends Base
{
    protected $limits = array();

    /** The max amount of elements in array */
    protected $max = 0;

    /** The min amount of elements in array */
    protected $min = 0;

    /**
     * Check if the input is valid
     *
     * @param mixed $values The value to check
     * @param Error $error The reference for getting error feedback
     *
     * @return bool Return True when it's qualified, false otherwise
     */
    public function qualified(&$values, &$error)
    {
        if (!is_array($values)) {
            $error = new Error('INVALID', 'DATATYPE', array());

            return false;
        }

        $amount = count($values);

        if ($this->max && $amount > $this->max) {
            $error = new Error(
                'INVALID',
                'TOOMANY',
                array(
                    'Max' => $this->max
                )
            );

            return false;
        }

        if ($this->min && $amount < $this->min) {
            $error = new Error(
                'INVALID',
                'TOOLESS',
                array(
                    'Min' => $this->min
                )
            );

            return false;
        }

        foreach ($values as $value) {
            foreach ($this->limits as $limit) {
                if (!$limit->qualified($value, $error)) {
                    $error = new Error($error->type(), $error->code(), $error->data());

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Set a limit
     *
     * @param Limit $limit The limit
     *
     * @return object Return current object instance
     */
    public function limit(Base $limit)
    {
        $this->limits[] = $limit;

        return $this;
    }

    /**
     * Set multi limits
     *
     * @return object Return current object instance
     */
    public function limits()
    {
        foreach (func_get_args() as $arg) {
            $this->limit($arg);
        }

        return $this;
    }

    /**
     * Set the max amount limit
     *
     * @param integer $max The max amount limit
     *
     * @return object Return current object instance
     */
    public function max($max)
    {
        $this->max = (int)$max;

        return $this;
    }

    /**
     * Set the min amount limit
     *
     * @param integer $min The min amount limit
     *
     * @return object Return current object instance
     */
    public function min($min)
    {
        $this->min = (int)$min;

        return $this;
    }
}
