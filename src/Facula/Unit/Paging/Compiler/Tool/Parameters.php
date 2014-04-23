<?php

/**
 * Parameter Parser for Page Compiler
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

namespace Facula\Base\Tool\Paging\Compiler;

/**
 * Parse the compiler's tag, get tag properties.
 */
class Parameters
{
    /** The tag properties in string */
    protected $paramStr = '';

    /** Lengths of properties string */
    protected $paramLen = 0;

    /**
     * Parameters that will be read
     *
     * In format:
     *
     *    array(
     *        Key => DataType,
     *        Key2 => DataType,
     *    );
     */
    protected $paramTpl = array();

    /** Result properties */
    protected $params = array();

    /** Assignment Symbol (=) */
    protected static $valueAssignSymbol = '=';

    /** Space symbols can be use before and after a valueAssignSymbol */
    protected static $allowedSpaceSymbols = array(
        "\r" => true,
        "\n" => true,
        ' ' => true,
        "\t" => true,
    );

    /** Start symbol for indicate the begin of property value */
    protected static $valueStartSymbol = '"';

    /** End symbol for indicate the end of property value */
    protected static $valueEndSymbol = '"';

    /** Escape symbol for escaping the End Symbol */
    protected static $valueSkipSymbol = '\\';

    /**
     * Constructor of parameter parser
     *
     * @param string $parameters Parameters in string
     * @param array $parameterTemplate The tag processor
     *
     * @return void
     */
    public function __construct($parameters, array $parameterTemplate)
    {
        if (!is_string($parameters)) {
            return;
        }

        $this->paramStr = $parameters;
        $this->paramLen = strlen($parameters);
        $this->paramTpl = $parameterTemplate;

        $this->params = $this->parseParameter();
    }

    /**
     * Check if the property has set
     *
     * @param string $key Name of the property
     * @param integer $idx Index of the property, 0 for the first one
     *
     * @return bool Return true when the property has set, false otherwise
     */
    public function has($key, $idx = 0)
    {
        if (isset($this->params[$key][$idx])) {
            return true;
        }

        return false;
    }

    /**
     * Get a property using property name and index
     *
     * @param string $key Name of the property
     * @param integer $idx Index of the property
     *
     * @return mixed Return the property value when succeed, null otherwise
     */
    public function get($key, $idx = 0)
    {
        if (isset($this->params[$key][$idx])) {
            return $this->params[$key][$idx];
        }

        return null;
    }

    /**
     * Get a all properties with property name
     *
     * @param string $key Name of the property
     *
     * @return mixed Return the property values when succeed, or an empty array
     */
    public function getAll($key)
    {
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }

        return array();
    }

    /**
     * Parse parameter according to the parameter string
     *
     * @return array Return parsed tags and property value as array
     */
    protected function parseParameter()
    {
        $currenPos = 0;
        $tag = array();
        $paramValue = $paramTempValue = '';

        foreach ($this->getTagNamePositions() as $postion) {
            if ($currenPos > $postion['TagNameStartPos']) {
                continue;
            }

            $paramTempValue = $this->getParamAfterPos(
                $postion['TagNameEndPos'],
                $currenPos
            );

            switch ($this->paramTpl[$postion['TagName']]) {
                case 'bool':
                    $paramValue = $paramTempValue ? true : false;
                    break;

                case 'integer':
                    $paramValue = (int)$paramTempValue;
                    break;
                case 'float':
                    $paramValue = (float)$paramTempValue;
                    break;

                default:
                    $paramValue = $paramTempValue;
                    break;
            }

            $tag[$postion['TagName']][] = $paramValue;

        }

        return $tag;
    }

    /**
     * Get property value after the parameter name position
     *
     * @param integer $pos Starting position for search
     * @param integer &$reachedPos Last position we reached
     *
     * @return mixed Return the property value on success, false otherwise
     */
    protected function getParamAfterPos($pos, &$reachedPos = 0)
    {
        $reachedPos = $pos;
        $currentChar = '';
        $maxPos = $this->paramLen - 1;
        $currentLooking = 'AssignSymbol';

        $paramValStart = $paramValEnd = 0;

        while ($reachedPos <= $maxPos) {
            $currentChar = $this->paramStr[$reachedPos];

            switch ($currentChar) {
                case static::$valueAssignSymbol[0]:
                    // If we pick up a AssignSymbol
                    // and currently looking for AssignSymbol
                    // Change pickup status to StartSymbol
                    if ($currentLooking == 'AssignSymbol') {
                        $currentLooking = 'StartSymbol';
                    }
                    break;

                case static::$valueStartSymbol[0]:
                    // If we pick up a StartSymbol
                    // and we currently looking for StartSymbol
                    // Save the position as start pos, and
                    // Change pickup status to EndSymbol
                    if ($currentLooking == 'StartSymbol') {
                        $currentLooking = 'EndSymbol';

                        $paramValStart = $reachedPos + 1;
                    } elseif (static::$valueStartSymbol[0] == static::$valueEndSymbol[0]
                        && $currentLooking == 'EndSymbol'
                        && $this->paramStr[$reachedPos - 1] != static::$valueSkipSymbol[0]) {
                        // Or, if valueStartSymbol is the same of valueEndSymbol
                        // We still need to end it.
                        $paramValEnd = $reachedPos;
                        $currentLooking = 'Nothing';

                        break 2;
                    }
                    break;

                case static::$valueEndSymbol[0]:
                    if ($currentLooking == 'EndSymbol'
                    && $this->paramStr[$reachedPos - 1] != static::$valueSkipSymbol[0]) {
                        $paramValEnd = $reachedPos;

                        break 2;
                    }
                    break;

                default:
                    switch($currentLooking) {
                        case 'AssignSymbol':
                            if (!isset(
                                static::$allowedSpaceSymbols[$currentChar]
                            )) {
                                return false;
                            }
                            break;

                        case 'StartSymbol':
                            if (!isset(
                                static::$allowedSpaceSymbols[$currentChar]
                            )) {
                                return false;
                            }
                            break;

                        default:
                            break;
                    }
                    break;
            }

            $reachedPos++;
        }

        if ($paramValEnd > 0
        && $paramValEnd >= $paramValStart) {
            // Escape it back
            return str_replace(
                static::$valueSkipSymbol[0] . static::$valueEndSymbol[0],
                static::$valueEndSymbol[0],
                substr(
                    $this->paramStr,
                    $paramValStart,
                    $paramValEnd - $paramValStart
                )
            );
        }

        return false;
    }

    /**
     * Scan all tags for getting their position
     *
     * @return array Return an array contains all tags position information
     */
    protected function getTagNamePositions()
    {
        $lastTagStartPos = $lastStartFoundPos = 0;
        $tags = array();

        // Scan all start point of tags
        foreach ($this->paramTpl as $paramName => $properties) {

            // Loop to get all params out
            while (($lastStartFoundPos = strpos(
                $this->paramStr,
                $paramName,
                $lastTagStartPos
            )) !== false) {
                // Move it little bit forward
                $lastTagStartPos = $lastStartFoundPos + 1;

                $tags[$lastStartFoundPos] = array(
                    'TagName' => $paramName,
                    'TagNameStartPos' => $lastStartFoundPos,
                    'TagNameEndPos' => $lastStartFoundPos + strlen($paramName),
                );

                if (($this->paramLen - 1) <= $lastTagStartPos) {
                    break;
                }
            }

            $lastTagStartPos = 0;
        }

        ksort($tags);

        return $tags;
    }
}
