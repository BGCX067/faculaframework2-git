<?php

/**
 * Template Tag Compiler
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
use Facula\Unit\Paging\Compiler as Compiler;

/**
 * Template tag compiler
 */
class Template implements Implement
{
    /** Wrapped Data in the tags */
    protected $data = '';

    /** Parameter object for main parameter */
    protected $mainParameter = null;

    /** Parameter object for ending parameter */
    protected $endParameter = null;

    /** Tag parameter template */
    protected $parameters = array(
        'name' => 'default',
        'set' => 'default',
        'to' => 'default',
    );

    /** Data needed for compile */
    protected $pool = array();

    /** config needed for compile */
    protected $backSlash = array();

    /** Data of child tags */
    protected $middles = array();

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

        // There tree type of ending

        // Begin: \{
        $this->backSlash['Search'][] =
            $config['Skipper'] . $config['Delimiter']['Begin'];

        $this->backSlash['Replace'][] =
            $config['Delimiter']['Begin'];

        // Pass Ending: \/}
        $this->backSlash['Search'][] =
            $config['Skipper'] . $config['Ender'] . $config['Delimiter']['End'];

        $this->backSlash['Replace'][] =
            $config['Ender'] . $config['Delimiter']['End'];

        // Half Ending: \}
        $this->backSlash['Search'][] =
            $config['Skipper'] . $config['Delimiter']['End'];

        $this->backSlash['Replace'][] =
            $config['Delimiter']['End'];

        // Full Ending: \}
        $this->backSlash['Search'][] =
            $config['Skipper'] . $config['Delimiter']['Begin'] . $config['Ender'];

        $this->backSlash['Replace'][] =
            $config['Delimiter']['Begin'] . $config['Ender'];
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
     * Get the set & to pair
     *
     * @param Parameter $parameter The parameter object which contains parameters
     *
     * @return array The array of paired set / to set
     */
    protected function getSetTo(
        Parameter $parameter
    ) {
        $result = array();
        $sets = $parameter->getAll('set');
        $tos = $parameter->getAll('to');
        $setCount = count($sets);
        $toCount = count($tos);

        if ($toCount != $setCount) {
            throw new Exception\TemplateMissingSetOrTo(
                $setCount,
                $toCount
            );

            return false;
        }

        foreach ($sets as $key => $set) {
            if (!isset($tos[$key])) {
                throw new Exception\TemplateMissingTo(
                    $set
                );
            }

            if (!$set) {
                throw new Exception\TemplateSetIsEmpty(
                    $key + 1
                );
            }

            $result[str_replace(
                $this->backSlash['Search'],
                $this->backSlash['Replace'],
                $set
            )] = str_replace(
                $this->backSlash['Search'],
                $this->backSlash['Replace'],
                $tos[$key]
            );
        }

        return $result;
    }

    /**
     * Compile the tag data and return result
     *
     * @return string Compiled result of the tag wrapper
     */
    public function compile()
    {
        $templateContent = $compiler = '';
        $sets = $this->getSetTo($this->mainParameter);

        $templateName = $this->mainParameter->get('name');

        if (!isset($this->pool['File']['Tpl'][$templateName]['default'])) {
            throw new Exception\TemplateNotFound($templateName);

            return false;
        }

        $templateContent = str_replace(
            array_keys($sets),
            array_values($sets),
            file_get_contents(
                $this->pool['File']['Tpl'][$templateName]['default']
            )
        );

        $compiler = Compiler::compile($this->pool, $templateContent);

        return $compiler->result();
    }
}
