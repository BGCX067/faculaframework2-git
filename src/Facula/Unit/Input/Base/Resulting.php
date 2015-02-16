<?php

/**
 * Input Resulting Wrapping Object
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

namespace Facula\Unit\Input\Base;

use Facula\Unit\Input\Base\Exception\Resulting as Exception;
use Facula\Unit\Input\Base\Implement\Resulting as Impl;

/**
 * Input Resulting Wrapping Object
 */
abstract class Resulting implements Impl
{
    /** The result value */
    protected $value = null;

    /** The unconverted original value */
    protected $original = null;

    /** Data type of the value */
    protected static $dataType = '';

    /**
     * Constructor
     *
     * @param mixed $value The value
     * @param mixed $original The original value
     *
     * @return void
     */
    final public function __construct($value, $original)
    {
        $this->original = $original;

        switch (static::$dataType) {
            case 'Data':
                $this->value = $value;
                break;

            case 'Group':
                if (!is_array($value)) {
                    throw new Exception\GroupItemMustBeArray(gettype($value));

                    return false;
                }

                foreach ($value as $key => $val) {
                    if (!is_subclass_of($val, __CLASS__)) {
                        throw new Exception\GroupItemMustInherit(get_class($val), __CLASS__);

                        return false;
                        break 2;
                    }

                    $this->value[$key] = $val;
                }

                // Group can be empty so check it there,
                // or we will get null instead of array
                if (empty($value)) {
                    $this->value = array();
                }
                break;

            case 'Wrapper':
                $this->value = $value;
                break;

            default:
                throw new Exception\UnknownDataType($this->dataType);

                return;
                break;
        }
    }

    /**
     * Get the value
     *
     * @return mixed The value of this resulting object
     */
    final public function value()
    {
        return $this->value;
    }

    /**
     * Get the value
     *
     * @return mixed The original value of this resulting object
     */
    final public function original()
    {
        return $this->original;
    }
}
