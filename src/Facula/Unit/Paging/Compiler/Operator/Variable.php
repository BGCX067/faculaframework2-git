<?php

/**
 * Variable Tag Compiler
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
use Facula\Unit\Paging\Compiler\Exception\Compiler\Operator as Exception;
use Facula\Unit\Paging\Compiler\Parameters as Parameters;
use Facula\Base\Exception\Factory\Operator as OperatorException;

/**
 * Variable tag compiler
 */
class Variable extends Base implements Implement
{
    /** Wrapped Data in the tags */
    protected $data = '';

    /** Tag parameter types */
    protected $parameters = array(
        'var' => 'variable',
        'type' => 'default',
        'parameters' => 'default',
    );

    /** Tag parameters */
    protected $parameter = null;

    /** Data needed for compile */
    protected $pool = array();

    /** Data of child tags */
    protected $middles = array();

    /** Variable operators */
    protected static $operators = array(
        'default' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\Defaults',

        'date' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\Date',

        'friendlyTime' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\FriendlyTime',

        'bytes' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\Bytes',

        'json' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\Json',

        'jsonData' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\JsonData',

        'urlChar' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\URLCharacter',

        'slashed' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\Slashed',

        'nl' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\NewLine',

        'pure' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\Pure',

        'pureNl' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\PureNewLine',

        'number' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\Numeric',

        'friendlyNumber' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\FriendlyNumber',

        'floatNumber' =>
            'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\FloatNumber',
    );

    protected static $operatorImplement =
        'Facula\Unit\Paging\Compiler\Operator\Variable\Operator\OperatorImplement';

    /**
     * Return the tag registration information
     *
     * @return array Return registration information of this tag compiler
     */
    public static function register()
    {
        return array();
    }

    /**
     * Constructor
     *
     * Do some necessary initialize
     *
     * @param array $pool Data that may needed for tag compile
     * @param array $config The config of main compiler
     *
     * @return void
     */
    public function __construct(array $pool, array $config)
    {
        $this->pool = $pool;
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
        $varName = $varType = $varParam = '';
        $parameters = array();

        switch ($type) {
            case 'Main':
                $parameters = explode('|', trim($param), 3);

                if (isset($parameters[1])) {
                    $varName = array_shift($parameters);
                    $varType = array_shift($parameters);
                    $varParam = array_shift($parameters);
                } else {
                    $varName = $parameters[0];
                }

                $this->parameter = new Parameters(
                    'var="'
                    . $varName
                    . '" type="'
                    . $varType
                    . '" parameters="'
                    . str_replace('\"', '"', $varParam)
                    . '"',
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
        return;
    }

    /**
     * Compile the tag data and return result
     *
     * @return string Compiled result of the tag wrapper
     */
    public function compile()
    {
        $varName = $this->parameter->get('var');
        $varType = $this->parameter->get('type');

        $php = '<?php if (!isset('
            . $varName
            . ')) { '
            . $varName
            . ' = null; } ?>';

        try {
            $className = static::getOperator(
                $varType ? $varType : 'default'
            );

            if (!class_exists($className)) {
                throw new Exception\VariableOperatorClassNotFound(
                    $className,
                    $varType
                );

                return '';
            }

            if (!class_implements($className, static::$operatorImplement)) {
                throw new Exception\VariableOperatorInterfaceInvaild(
                    $className,
                    static::$operatorImplement
                );

                return '';
            }

            return $php . $className::convert(
                $varName,
                explode('|', $this->parameter->get('parameters')),
                $this->pool
            );
        } catch (OperatorException\OperatorNotFound $e) {
            throw new Exception\VariableTypeNotFound($varType, $varName);
        }

        return '';
    }
}
