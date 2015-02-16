<?php

/**
 * Tag Compiler of FriendlyTime Variables
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
 * FriendlyTime variables compiler
 */
class FriendlyTime implements Implement
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

        if (isset($pool['LanguageMap']['FORMAT_TIME_DEFAULT']) &&
            isset($pool['LanguageMap']['FORMAT_TIME_BEFORE_DAY']) &&
            isset($pool['LanguageMap']['FORMAT_TIME_BEFORE_HR']) &&
            isset($pool['LanguageMap']['FORMAT_TIME_BEFORE_MIN']) &&
            isset($pool['LanguageMap']['FORMAT_TIME_BEFORE_SND']) &&
            isset($pool['LanguageMap']['FORMAT_TIME_AFTER_SND']) &&
            isset($pool['LanguageMap']['FORMAT_TIME_AFTER_MIN']) &&
            isset($pool['LanguageMap']['FORMAT_TIME_AFTER_HR']) &&
            isset($pool['LanguageMap']['FORMAT_TIME_AFTER_DAY'])) {
            $phpCode .= '<?php '
                    . '$tempTime = $Time - (int)(' . $varName . ');'
                    // If small than 0, means after time
                    . 'if ($tempTime < 0) { $tempTime = abs($tempTime); '

                    . 'if ($tempTime < 60) { printf(\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_TIME_AFTER_SND'])
                    . '\', $tempTime); '

                    . '} elseif ($tempTime < 3600) { printf(\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_TIME_AFTER_MIN'])
                    . '\', (int)($tempTime / 60)); '

                    . '} elseif ($tempTime < 86400) { printf(\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_TIME_AFTER_HR'])
                    . '\', (int)($tempTime / 3600)); '

                    . '} elseif ($tempTime < 604800) { printf(\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_TIME_AFTER_DAY'])
                    . '\', (int)($tempTime / 86400)); '

                    . '} elseif ($tempTime) { echo(date(\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_TIME_DEFAULT'])
                    . '\', (int)(' . $varName . '))); } '

                    . '} else { ' // Or, if larger than 0 means before

                    . 'if ($tempTime < 60) { printf(\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_TIME_BEFORE_SND'])
                    . '\', $tempTime); '

                    . '} elseif ($tempTime < 3600) { printf(\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_TIME_BEFORE_MIN'])
                    . '\', (int)($tempTime / 60)); '

                    . '} elseif ($tempTime < 86400) { printf(\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_TIME_BEFORE_HR'])
                    . '\', (int)($tempTime / 3600)); '

                    . '} elseif ($tempTime < 604800) { printf(\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_TIME_BEFORE_DAY'])
                    . '\', (int)($tempTime / 86400)); '

                    . '} elseif ($tempTime) { echo(date(\''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_TIME_DEFAULT'])
                    . '\', (int)(' . $varName . '))); } '

                    . '} $tempTime = 0;'
                    . ' ?>';
        } else {
            throw new Exception\VariableFriendlyTimeFormatMissed(
                $varName,
                implode(', ', array(
                    'FORMAT_TIME_DEFAULT',
                    'FORMAT_TIME_BEFORE_DAY',
                    'FORMAT_TIME_BEFORE_HR',
                    'FORMAT_TIME_BEFORE_MIN',
                    'FORMAT_TIME_BEFORE_SND',
                    'FORMAT_TIME_AFTER_SND',
                    'FORMAT_TIME_AFTER_MIN',
                    'FORMAT_TIME_AFTER_HR',
                    'FORMAT_TIME_AFTER_DAY',
                ))
            );
        }

        return $phpCode;
    }
}
