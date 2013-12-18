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

namespace \Facula\Base\Tool\Misc;

abstract class PHPIni
{
    public static function convertIniUnit($str)
    {
        $strLen = 0;
        $lastChar = '';

        if (is_numeric($str)) {
            return (int)$str;
        } else {
            $strLen = strlen($str);

            if ($lastChar = $str[$strLen - 1]) {
                $strSelected = substr($str, 0, $strLen - 1);

                switch (strtolower($lastChar)) {
                    case 'k':
                        return (int)($strSelected) * 1024;
                        break;

                    case 'm':
                        return (int)($strSelected) * 1048576;
                        break;

                    case 'g':
                        return (int)($strSelected) * 1073741824;
                        break;
                }
            }
        }

        return 0;
    }
}
