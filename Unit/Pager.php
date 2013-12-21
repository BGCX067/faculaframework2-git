<?php

/**
 * Pager Unit
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
 * Get arguments for page switching
 */
abstract class Pager
{
    /**
     * Get the page switch arguments for number-list page switcher
     *
     * @param integer $itemPerPage Item per page
     * @param integer $current Current page
     * @param integer $totalItems How many items totally
     * @param integer $maxPages Max items of number-list page switcher
     *
     * @return array Page switch arguments
     */
    public static function get(
        $itemPerPage,
        $current,
        $totalItems = 0,
        $maxPages = 6000
    ) {
        $tp = $p = 0;
        $vip = $vc = $vti = 0;

        $vc = (int)($current) - 1;
        $vip = (int)($itemPerPage);
        $vti = (int)($totalItems);

        if ($vc < 0) {
            $vc = 0;
        } elseif ($vc > ($maxPages ? $maxPages : 5000)) {
            $vc = $maxPages;
        }

        if ($vti) {
            $tp = ceil($vti > $vip ? $vti / $vip : 1);
            $tp = $tp > $maxPages ? $maxPages : $tp;

            if ($vc >= $tp) {
                $vc = $tp - 1;
            }
        }

        $p = $vip * $vc;

        return array(
            'Offset' => $p,
            'Distance' => $vip,
            'Current' => $vc ? $vc + 1 : 1,
            'TotalPages' => $tp,
            'MaxPagesDisplay' => $maxPages
        );
    }

    /**
     * Get the page switch arguments for previous-next page switcher
     *
     * @param integer $current Current page
     * @param bool $hasNext Is there content for next page?
     *
     * @return array Page switch arguments
     */
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
