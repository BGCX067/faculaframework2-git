<?php

/**
 * Operator Base
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

namespace Facula\Unit\Paging\Compiler;

abstract class OperatorBase
{
    protected function convertVariableName($string)
    {
        if (!$string) {
            return false;
        }

        if ($string[0] == '$') {
            if (!$this->isStandardString($string)) {
                return false;
            }

            return $string;
        }

        $varNameArray = explode('.', $string);

        $varName = '$' . array_shift($varNameArray);

        if (!empty($varNameArray)) {
            foreach ($varNameArray as $arrayTable) {
                $varName .= '[\'' . $arrayTable . '\']';
            }
        }

        return $varName;
    }

    protected function isStandardString($string)
    {
        return \Facula\Unit\Validator::check($string, 'alphanumber');
    }
}
