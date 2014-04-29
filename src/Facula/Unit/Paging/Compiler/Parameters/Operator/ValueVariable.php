<?php

/**
 * Variable parameter parser
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

namespace Facula\Unit\Paging\Compiler\Parameters\Operator;

use Facula\Unit\Paging\Compiler\Parameters\OperatorImplement as Implement;
use Facula\Unit\Paging\Compiler\Exception\Parameters as Exception;

/**
 * Default Parameters
 */
class ValueVariable implements Implement
{
    const MARK_DELIMITER_START = 1;
    const MARK_DELIMITER_END = 2;
    const MARK_SPLITER = 3;

    protected $var = '';

    /**
     * Variable scan delimiters
     */
    protected static $escape = '\\';
    protected static $delimiterStart = '(';
    protected static $delimiterEnd = ')';
    protected static $spliter = '.';

    /**
     * Constructor
     *
     * @param mixed $var The var to be converted in to parameter format
     *
     * @return void
     */
    public function __construct($string)
    {
        $this->var = $this->parseMarks($string);
    }

    /**
     * Parse variable expression
     *
     * @param string $string The variable to be parsed
     *
     * @return string The parsed result of a PHP variable name.
     */
    protected function parseMarks($string)
    {
        $varName = $varNameBuf = '';
        $thereSpilter = $skipSpliters = $newRangeEntered = false;
        $var = array();
        $delimiterLevel = 0;
        $targetString = $string . '.';

        $strLength = strlen($targetString);

        for ($seeker = 0; $seeker < $strLength; $seeker++) {
            switch ($targetString[$seeker]) {
                case static::$delimiterStart:
                    if (($seeker < 1 || $targetString[$seeker - 1] != static::$escape)
                    && $delimiterLevel++ <= 0) {
                        $skipSpliters = true;
                        $newRangeEntered = true;
                    } else {
                        $varNameBuf .= $targetString[$seeker];
                    }
                    break;

                case static::$delimiterEnd:
                    if (($seeker < 1 || $targetString[$seeker - 1] != static::$escape)
                    && --$delimiterLevel <= 0) {
                        $skipSpliters = false;
                    } else {
                        $varNameBuf .= $targetString[$seeker];
                    }
                    break;

                case static::$spliter:
                    if (($seeker < 1 || $targetString[$seeker - 1] != static::$escape)
                    && !$skipSpliters) {

                        if ($newRangeEntered) {
                            $newRangeEntered = false;

                            if ($varNameBuf[0] != static::$escape) {
                                if (!$varNameBuf) {
                                    throw new Exception\VariableDelimiterRangeEmpty(
                                        $string
                                    );

                                    continue 2;
                                }

                                $var[] = $this->parseMarks($varNameBuf);
                            } else {
                                // Normal
                                $var[] = $varNameBuf;
                            }

                        } else {
                            $var[] = $varNameBuf;
                        }

                        $varNameBuf = '';
                    } else {
                        $varNameBuf .= $targetString[$seeker];
                    }
                    break;

                default:
                    $varNameBuf .= $targetString[$seeker];
                    break;
            }
        }

        $var[] = $varNameBuf;

        $varName = array_shift($var);

        if (!preg_match('/^([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/', $varName)) {
            throw new Exception\InvalidVariableName(
                $string
            );

            return '';
        }

        if ($delimiterLevel != 0) {
            throw new Exception\VariableDelimiterNotClosed(
                $string
            );

            return '';
        }

        foreach ($var as $name) {
            if (isset($name[0])) {
                if ($name[0] == '$') {
                    $varName .= '[' . str_replace('\'', '\\\'', $name) . ']';
                } else {
                    $varName .= '[\'' . str_replace(array('\\', '\''), array('', '\\\''), $name) . '\']';
                }
            }
        }

        return '$' . $varName;
    }

    /**
     * Get convert result
     *
     * @return mixed Return the result of the convert
     */
    public function result()
    {
        return $this->var;
    }
}
