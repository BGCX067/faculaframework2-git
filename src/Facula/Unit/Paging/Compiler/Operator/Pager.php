<?php

/**
 * Pager Tag Compiler
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
use Facula\Unit\Paging\Compiler\Exception\Compiler\Operator as Exception;
use Facula\Unit\Paging\Compiler\Parameters as Parameter;

/**
 * Pager tag compiler
 */
class Pager extends Base implements Implement
{
    /** Data container for data exchange */
    protected $dataContainer = null;

    /** Wrapped Data in the tags */
    protected $data = '';

    /** Parameter object for main parameter */
    protected $mainParameter = null;

    /** Tag parameter template */
    protected $parameters = array(
        'name' => 'default',
        'classname' => 'default',
        'current' => 'variable',
        'max' => 'variable',
        'total' => 'variable',
        'format' => 'default',
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
        return array();
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
        $this->middles[$tag][] = $data;
    }

    /**
     * Compile the tag data and return result
     *
     * @return string Compiled result of the tag wrapper
     */
    public function compile()
    {
        $phpCode = '';

        $formatMatched = array();
        $formatVariables = array('Search' => array(), 'Replace' => array());

        $name = $this->mainParameter->get('name');
        $className = $this->mainParameter->get('classname');
        $currentPage = $this->mainParameter->get('current');
        $maxDisplay = $this->mainParameter->get('max');
        $totalPage = $this->mainParameter->get('total');
        $linkFormat = $this->mainParameter->get('format');

        if (!$name) {
            throw new Exception\PagerParameterMissed('name');

            return '';
        }

        if (!$className) {
            throw new Exception\PagerParameterMissed('classname');

            return '';
        }

        if (!$currentPage) {
            throw new Exception\PagerParameterMissed('current');

            return '';
        }

        if (!$maxDisplay) {
            throw new Exception\PagerParameterMissed('max');

            return '';
        }

        if (!$totalPage) {
            throw new Exception\PagerParameterMissed('total');

            return '';
        }

        if (!$linkFormat) {
            throw new Exception\PagerParameterMissed('format');

            return '';
        }

        $currentVarPureName = $this->getPureVarName($currentPage);
        $maxVarPureName = $this->getPureVarName($maxDisplay);
        $totalVarPureName = $this->getPureVarName($totalPage);

        if ($this->dataContainer->checkMutex('Overwrite!' . $currentVarPureName)) {
            throw new Exception\PagerOverwriteRisk(
                $currentVarPureName,
                'current'
            );

            return '';
        } elseif ($this->dataContainer->checkMutex('Overwrite!' . $maxVarPureName)) {
            throw new Exception\PagerOverwriteRisk(
                $maxVarPureName,
                'max'
            );

            return '';
        } elseif ($this->dataContainer->checkMutex('Overwrite!' . $totalVarPureName)) {
            throw new Exception\PagerOverwriteRisk(
                $totalVarPureName,
                'total'
            );

            return '';
        }

        $phpCode .= '<?php ';
        $phpCode .= 'if (!isset(' . $currentPage . ')) { ' . $currentPage . ' = null; } ';
        $phpCode .= 'if (!isset(' . $maxDisplay . ')) { ' . $maxDisplay . ' = null; } ';
        $phpCode .= 'if (!isset(' . $totalPage . ')) { ' . $totalPage . ' = null; } ';
        $phpCode .= '?>';

        $name = htmlspecialchars($name, ENT_QUOTES, $this->pool['Charset']);
        $className = htmlspecialchars($className, ENT_QUOTES, $this->pool['Charset']);

        // Find all variables in the format string
        if (preg_match_all('/\{(\$[A-Za-z0-9\_\'\"\[\]]+)\}/sU', $linkFormat, $formatMatched)) {
            // Prepare for the replacement
            foreach ($formatMatched[0] as $key => $value) {
                $formatVariables['Search'][] = urlencode($value);
                $formatVariables['Replace'][] = '\' . ' . $formatMatched[1][$key] . ' . \'';
            }
        }

        // Urlencode the format but replace some string back for url params
        $linkFormat = str_replace(
            array('%3A', '%2F', '%3F', '%3D', '%26', '%25PAGE%25'),
            array(':', '/', '?', '=', '&', '%PAGE%'),
            urlencode($linkFormat)
        );

        // Replace variables string to variables
        $linkFormat = str_replace(
            $formatVariables['Search'],
            $formatVariables['Replace'],
            $linkFormat
        );

        $phpCode .= '<?php if (' . $totalPage. ' > 1) { echo(\'<ul id="'
                . $name . '" class="' . $className . '">\'); if ('
                . $totalPage . ' > 0 && ' . $currentPage . ' <= ' . $totalPage . ') { if ('
                . $currentPage . ' > 1) echo(\'<li><a href="'
                . str_replace('%PAGE%', '1', $linkFormat)
                . '">&laquo;</a></li><li><a href="\' . str_replace(\'%PAGE%\', ('
                . $currentPage . ' - 1), \'' . $linkFormat
                . '\') . \'">&lsaquo;</a></li>\'); $loop = (int)(' . $maxDisplay
                . ' / 2); if (' . $currentPage . ' - $loop > 0) { for ($i = '
                . $currentPage . ' - $loop; $i <= ' . $totalPage . ' && $i <= '
                . $currentPage . ' + $loop; $i++) { if ($i == ' . $currentPage
                . ') { echo(\'<li class="this"><a href="\' . str_replace(\'%PAGE%\', $i, \''
                . $linkFormat . '\'). \'">\' . $i . \'</a></li>\'); } '
                . ' else { echo(\'<li><a href="\' . str_replace(\'%PAGE%\', $i, \''
                . $linkFormat . '\') . \'">\' . $i . \'</a></li>\'); } } } else '
                . '{ for ($i = 1; $i <= ' . $totalPage . ' && $i <= ' . $maxDisplay
                . '; $i++) { if ($i == ' . $currentPage
                . ') { echo(\'<li class="this"><a href="\' . str_replace(\'%PAGE%\', $i, \''
                . $linkFormat . '\'). \'">\' . $i . \'</a></li>\'); } else'
                . ' { echo(\'<li><a href="\' . str_replace(\'%PAGE%\', $i, \''
                . $linkFormat . '\') . \'">\' . $i . \'</a></li>\'); } } } unset($loop); if ('
                . $totalPage . ' > ' . $currentPage
                . ') echo(\'<li><a href="\' . str_replace(\'%PAGE%\', ('
                . $currentPage . ' + 1), \'' . $linkFormat
                . '\') . \'">&rsaquo;</a></li><li><a href="\' . str_replace(\'%PAGE%\', ('
                . $totalPage . '), \'' . $linkFormat
                . '\') . \'">&raquo;</a></li>\'); } echo(\'</ul>\'); } ?>';

        $phpCode = str_replace(array("\r", "\r\n", "\t",'  '), '', $phpCode);

        return $phpCode;
    }
}
