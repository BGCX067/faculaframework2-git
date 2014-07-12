<?php

/**
 * Input Resulting Wrapping Object
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
     * @return void
     */
    final public function __construct($value)
    {
        $this->original = $value;

        switch (static::$dataType) {
            case 'Integer':
                $this->value = (integer)$value;
                break;

            case 'Float':
                $this->value = (float)$value;
                break;

            case 'String':
                $this->value = (string)$value;
                break;

            case 'Boolean':
                $this->value = $value ? true : false;
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
