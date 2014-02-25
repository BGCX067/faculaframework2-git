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
    /** Configuration of this class */
    protected static $config = array(
        'Separators' => array(
            '\\', '/'
        ),
        'NoEnding' => true,
    );

    /**
    * Get the valid path
    *
    * @param string $path The path to get
    *
    * @return string Valid path
    */
    public static function get($path)
    {
        // Check type of this path
        if (($uriPos = strpos($path, '://')) !== false) { // 1: URI: http://123.com/dir/ (file://c/system32/)
            $rightPath = static::replaceSub(
                $path,
                static::$config['Separators'],
                '/',
                $uriPos + 3,
                strlen($path),
                static::$config['NoEnding']
            );
        } elseif (($uriPos = strpos($path, '\\\\')) !== false && $uriPos == 0) { // 2: Samba server addr \\123.com\dir\
            $rightPath = static::replaceSub(
                $path,
                static::$config['Separators'],
                '\\',
                $uriPos + 2,
                strlen($path),
                static::$config['NoEnding']
            );
        } else { // Normal file system path
            $rightPath = static::replaceSub(
                $path,
                static::$config['Separators'],
                DIRECTORY_SEPARATOR,
                0,
                strlen($path),
                static::$config['NoEnding']
            );
        }

        return $rightPath;
    }

    protected static function replaceSub(
        $string,
        $find,
        $replaceTo,
        $startPos = 0,
        $endPos = 0,
        $addEnding = false
    ) {
        $result = $beforeStr = $targetStr = $afterStr = '';
        $finds = array();

        $beforeStr = substr($string, 0, $startPos);
        $afterStr = substr($string, $endPos, strlen($string) - 1);

        $targetStr = substr($string, $startPos, $endPos);

        if (!is_array($find)) {
            $finds[] = $find;
        } else {
            $finds = $find;
        }

        foreach ($finds as $word) {
            $targetStr = str_replace($word, $replaceTo, $targetStr);

            while (strpos($targetStr, $replaceTo . $replaceTo) !== false) {
                $targetStr = str_replace($replaceTo . $replaceTo, $replaceTo, $targetStr);
            }
        }

        $result = rtrim($beforeStr . $targetStr . $afterStr, $replaceTo);

        if ($addEnding && (!$result || $result[strlen($result) - 1] != $replaceTo)) {
            $result .= $replaceTo;
        }

        return $result;
    }
}
