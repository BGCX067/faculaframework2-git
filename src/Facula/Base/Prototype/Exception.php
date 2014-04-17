<?php

/**
 * Exception Base Prototype
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

namespace Facula\Base\Prototype;

use Facula\Base\Implement\Exception as Implement;

/**
 * Base class of execption
 */
abstract class Exception extends \Exception implements Implement
{
    /** Default exception message */
    protected static $exceptionMessage = 'Exception without message.';

    /** Default exception level */
    protected static $exceptionLevel = E_USER_ERROR;

    /**
     * Constructor
     *
     * Parameters will be use for the second parameter vsprintf.
     * to formating the error message.
     *
     * @return void
     */
    final public function __construct()
    {
        $message = $tempMessage = '';

        $message = vsprintf(
            static::$exceptionMessage,
            func_get_args()
        );

        while (true) {
            $tempMessage = str_replace(
                array("\n", "\r", '  '),
                ' ',
                $message
            );

            if ($tempMessage == $message) {
                break;
            }

            $message = $tempMessage;
        }

        return parent::__construct(
            trim($message),
            static::$exceptionLevel
        );
    }

    /**
     * Convert object to string
     *
     * @return string Return the error message
     */
    final public function __toString()
    {
        return __CLASS__ . ':[ ' . $this->code . ']: ' . $this->message;
    }
}
