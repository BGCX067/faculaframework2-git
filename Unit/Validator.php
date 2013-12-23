<?php

/**
 * String Validator
 *
 * Facula Framework 2013 (C) Rain Lee
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
 * @copyright  2013 Rain Lee
 * @package    Facula
 * @version    2.2 prototype
 * @see        https://github.com/raincious/facula FYI
 */

namespace Facula\Unit;

/**
 * String Formation Validator
 */
abstract class Validator
{
    /** Default regulars */
    private static $regulars = array(
        'email' => '/^[a-zA-Z0-9\_\-\.]+\@[a-zA-Z0-9\_\-\.]+\.[a-zA-Z0-9\_\-\.]+$/u',
        'password' => '/^[a-fA-F0-9]+$/i',
        'username' => '/^[A-Za-z0-9\x{007f}-\x{ffe5}\.\_\-]+$/u',
        'standard' => '/^[A-Za-z0-9\x{007f}-\x{ffe5}\.\_\@\-\:\#\,\s]+$/u',
        'filename' => '/^[A-Za-z0-9\s\(\)\.\-\,\_\x{007f}-\x{ffe5}]+$/u',
        'url' => '/^[a-zA-Z0-9]+\:\/\/[a-zA-Z0-9\&\;\.\#\/\?\-\=\_\+\:\%\,]+$/u',
        'urlelement' => '/[a-zA-Z0-9\.\/\?\-\=\&\_\+\:\%\,]+/u',
        'number' => '/^[0-9]+$/u',
        'integer' => '/^(\+|\-|)[0-9]+$/u',
        'float' => '/^(\+|\-|)[0-9]+(\.[0-9]|)+$/u',
    );

    /**
     * Check if the string is valid
     *
     * @param string $string The string to check
     * @param string $type Type if the string
     * @param integer $max Max length of the string
     * @param integer $min Min length of the string
     * @param string $error Min length of the string
     *
     * @return bool Return true when the string is valid, false when not
     */
    public static function check($string, $type = '', $max = 0, $min = 0, &$error = '')
    {
        $strLen = 0;

        if ($string
            &&
            (
                !$type
                ||
                (
                    isset(self::$regulars[$type])
                    && preg_match(self::$regulars[$type], $string)
                )
            )
        ) {
            $strLen = mb_strlen($string);

            if ($max && $max < $strLen) {
                $error = 'TOOLONG';

                return false;
            } elseif ($min && $min > $strLen) {
                $error = 'TOOSHORT';

                return false;
            }

            return $string;
        } else {
            $error = 'FORMAT';
        }

        return false;
    }

    /**
     * Add new regular into class
     *
     * @param string $type Type if the regular
     * @param string $regular Regular itself
     *
     * @return bool Return true when added, false on failed
     */
    public static function add($type, $regular)
    {
        if (!isset(self::$regulars[$type])) {
            self::$regulars[$type] = $regular;

            return true;
        }

        return false;
    }

    /**
     * Export all regulars in the class
     *
     * @return array Regulars in array
     */
    public static function export()
    {
        return self::$regulars;
    }
}
