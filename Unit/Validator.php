<?php

/**
 * Facula Framework Struct Manage Unit
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

abstract class Validator
{
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

    public static function check($string, $type = '', $max = 0, $min = 0, &$error = '')
    {
        $strLen = 0;

        if ($string && (!$type || (isset(self::$regulars[$type]) && preg_match(self::$regulars[$type], $string)))) {
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

    public static function add($name, $regular)
    {
        if (!isset(self::$regulars[$name])) {
            self::$regulars[$name] = $regular;

            return true;
        }

        return false;
    }

    public static function export()
    {
        return self::$regulars;
    }
}
