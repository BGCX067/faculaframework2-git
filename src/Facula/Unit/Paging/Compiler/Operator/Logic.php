<?php

/**
 * Logic Tag Compiler
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
use Facula\Unit\Paging\Compiler\Exception\Compiler\Operator as Exception;

/**
 * Logic tag parse
 */
class Logic implements Implement
{
    protected $data = '';
    protected $mainParameter = '';
    protected $endParameter = '';

    protected $parameters = array(
        'var' => 'variable', // var="$variable"

        // variable compare to another variable
        'equals' => 'variable', // var="$variable" equal="$someothervar"
        'unequals' => 'variable', // var="$variable" unequal="$someothervar"

        // variable compare to another value
        'is' => 'variable', // var="$variable" is="$someothervar"
        'not' => 'variable', // var="$variable" not="$someothervar"

        // variable compare to another token (true | false | null)
        'fits' => 'default', // var="$variable" fits="true"
        'unfits' => 'default', // var="$variable" unfits="false"
    );

    protected $middles = array();

    public static function register()
    {
        return array(
            'Middles' => array(
                'else' => false,
                'elseif' => true,
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
                $this->mainParameter = new Parameter(
                    $param,
                    $this->parameters
                );
                break;

            case 'End':
                $this->endParameter = new Parameter(
                    $param,
                    $this->parameters
                );
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
        $this->middles[$tag][] = array(
            'Parameter' => trim($param),
            'Data' => $data,
        );
    }

    public function getIsDataFitType($type)
    {
        $result = '';

        switch ($type) {
            case 'false':
                $result = 'false';
                break;

            case 'true':
                $result = 'true';
                break;

            case 'null':
                $result = 'null';
                break;

            default:
                break;
        }

        return $result;
    }

    protected function compileParameters(
        Parameter $parameters
    ) {
        $php = '';
        $stacks = array();
        $lastVarName = '';
        $params = $parameters->fetch();

        if (isset($params[0]) && !$params[0]['Tag'] !== 'var') {
            throw new Exception\LogicFirstParamaterMustBeVar();
        }

        foreach ($parameters->fetch() as $params) {
            switch ($params['Tag']) {
                case 'var':
                    $lastVarName = $params['Data'];
                    break;

                case 'fits':
                    $stacks[$lastVarName][] = array(
                        'Tag' =>
                            $params['Tag'],

                        'Data' =>
                            $this->getIsDataFitType($params['Data']),
                    );
                    break;

                case 'unfits':
                    $stacks[$lastVarName][] = array(
                        'Tag' =>
                            $params['Tag'],

                        'Data' =>
                            $this->getIsDataFitType($params['Data']),
                    );
                    break;

                default:
                    $stacks[$lastVarName][] =
                        $params;
            }
        }


    }

    public function compile()
    {
        if (!$this->mainParameter) {
            throw new Exception\LogicExpressionInvalid(
                $this->mainParameter
            );
        }

        $php = '<?php if (' . $this->compileParameters($this->mainParameter) . ') { ?>';

        if (isset($this->middles['elseif'])) {
            foreach ($this->middles['elseif'] as $elseif) {
                $php .= '<?php } elseif (' . $elseif['Parameter'] . ') { ?>';
                $php .= $elseif['Data'];
            }
        }

        if (isset($this->middles['else'])) {
            $php .= '<?php } else { ?>';

            foreach ($this->middles['else'] as $else) {
                $php .= $else['Data'];
            }
        }

        $php .= '<?php } ?>';

        return $php;
    }
}
