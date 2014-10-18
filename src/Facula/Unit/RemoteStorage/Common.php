<?php

/**
 * Remote Storage Operator Common Functions
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

namespace Facula\Unit\RemoteStorage;

/**
 * Operator Common Functions
 */
abstract class Common
{
    /**
     * Generate a path for the uploading file
     *
     * @param string $fileName The name of file for path generate
     * @param integer $splitLen Name length of each sub folder
     *
     * @return string The generated path
     */
    protected function generatePath($fileName, $splitLen = 3)
    {
        $resultName = '';
        $validFileName = preg_replace(
            '/([^a-zA-Z0-9\x{007f}-\x{ffe5}\-\_\@]+)+/iu',
            '~',
            pathinfo(
                $fileName,
                PATHINFO_FILENAME
            )
        );

        while (strpos($validFileName, '~~') !== false) {
            $validFileName = str_replace('~~', '~', $validFileName);
        }

        $fileNameLen = strlen($validFileName);
        $fileNameLastIdx = $fileNameLen - 1;
        $fileNameSplitLen = $fileNameLen - $splitLen;

        if ($fileNameLen >= $splitLen) {
            for ($charLoop = 0; $charLoop < $fileNameSplitLen;) {
                for ($elLoop = 0; $elLoop < $splitLen; $elLoop++) {
                    $resultName .= $validFileName[$charLoop];

                    if (++$charLoop > $fileNameLastIdx) {
                        break;
                    }
                }

                $resultName .= '/';
            }
        } else {
            $resultName = date('Y')
                . '/'
                . abs((int)(crc32(date('m/w')) / 10240));
        }

        return $resultName;
    }
}
