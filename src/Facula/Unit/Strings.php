<?php

/**
 * Strings
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

namespace Facula\Unit;

/* Define function, use mb_* when available */
define('FUNC_STRLEN', function_exists('mb_strlen') ? 'mb_strlen' : 'strlen');
define('FUNC_SUBSTR', function_exists('mb_substr') ? 'mb_substr' : 'substr');
define('FUNC_STRPOS', function_exists('mb_strpos') ? 'mb_strpos' : 'strpos');

/**
 * String Operator
 */
abstract class Strings
{
    /**
     * Wrapped version of substr but with mb_substr
     *
     * @param string $string The string to operate
     * @param string $start Start position to get substring
     * @param string $len Length of result string
     * @param boolean $apostrophe Will we display apostrophe at end when for
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
        $FUNC_STRLEN = FUNC_STRLEN;
        $FUNC_SUBSTR = FUNC_SUBSTR;

        if ($len > $FUNC_STRLEN($string)) {
            return $string;
        } else {
            if ($apostrophe && $len > 3) {
                return $FUNC_SUBSTR($string, $start, $len - 3) . '...';
            } else {
                return $FUNC_SUBSTR($string, $start, $len);
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
        $FUNC_STRLEN = FUNC_STRLEN;

        return $FUNC_STRLEN($string);
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
        $FUNC_STRPOS = FUNC_STRPOS;

        return $FUNC_STRPOS($haystack, $needle, $offset);
    }
}
