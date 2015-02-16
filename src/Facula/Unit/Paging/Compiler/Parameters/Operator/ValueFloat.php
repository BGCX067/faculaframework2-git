<?php

/**
 * Float parameter parser
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

namespace Facula\Unit\Paging\Compiler\Parameters\Operator;

use Facula\Unit\Paging\Compiler\Parameters\OperatorImplement as Implement;

/**
 * Default Parameters
 */
class ValueFloat implements Implement
{
    protected $var = '';

    /**
     * Constructor
     *
     * @param mixed $var The var to be converted in to parameter format
     *
     * @return void
     */
    public function __construct($var)
    {
        $this->var = (float)$var;
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
