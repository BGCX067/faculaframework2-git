<?php

/**
 * Variable parameter parser
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

namespace Facula\Unit\Paging\Compiler\Parameters\Operator;

use Facula\Unit\Paging\Compiler\Parameters\OperatorImplement as Implement;
use Facula\Unit\Paging\Compiler\Exception\Parameters as Exception;
use Facula\Unit\Validator as Validator;

/**
 * Default Parameters
 */
class ValueVariable implements Implement
{
    protected $var = '';

    /**
     * Constructor
     *
     * @param mixed $var The var to be converted in to parameter format
     *
     * @return void
     */
    public function __construct($string)
    {
        if (!$string) {
            return false;
        }

        if (!isset($string[0])) {
            throw new Exception\EmptyVariableName();

            return;
        }

        if ($string[0] == '$') {
            if (!isset($string[1])
            || !preg_match('/^\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/', $string)) {
                throw new Exception\InvalidVariableName(
                    $string
                );

                return;
            }

            $varName = $string;
        } else {
            if (is_numeric($string[0])) {
                throw new Exception\InvalidVariableName(
                    $string
                );

                return;
            }

            $varNameArray = explode('.', str_replace('\\.', "\0", $string));

            $varName = '$' . array_shift($varNameArray);

            if (!preg_match('/^\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/', $varName)) {
                throw new Exception\InvalidVariableName(
                    $string
                );

                return;
            }

            if (!empty($varNameArray)) {
                foreach ($varNameArray as $arrayTable) {
                    $varName .= '[\'' . str_replace("\0", '.', $arrayTable) . '\']';
                }
            }
        }

        $this->var = $varName;
    }

    /**
     * Get convert result
     *
     * @return mixed Return the result of the convert
     */
    public function result()
    {
        return $this->var;
    }
}
