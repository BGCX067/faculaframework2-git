<?php

/**
 * Page Compiler Container
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

namespace Facula\Unit\Paging;

use Facula\Base\Factory\Operator as Base;
use Facula\Base\Implement\Core\Template\Compiler as Implement;
use Facula\Base\Exception\Factory\Operator as OperatorException;
use Facula\Unit\Paging\Compiler\OperatorImplement as OperatorImplement;
use Facula\Unit\Paging\Compiler\Exception\Compiler as Exception;
use Facula\Unit\Paging\Compiler\Exception\Parser as ParserException;
use Facula\Unit\Paging\Compiler\Tool\Parser as Parser;
use Facula\Unit\Paging\Compiler\Error\Compiler as Error;

/**
 * Provide a space to compile Facula pages
 */
class Compiler extends Base implements Implement
{
    /** Array of config */
    protected static $config = array(
        'Delimiter' => array(
            'Begin' => '{',
            'End' => '}'
        ),
        'Ender' => '/',
        'Skipper' => '\\',
        'Blank' => ' ',
    );

    /** Tag handlers */
    protected static $operators = array(
        /*

        'inject' => 'Facula\Unit\Paging\Compiler\Operator\Inject',
        'language' => 'Facula\Unit\Paging\Compiler\Operator\Language',
        'variable' => 'Facula\Unit\Paging\Compiler\Operator\Variable',
        'pager' => 'Facula\Unit\Paging\Compiler\Operator\Pager',
        'loop' => 'Facula\Unit\Paging\Compiler\Operator\Loop',
        */

        'template' => 'Facula\Unit\Paging\Compiler\Operator\Template',
        'loop' => 'Facula\Unit\Paging\Compiler\Operator\Loop',
        'logic' => 'Facula\Unit\Paging\Compiler\Operator\Logic',


        /*
        'case' => 'Facula\Unit\Paging\Compiler\Operator\Case',
        */
    );

    /** Interface that needs to be implemented by tag handlers */
    protected static $operatorsInterface =
        'Facula\Unit\Paging\Compiler\OperatorImplement';

    protected static $inited = false;

    /** Source to be parsed and compiled */
    protected $sourceContent = '';

    /** The length of source content */
    protected $sourceContentLen = 0;

    /** Line map for position to line & column convert */
    protected $sourceContentLineMap = array();

    /** Lines of source content */
    protected $sourceContentLines = 0;

    /** Data that needed for compile */
    protected $sourcePool = array();

    /**
     * Compile the specified string
     *
     * @param array $pool The data that needed for compile
     * @param string $sourceContent The string that will be parsed
     *
     * @return object Return a new instance of compiler
     */
    public static function compile(array &$pool, &$sourceContent)
    {
        static::init();

        return new static(
            $pool,
            $sourceContent
        );
    }

    /**
     * Static self initer
     *
     * Execute Parser init, tag register to make compiler ready to compile
     *
     * @return bool Return true when succeed, or false otherwise.
     */
    protected static function init()
    {
        $class = '';
        $registerData = array();

        if (static::$inited) {
            return true;
        }

        Parser::config(array(
            'DelimiterStart' => static::$config['Delimiter']['Begin'],
            'DelimiterEnd' => static::$config['Delimiter']['End'],
            'TagEnderSymbol' => static::$config['Ender'],
            'TagBlankSymbol' => static::$config['Blank'],
            'TagSkipperSymbol' => static::$config['Skipper'],
        ));

        // Do not directly use array to register, use more standardized getOperator instead
        foreach (static::$operators as $operator => $operatorClass) {
            try {
                $class = static::getOperator($operator);
            } catch (OperatorException\OperatorNotFound $e) {
                new Error(
                    'OPERATOR_NOT_FOUND',
                    array(
                        $operator
                    ),
                    'ERROR'
                );
            }

            if (!class_exists($class)) {
                throw new Exception\OperatorClassNotFound(
                    $operator,
                    $class
                );

                return false;
            }

            if (!class_implements(
                $class,
                static::$operatorsInterface
            )) {
                throw new Exception\OperatorInterfaceInvalid(
                    $operator,
                    $class,
                    static::$operatorsInterface
                );

                return false;
            }

            $registerData = $class::register();

            if (!isset($registerData['Tag'][0])) {
                throw new Exception\TagNameInvalid($operator);

                return false;
            }

            if (isset($registerData['Middles']) && is_array($registerData['Middles'])) {
                Parser::registerTag($registerData['Tag'], false);

                foreach ($registerData['Middles'] as $midTag => $hasParameter) {
                    Parser::registerMiddleTag($registerData['Tag'], $midTag, $hasParameter);
                }
            } else {
                Parser::registerTag($registerData['Tag'], true);
            }
        }

        return true;
    }

    /**
     * Constructor of compiler instance
     *
     * Execute Parser init, tag register to make compiler ready to compile
     *
     * @return bool Return true when succeed, or false otherwise.
     */
    protected function __construct(&$pool, &$sourceContent)
    {
        $searchContent = 0;

        $this->sourceContent = $sourceContent;
        $this->sourceContentLen = strlen($sourceContent);
        $this->sourceContentLines = count($this->sourceContentLineMap);

        $searchContent = $sourceContent . "\n";

        while (($this->sourceContentLines = strpos(
            $searchContent,
            "\n",
            $this->sourceContentLines
        )) !== false) {
            $this->sourceContentLineMap[] = $this->sourceContentLines;

            $this->sourceContentLines++;
        }

        $this->sourcePool = $pool;
    }

    /**
     * Convert soruceContent position to line and column
     *
     * @param integer $pos The position in soruceContent
     *
     * @return array Return an array that contains Line and Column info in array(Line => 0, Column => 0)
     */
    protected function getLineByPosition($pos)
    {
        $result = array(
            'Line' => 0,
            'Column' => 0,
        );

        if ($pos > $this->sourceContentLen) {
            $result['Line'] = $this->sourceContentLines;
            $result['Column'] = $this->sourceContentLen - 1;
        }

        foreach ($this->sourceContentLineMap as $lineIndex => $position) {
            if ($position >= $pos) {
                $result['Line'] = $lineIndex + 1;

                if ($lineIndex > 0) {
                    $result['Column'] = strlen(
                        substr(
                            $this->sourceContent,
                            $this->sourceContentLineMap[$lineIndex - 1] + 1,
                            ($position - ($this->sourceContentLineMap[$lineIndex - 1] + 1))
                            - ($position - $pos)
                        )
                    ) + 1;
                } else {
                    $result['Column'] = $pos + 1;
                }

                break;
            }
        }

        return $result;
    }

    /**
     * Parse the sourceContent
     *
     * @return bool Return true when succeed, false otherwise.
     */
    protected function parse()
    {
        $errorLine = array();

        try {
            $parser = new Parser($this->sourceContent);

            foreach ($parser->parse() as $tag) {

            }
        } catch (ParserException\MaxNestLevelReached $e) {
            $errorLine = $this->getLineByPosition(
                $e->getParameter(1)
            );

            throw new Exception\MaxNestLevelReached(
                $e->getParameter(0),
                $errorLine['Line'],
                $errorLine['Column']
            );
        } catch (ParserException\UnexpectedClosingTag $e) {
            $errorLine = $this->getLineByPosition(
                $e->getParameter(1)
            );

            throw new Exception\UnexpectedClosingTag(
                $e->getParameter(0),
                $errorLine['Line'],
                $errorLine['Column']
            );
        } catch (ParserException\UnexpectedEndOfAClosingTag $e) {
            $errorLine = $this->getLineByPosition(
                $e->getParameter(1)
            );

            throw new Exception\UnexpectedEndOfAClosingTag(
                $e->getParameter(0),
                $errorLine['Line'],
                $errorLine['Column']
            );
        } catch (ParserException\UnexpectedEndOfAnMiddleTag $e) {
            $errorLine = $this->getLineByPosition(
                $e->getParameter(1)
            );

            throw new Exception\UnexpectedEndOfAnMiddleTag(
                $e->getParameter(0),
                $errorLine['Line'],
                $errorLine['Column']
            );
        } catch (ParserException\UnexpectedEndOfAnOpeningTag $e) {
            $errorLine = $this->getLineByPosition(
                $e->getParameter(1)
            );

            throw new Exception\UnexpectedEndOfAnOpeningTag(
                $e->getParameter(0),
                $errorLine['Line'],
                $errorLine['Column']
            );
        } catch (ParserException\UnexpectedMiddleTag $e) {
            $errorLine = $this->getLineByPosition(
                $e->getParameter(1)
            );

            throw new Exception\UnexpectedMiddleTag(
                $e->getParameter(0),
                $errorLine['Line'],
                $errorLine['Column']
            );
        } catch (ParserException\UnexpectedOpeningTag $e) {
            $errorLine = $this->getLineByPosition(
                $e->getParameter(1)
            );

            throw new Exception\UnexpectedOpeningTag(
                $e->getParameter(0),
                $errorLine['Line'],
                $errorLine['Column']
            );
        } catch (ParserException\TagNeedToBeClosed $e) {
            $errorLine = $this->getLineByPosition(
                $e->getParameter(1)
            );

            throw new Exception\TagNeedToBeClosed(
                $e->getParameter(0),
                $errorLine['Line'],
                $errorLine['Column']
            );
        }

        return false;
    }

    /**
     * Get compile result
     *
     * @return string Compiled string.
     */
    public function result()
    {
        $result = '';

        $this->parse();


        return $result;
    }
}
