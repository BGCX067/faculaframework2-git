<?php

/**
 * Language Tag Compiler
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
use Facula\Unit\Paging\Compiler\Exception\Compiler\Operator as Exception;
use Facula\Unit\Paging\Compiler as Compiler;

/**
 * Language tag compiler
 */
class Language implements Implement
{
    /** Wrapped Data in the tags */
    protected $data = '';

    /** The language code */
    protected $langCode = '';

    /** Data needed for compile */
    protected $pool = array();

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
        switch ($type) {
            case 'Main':
                $this->langCode = trim($param);
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
        return;
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
        if (!isset($this->pool['LanguageMap'][$this->langCode])) {
            throw new Exception\LanguageCodeNotFound(
                $this->langCode
            );
        }

        return Compiler::compile(
            $this->pool,
            $this->pool['LanguageMap'][$this->langCode]
        )->result();
    }
}
