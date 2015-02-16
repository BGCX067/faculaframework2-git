<?php

/**
 * Tag Compiler of Bytes Variables
 *
 * Facula Framework 2015 (C) Rain Lee
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
 * @copyright  2015 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Unit\Paging\Compiler\Operator\Variable\Operator;

use Facula\Unit\Paging\Compiler\Operator\Variable\OperatorImplement as Implement;
use Facula\Unit\Paging\Compiler\Exception\Compiler\Operator as Exception;

/**
 * Bytes variables compiler
 */
class Bytes implements Implement
{
    /**
     * Convert variable format
     *
     * @param string $varName Name of the variable
     * @param array $parameter Array contains format parameters
     * @param array $pool Data that may needed for compile
     *
     * @return string Compiled PHP code
     */
    public static function convert($varName, array $parameter, array $pool)
    {
        $phpCode = '';

        if (isset($pool['LanguageMap']['FORMAT_BYTES_BYTES']) &&
            isset($pool['LanguageMap']['FORMAT_BYTES_KILOBYTES']) &&
            isset($pool['LanguageMap']['FORMAT_BYTES_MEGABYTES']) &&
            isset($pool['LanguageMap']['FORMAT_BYTES_GIGABYTES']) &&
            isset($pool['LanguageMap']['FORMAT_BYTES_TRILLIONBYTES'])) {
            $phpCode .= '<?php $tempsize = '
                    . $varName
                    . '; if ($tempsize < 1024) { echo (($tempsize).\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_BYTES_BYTES'])
                    . '\'); } elseif ($tempsize < 1048576) {'
                    . ' echo ((int)($tempsize / 1024).\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_BYTES_KILOBYTES'])
                    . '\'); } elseif ($tempsize < 1073741824) {'
                    . ' echo (round($tempsize / 1048576, 1).\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_BYTES_MEGABYTES'])
                    . '\'); } elseif ($tempsize < 1099511627776) {'
                    . ' echo (round($tempsize / 1073741824, 2).\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_BYTES_GIGABYTES'])
                    . '\'); } elseif ($tempsize < 1125899906842624) {'
                    . ' echo (round($tempsize / 1099511627776, 3).\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_BYTES_TRILLIONBYTES'])
                    . '\'); } $tempsize = 0; ?>';
        } else {
            throw new Exception\VariableBytesFormatMissed(
                $varName,
                implode(', ', array(
                    'FORMAT_BYTES_BYTES',
                    'FORMAT_BYTES_KILOBYTES',
                    'FORMAT_BYTES_MEGABYTES',
                    'FORMAT_BYTES_GIGABYTES',
                    'FORMAT_BYTES_TRILLIONBYTES',
                ))
            );
        }

        return $phpCode;
    }
}
