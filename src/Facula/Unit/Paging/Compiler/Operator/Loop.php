<?php

/**
 * Loop Tag Parser
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
use Facula\Unit\Paging\Compiler\OperatorBase as Base;
use Facula\Unit\Paging\Compiler\Parameters as Parameter;
use Facula\Unit\Paging\Compiler\Exception\Compiler\Operator as Exception;

/**
 * Loop tag parse
 */
class Loop extends Base implements Implement
{
    protected $pool = array();

    protected $data = '';
    protected $mainParameter = null;
    protected $endParameter = null;

    protected $parameters = array(
        'var' => 'default',
        'name' => 'default',
    );

    protected $middles = array();

    public static function register()
    {
        return array(
            'Middles' => array(
                'empty' => false,
            ),
        );
    }

    public function __construct()
    {

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
        $this->middles[$tag][] = $data;
    }

    public function compile()
    {
        $empty = '';

        if (!$varName = $this->convertVariableName($this->mainParameter->get('var'))) {
            throw new Exception\VariableNameInvaild($this->mainParameter->get('var'));
        }

        if (!$varKeyName = $this->isStandardString($this->mainParameter->get('name'))) {
            throw new Exception\NameInvaild($this->mainParameter->get('name'));
        }

        $php = '<?php if (isset(' . $varName . ') && empty(' . $varName . ')) { ';
        $php .= 'foreach (' . $varName . ' as $_' . $varKeyName . ' => $' . $varKeyName . ') { ?> ';
        $php .= $this->data;

        if (isset($this->middles['empty'])) {
            foreach ($this->middles['empty'] as $codes) {
                $empty .= $codes;
            }
        }

        if (!$empty) {
            $php .= '<?php }} ?>';
        } else {
            $php .= ' <?php }} else { ?>';
            $php .= $empty;
            $php .= ' <?php } ?>';
        }

        return $php;
    }
}
