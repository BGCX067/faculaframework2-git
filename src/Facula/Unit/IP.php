<?php

/**
 * IP Unit
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
 * @copyright  2014 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Unit;

/**
 * IP Unit
 */
abstract class IP
{
    /**
     * Split IP address into array
     *
     * @param string $ip The IP that will be splited
     *
     * @return array Spilted IP address
     */
    private static function splitIP($ip)
    {
        // Max is 8 for a IP addr
        return explode(':', str_replace('.', ':', $ip), 8);
    }

    /**
     * Join spilted IP address in to string
     *
     * @param array $ip The IP that will be joined
     * @param array $mask Mask the ending address
     *
     * @return string Joined IP address
     */
    public static function joinIP(array $ip, $mask = false)
    {
        foreach (array_reverse($ip) as $v) {
            if (!$v) {
                array_pop($ip);
            } else {
                break;
            }
        }

        $iplen = count($ip);

        if ($mask) {
            if ($iplen > 3) {
                $ip[$iplen - 2] = $ip[$iplen - 1] = '*';
            } elseif ($iplen > 2) {
                $ip[$iplen - 1] = '*';
            }
        }

        if ($iplen == 4) {
            return implode(
                '.',
                array(
                    $ip[0],
                    $ip[1],
                    $ip[2],
                    $ip[3]
                )
            );
        }

        return implode(':', $ip);
    }
}
