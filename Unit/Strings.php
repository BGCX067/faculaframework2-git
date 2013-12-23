<?php

/**
 * Strings
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
 * String Operator
 */
abstract class Strings
{
    /** Tag to anti-reinitialize */
    protected static $inited = false;

    /** Default internal encode */
    protected static $default_encode = 'utf-8';

    /**
     * Initialize the class
     *
     * @return bool Return true when initialized, false when initialize failed
     */
    protected static function selfInit()
    {
        if (static::$inited) {
            return true;
        }

        if (mb_internal_encoding(static::$default_encode)) {
            return true;
        }

        return false;
    }

    /**
     * Wrapped version of substr but with mb_substr
     *
     * @param string $string The string to operate
     * @param string $start Start position to get substring
     * @param string $len Length of result string
     * @param string $apostrophe Will we display apostrophe at end when for
     *                           indicate there some content been cut out
     *
     * @return bool Return result of mb_substr
     */
    public static function substr(
        $string,
        $start,
        $len,
        $apostrophe = false
    ) {
        static::selfInit();

        if ($len > mb_strlen($string)) {
            return $string;
        } else {
            if ($apostrophe && $len > 3) {
                return mb_substr($string, $start, $len - 3) . '...';
            } else {
                return mb_substr($string, $start, $len);
            }

        }

        return false;
    }

    /**
     * Wrapped version of strlen but with mb_strlen
     *
     * @param string $string The string to operate
     *
     * @return bool Return result of mb_strlen
     */
    public static function strlen($string)
    {
        static::selfInit();

        return mb_strlen($string);
    }

    /**
     * Wrapped version of strpos but with mb_strpos
     *
     * @param string $haystack The string for search
     * @param string $needle Search this in the string
     * @param string $offset Start position for search
     *
     * @return bool Return result of mb_strpos
     */
    public static function strpos(
        $haystack,
        $needle,
        $offset = 0
    ) {
        static::selfInit();

        return mb_strpos($haystack, $needle, $offset);
    }
}
