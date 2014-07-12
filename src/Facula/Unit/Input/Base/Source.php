<?php

/**
 * Input Source
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

use Facula\Unit\Input\Base\Implement\Source as Impl;
use Facula\Unit\Input\Base\Error;

/**
 * Input Source
 */
abstract class Source implements Impl
{
    /** Error container */
    protected $errors = array();

    /**
     * Create a new instance of the source object
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Abstract constructor
     */
    abstract public function __construct();

    /**
     * Abstract: Get data from the source
     */
    abstract public function get($key);

    /**
     * Abstract: Get data accept status
     */
    abstract public function accepted();

    /**
     * Add a new error
     *
     * @param Error $error The instance of Error
     *
     * @return array All errors
     */
    final protected function error(Error $error)
    {
        $this->errors[] = $error;
    }

    /**
     * Check if there any error in the source
     *
     * @return bool Return true if there is error, false otherwise
     */
    final public function errored()
    {
        return !empty($this->errors);
    }

    /**
     * Get all errors from current source
     *
     * @return array All errors
     */
    final public function errors()
    {
        return $this->errors;
    }
}
