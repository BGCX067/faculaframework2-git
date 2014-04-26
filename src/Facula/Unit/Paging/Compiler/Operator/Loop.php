<?php

/**
 * Loop Tag Compiler
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
use Facula\Unit\Paging\Compiler\OperatorBase as Base;

/**
 * Loop tag compiler
 */
class Loop extends Base implements Implement
{
    /** Wrapped Data in the tags */
    protected $data = '';

    /** Parameter object for main parameter */
    protected $mainParameter = null;

    /** Parameter object for ending parameter */
    protected $endParameter = null;

    /** Tag parameter template */
    protected $parameters = array(
        'var' => 'variable',
        'name' => 'alphaNumber',
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
            'Middles' => array(
                'empty' => false,
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
        $this->middles[$tag][] = $data;
    }

    /**
     * Compile the tag data and return result
     *
     * @return string Compiled result of the tag wrapper
     */
    public function compile()
    {
        $empty = '';

        $varName = $this->mainParameter->get('var');
        $varKeyName = $this->mainParameter->get('name');

        try {
            $this->setMutex($varKeyName);
        } catch (Exception\MutexExisted $e) {
            throw new Exception\NameAlreadyExisted(
                $e->getParameter(0)
            );
        }

        $php = '<?php if (isset(' . $varName . ') && is_array(' . $varName . ')) { ';
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
