<?php

/**
 * Logic Tag Parser
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

namespace Facula\Unit\Paging\Compiler\Operator;

use Facula\Unit\Paging\Compiler\OperatorImplement as Implement;
use Facula\Unit\Paging\Compiler\Parameters as Parameter;

/**
 * Logic tag parse
 */
class Logic implements Implement
{
    protected $data = '';
    protected $mainParameter = null;
    protected $endParameter = '';

    protected $parameters = array(

    );

    public static function register()
    {
        return array(
            'Middles' => array(
                'else' => false,

            ),
        );
    }

    public function setParameter($type, $param)
    {
        switch ($type) {
            case 'Main':
                $this->mainParameter = new Parameter($param, $this->parameters);
                break;

            case 'End':
                $this->endParameter = new Parameter($param, $this->parameters);
                break;

            default:
                break;
        }
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function setMiddle($tag, $param, $data)
    {

    }

    public function compile()
    {

    }
}
