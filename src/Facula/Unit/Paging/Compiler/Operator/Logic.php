<?php

/**
 * Logic Tag Compiler
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

namespace Facula\Unit\Paging\Compiler\Operator;

use Facula\Unit\Paging\Compiler\OperatorBase as Base;
use Facula\Unit\Paging\Compiler\OperatorImplement as Implement;
use Facula\Unit\Paging\Compiler\DataContainer as DataContainer;
use Facula\Unit\Paging\Compiler\Parameters as Parameter;
use Facula\Unit\Paging\Compiler\Exception\Compiler\Operator as Exception;

/**
 * Logic tag compiler
 */
class Logic extends Base implements Implement
{
    /** Data container for data exchange */
    protected $dataContainer = null;

    /** Wrapped Data in the tags */
    protected $data = '';

    /** Parameter object for main parameter */
    protected $mainParameter = '';

    /** Tag parameter template */
    protected $parameters = array(
        'var' => 'variable', // var="$variable"

        // variable compare to another variable
        'equals' => 'variable', // var="$variable" equal="$someothervar"
        'unequals' => 'variable', // var="$variable" unequal="$someothervar"

        // variable compare to another value
        'is' => 'default', // var="$variable" is="someothervar"
        'not' => 'default', // var="$variable" not="someothervar"

        'larger' => 'number', // var="$variable" larger="1"
        'smaller' => 'number', // var="$variable" smaller="2"

        // variable compare to another token (true | false | null)
        'fits' => 'default', // var="$variable" fits="true"
        'unfits' => 'default', // var="$variable" unfits="false"

        'unempty' => 'variable',
        'empty' => 'variable',
        'true' => 'variable',
        'false' => 'variable',
    );

    /** Data of child tags */
    protected $middles = array();

    /**
     * Return the tag registration information
     *
     * @return array Return registration information of this tag compiler
     */
    public static function register()
    {
        return array(
            'Wrapped' => true,
            'Middles' => array(
                'else' => false,
                'elseif' => true,
            ),
        );
    }

    /**
     * Constructor
     *
     * Do some necessary initialize
     *
     * @param array $pool Data that may needed for tag compile
     * @param array $config The config of main compiler
     * @param DataContainer $dataContainer Data container for compiling data exchange
     *
     * @return void
     */
    public function __construct(array $pool, array $config, DataContainer $dataContainer)
    {
        $this->pool = $pool;
        $this->dataContainer = $dataContainer;
    }

    /**
     * Set parameter info of current tag to tag compiler
     *
     * @param string $type Tag type
     * @param string $param The parameter that will be set
     *
     * @return void
     */
    public function setParameter($type, $param)
    {
        switch ($type) {
            case 'Main':
                $this->mainParameter = new Parameter(
                    $param,
                    $this->parameters
                );
                break;

            default:
                break;
        }
    }

    /**
     * Set wrapped data of current tag to tag compiler
     *
     * @param string $data Data
     *
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Set child code data of current tag to tag compiler
     *
     * @param string $tag The tag name
     * @param string $param Tag parameter
     * @param string $data Wrapped data of current child tag
     *
     * @return void
     */
    public function setMiddle($tag, $param, $data)
    {
        $this->middles[$tag][] = array(
            'Parameter' => new Parameter(
                $param,
                $this->parameters
            ),
            'Data' => $data,
        );
    }

    /**
     * Get actual value according to type of fits / unfits parameter
     *
     * @param string $type The fitting type
     *
     * @return string Actual value that will be used in IF expression
     */
    protected function getIsDataFitType($type)
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
                if (is_numeric($type)) {
                    $result = $type;
                } else {
                    throw new Exception\LogicUnknownDataFitType($type);
                }
                break;
        }

        return $result;
    }

    /**
     * Compile parameter to IF expression
     *
     * @param Facula\Unit\Paging\Compiler\Parameters $parameters The parameter will be used to compile
     *
     * @return array Result of compiled parameters including isset check syntaxes.
     */
    protected function compileParameters(
        Parameter $parameters
    ) {
        $phpIsset = $lastVarName = '';
        $stacks = $varNames = $logicStack = array();
        $params = $parameters->fetch();

        if (isset($params[0])) {
            if ($params[0]['Tag'] != 'var'
            && $params[0]['Tag'] != 'empty'
            && $params[0]['Tag'] != 'unempty'
            && $params[0]['Tag'] != 'true'
            && $params[0]['Tag'] != 'false') {
                throw new Exception\LogicFirstParamaterMustBeVar(implode(', ', array(
                    'empty', 'unempty', 'true', 'false'
                )));
            }

            foreach ($parameters->fetch() as $param) {
                switch ($param['Tag']) {
                    case 'unempty':
                        // just remember the tag, discards data
                    case 'empty':
                        // just remember the tag, discards data
                    case 'true':
                        // just remember the tag, discards data
                    case 'false':
                        $stacks[] = array(
                            'Tag' =>
                                $param['Tag'],

                            'Var' =>
                                $param['Data'],
                        );
                        $varNames[] = $param['Data'];
                        break;


                    case 'var':
                        $varNames[] = $param['Data'];
                        $lastVarName = $param['Data'];
                        break;

                    case 'fits':
                        // fits and unfits use same operation
                    case 'unfits':
                        $stacks[] = array(
                            'Tag' =>
                                $param['Tag'],

                            'Var' =>
                                $lastVarName,

                            'Data' =>
                                $this->getIsDataFitType($param['Data']),
                        );
                        break;

                    case 'equals':
                        // equals and unequals use same operation
                    case 'unequals':
                        // Save the variable name to varNames
                        $varNames[] = $param['Data'];

                        // And save the variable name as data, like defaults did

                    default:
                        $stacks[] = array(
                            'Tag' =>
                                $param['Tag'],

                            'Var' =>
                                $lastVarName,

                            'Data' =>
                                $param['Data'],
                        );
                            $param;
                        break;
                }
            }

            // I'll do this with violence
            // Syntax to check if variable has set.
            foreach ($varNames as $varName) {
                // Check overwrite for $varName
                $varPureName = $this->getPureVarName($varName);

                if ($this->dataContainer->checkMutex('Overwrite!' . $varPureName)) {
                    throw new Exception\LogicOverwriteRisk(
                        $varPureName
                    );

                    return '';
                }

                $phpIsset .= 'if (!isset(' . $varName . ')) { ';
                $phpIsset .= $varName . ' = null; ';
                $phpIsset .= '} ';
            }

            // The IF Logic syntax
            foreach ($stacks as $stack) {
                // Check overwrite for $stack['Var']
                $varPureName = $this->getPureVarName($stack['Var']);

                if ($this->dataContainer->checkMutex('Overwrite!' . $varPureName)) {
                    throw new Exception\LogicOverwriteRisk(
                        $varPureName
                    );

                    return '';
                }

                switch ($stack['Tag']) {
                    case 'unempty':
                        $logicStack[] =
                            '!empty(' . $stack['Var'] . ')';
                        break;

                    case 'empty':
                        $logicStack[] =
                            'empty(' . $stack['Var'] . ')';
                        break;

                    case 'true':
                        $logicStack[] =
                            '(' . $stack['Var'] . ')';
                        break;

                    case 'false':
                        $logicStack[] =
                            '(!' . $stack['Var'] . ')';
                        break;


                    case 'equals':
                        // $stack['Data'] will be the variable
                        $dataVarPureName = $this->getPureVarName($stack['Data']);

                        if ($this->dataContainer->checkMutex('Overwrite!' . $dataVarPureName)) {
                            throw new Exception\LogicOverwriteRisk(
                                $dataVarPureName
                            );

                            return '';
                        }
                        $logicStack[] =
                            '(' . $stack['Var'] . ' == ' . $stack['Data'] . ')';
                        break;

                    case 'unequals':
                        // $stack['Data'] will be the variable too
                        $dataVarPureName = $this->getPureVarName($stack['Data']);

                        if ($this->dataContainer->checkMutex('Overwrite!' . $dataVarPureName)) {
                            throw new Exception\LogicOverwriteRisk(
                                $dataVarPureName
                            );

                            return '';
                        }

                        $logicStack[] =
                            '(' . $stack['Var'] . ' != ' . $stack['Data'] . ')';
                        break;

                    case 'is':
                        $logicStack[] =
                            '(' . $stack['Var']
                            . ' == "'
                            . str_replace('"', '\"', $stack['Data'])
                            . '")';
                        break;

                    case 'not':
                        $logicStack[] =
                            '(' . $stack['Var']
                            . ' != "'
                            . str_replace('"', '\"', $stack['Data'])
                            . '")';
                        break;

                    case 'fits':
                        $logicStack[] =
                            '(' . $stack['Var'] . ' === ' . $stack['Data'] . ')';
                        break;

                    case 'unfits':
                        $logicStack[] =
                            '(' . $stack['Var'] . ' !== ' . $stack['Data'] . ')';
                        break;

                    default:
                        break;
                }
            }
        }

        return array(
            'Check' => $phpIsset,
            'Logic' => trim(implode(' && ', $logicStack)),
        );
    }

    /**
     * Compile the tag data and return result
     *
     * @return string Compiled result of the tag wrapper
     */
    public function compile()
    {
        $php = $phpChecker = '';
        $middleParameters = array();

        $mainParameter = $this->compileParameters($this->mainParameter);

        if ($mainParameter['Logic']) {
            $phpChecker .= '<?php ' . $mainParameter['Check'];
            $php .= '<?php if (' . $mainParameter['Logic'] . ') { ?>';
            $php .= $this->data;

            if (isset($this->middles['elseif'])) {
                foreach ($this->middles['elseif'] as $elseif) {
                    $middleParameters =
                        $this->compileParameters($elseif['Parameter']);

                    $phpChecker .=
                        $middleParameters['Check'];

                    if (empty($middleParameters['Logic'])) {
                        throw new Exception\LogicConditionEmpty();

                        return false; // For test
                    }

                    $php .= '<?php } elseif (' . $middleParameters['Logic'] . ') { ?>';
                    $php .= $elseif['Data'];
                }
            }

            if (isset($this->middles['else'])) {
                $php .= '<?php } else { ?>';

                foreach ($this->middles['else'] as $else) {
                    $php .= $else['Data'];
                }
            }

            $phpChecker .= '?>';
            $php .= '<?php } ?>';
        }

        return $phpChecker . $php;
    }
}
