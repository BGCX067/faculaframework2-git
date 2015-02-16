<?php

/**
 * Base of Page Compiler Operator
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

namespace Facula\Unit\Paging\Compiler;

use Facula\Unit\Paging\Compiler\Exception\Compiler\Operator as Exception;
use Facula\Base\Factory\Operator as Base;

/**
 * Base of operators
 */
abstract class OperatorBase extends Base
{
    /** Preset a empty operator array */
    protected static $operators = array();

    /**
     * Get pure variable name with out $ and []
     *
     * @param string $varName Name of the variable
     *
     * @return string Pure variable name
     */
    protected function getPureVarName($varName)
    {
        $varPureNameMatchs = array();

        if (!preg_match(
            '/^\$([A-Za-z0-9_]+)/iu',
            $varName,
            $varPureNameMatchs,
            PREG_OFFSET_CAPTURE
        )) {
            throw new Exception\BaseInvalidVariableName(
                $varName
            );

            return false;
        } else {
            return $varPureNameMatchs[1][0];
        }
    }
}
