<?php

/**
 * Input Field Interior Error Container
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

namespace Facula\Unit\Input\Base\Field;

use Facula\Unit\Input\Base\Exception\Field\Error as Exception;

/**
 * Input Field Interior Error Container
 */
class Error
{
    /** Valid error types */
    protected static $validTypes = array(
        'INVALID' => 'INVALID',
        'ERROR' => 'ERROR',
        'INFO' => 'INFO'
    );

    /** Default error type */
    protected $type = 'INVALID';

    /** Default error code */
    protected $code = 'UNKNOWN';

    /** Default error data */
    protected $data = array();

    /**
     * Constructor
     *
     * @param string $type The type if this error
     * @param string $code The code if this error
     * @param array $data The data of this error
     *
     * @return void
     */
    public function __construct($type, $code, array $data = array())
    {
        if (!isset(static::$validTypes[$type])) {
            throw new Exception\InvalidErrorType($type);

            return;
        }

        $this->type = static::$validTypes[$type];
        $this->code = $code;
        $this->data = $data;
    }

    /**
     * Get type
     *
     * @return string The type of current error
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Get code
     *
     * @return string The code of current error
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * Get data
     *
     * @return array The data of current error
     */
    public function data()
    {
        return $this->data;
    }
}
