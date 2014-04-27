<?php

/**
 * Tag Compiler of FriendlyNumber Variables
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

namespace Facula\Unit\Paging\Compiler\Operator\Variable\Operator;

use Facula\Unit\Paging\Compiler\Operator\Variable\OperatorImplement as Implement;
use Facula\Unit\Paging\Compiler\Exception\Compiler\Operator as Exception;

/**
 * FriendlyNumber variables compiler
 */
class FriendlyNumber implements Implement
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

        if (isset($pool['LanguageMap']['FORMAT_NUMBER_HUNDRED']) &&
            isset($pool['LanguageMap']['FORMAT_NUMBER_THOUSAND']) &&
            isset($pool['LanguageMap']['FORMAT_NUMBER_MILLION']) &&
            isset($pool['LanguageMap']['FORMAT_NUMBER_BILLION']) &&
            isset($pool['LanguageMap']['FORMAT_NUMBER_TRILLION']) &&
            isset($pool['LanguageMap']['FORMAT_NUMBER_QUADRILLION']) &&
            isset($pool['LanguageMap']['FORMAT_NUMBER_QUINTILLION']) &&
            isset($pool['LanguageMap']['FORMAT_NUMBER_SEXTILLION'])) {
            $phpCode .= '<?php if ('
                    . $varName
                    . ' > 1000000000000000000000) { echo(round(('
                    . $varName
                    . ' / 1000000000000000000000) , 2) . \''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_NUMBER_SEXTILLION'])
                    . '\'); } elseif ('
                    . $varName
                    . ' > 1000000000000000000) { echo(round(('
                    . $varName
                    . ' / 1000000000000000000) , 2) . \''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_NUMBER_QUINTILLION'])
                    . '\'); } elseif ('
                    . $varName
                    . ' > 1000000000000000) { echo(round(('
                    . $varName
                    . ' / 1000000000000000) , 2) . \''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_NUMBER_QUADRILLION'])
                    . '\'); } elseif ('
                    . $varName
                    . ' > 1000000000000) { echo(round(('
                    . $varName
                    . ' / 1000000000000) , 2) . \''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_NUMBER_TRILLION'])
                    . '\'); } elseif ('
                    . $varName
                    . ' > 1000000000) { echo(round(('
                    . $varName
                    . ' / 1000000000) , 2) . \''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_NUMBER_BILLION'])
                    . '\'); } elseif ('
                    . $varName
                    . ' > 1000000) { echo(round(('
                    . $varName
                    . ' / 1000000) , 2) . \''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_NUMBER_MILLION'])
                    . '\'); } elseif ('
                    . $varName
                    . ' > 1000) { echo(round(('
                    . $varName
                    . ' / 1000) , 2) . \''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_NUMBER_THOUSAND'])
                    . '\'); } elseif ('
                    . $varName
                    . ' > 100) { echo(round(('
                    . $varName
                    . ' / 100) , 2) . \''
                    . str_replace('\'', '\\\'', $pool['LanguageMap']['FORMAT_NUMBER_HUNDRED'])
                    . '\'); } else { echo('
                    . $varName
                    . '); } ?>';
        } else {
            throw new Exception\VariableFriendlyNumberFormatMissed(
                $varName,
                implode(
                    ', ',
                    array(
                        'FORMAT_NUMBER_HUNDRED',
                        'FORMAT_NUMBER_THOUSAND',
                        'FORMAT_NUMBER_MILLION',
                        'FORMAT_NUMBER_BILLION',
                        'FORMAT_NUMBER_TRILLION',
                        'FORMAT_NUMBER_QUADRILLION',
                        'FORMAT_NUMBER_QUINTILLION',
                        'FORMAT_NUMBER_SEXTILLION',
                    )
                )
            );
        }

        return $phpCode;
    }
}
