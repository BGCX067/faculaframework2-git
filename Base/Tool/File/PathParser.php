<?php

/**
 * Path Parser
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

namespace Facula\Base\Tool\File;

/**
 * Parsing the path string to make it valid
 */
class PathParser
{
    protected static $config = array(
        'Separators' => array(
            '\\', '/'
        ),
        'NoEnding' => true,
    );

    public static function get($path)
    {
        $rightPath = rtrim(
            str_replace(
                static::$config['Separators'],
                DIRECTORY_SEPARATOR,
                $path
            ),
            DIRECTORY_SEPARATOR
        );

        while (strpos($rightPath, DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR) === true) {
            $rightPath = str_replace(
                DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR,
                $rightPath
            );
        }

        if (!static::$config['NoEnding']) {
            $rightPath .= DIRECTORY_SEPARATOR;
        }

        return $rightPath;
    }
}
