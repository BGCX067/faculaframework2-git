<?php

/**
 * Tag Compiler of Numeric Variables
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

/**
 * Numeric variables compiler
 */
class Numeric implements Implement
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
        return '<?php echo(number_format(' . $varName . ')); ?>';
    }
}
