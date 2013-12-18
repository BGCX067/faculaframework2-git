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
 * @package    FaculaFramework
 * @version    2.2 prototype
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Unit;

abstract class Pager
{
    public static function get($itemprepage, $current, $totalitems = 0, $maxpages = 6000)
    {
        $tp = $p = 0;
        $vip = $vc = $vti = 0;

        $vc = (int)($current) - 1;
        $vip = (int)($itemprepage);
        $vti = (int)($totalitems);

        if ($vc < 0) {
            $vc = 0;
        } elseif ($vc > ($maxpages ? $maxpages : 5000)) {
            $vc = $maxpages;
        }

        if ($vti) {
            $tp = ceil($vti > $vip ? $vti / $vip : 1);
            $tp = $tp > $maxpages ? $maxpages : $tp;

            if ($vc >= $tp) {
                $vc = $tp - 1;
            }
        }

        $p = $vip * $vc;

        return array('Offset' => $p, 'Distance' => $vip, 'Current' => $vc ? $vc + 1 : 1, 'TotalPages' => $tp, 'MaxPagesDisplay' => $maxpages);
    }

    public static function getSwitch($current, $hasNext = false)
    {
        $currentPage = $current > 0 ? (int)($current) : 1;

        return array(
            'Previous' => $currentPage > 1 ? $currentPage - 1 : 0,
            'Current' => $currentPage,
            'Next' => $hasNext ? $currentPage + 1 : 0,
        );
    }
}
