<?php

/**
 * Page Compiler
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

namespace Facula\Unit\Paging;

use Facula\Base\Factory\Operator as Base;
use Facula\Base\Implement\Core\Template\Compiler as Implement;
use Facula\Base\Exception\Factory\Operator as OperatorException;
use Facula\Unit\Paging\Compiler\OperatorImplement as OperatorImplement;
use Facula\Unit\Paging\Compiler\DataContainer as DataContainer;
use Facula\Unit\Paging\Compiler\Exception\Compiler as Exception;
use Facula\Unit\Paging\Compiler\Exception\Compiler\Operator as TagOperatorException;
use Facula\Unit\Paging\Compiler\Exception\Parser as ParserException;
use Facula\Unit\Paging\Compiler\Parser as Parser;
use Facula\Unit\Paging\Compiler\Error\Compiler as Error;

/**
 * Compile a string in to formated content
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
    );

    /** per-defined tag handlers */
    protected static $operators = array(
        'var' => 'Facula\Unit\Paging\Compiler\Operator\Variable',
        'loop' => 'Facula\Unit\Paging\Compiler\Operator\Loop',
        'if' => 'Facula\Unit\Paging\Compiler\Operator\Logic',
        'case' => 'Facula\Unit\Paging\Compiler\Operator\Casing',
        'template' => 'Facula\Unit\Paging\Compiler\Operator\Template',
        'lang' => 'Facula\Unit\Paging\Compiler\Operator\Language',
        'inject' => 'Facula\Unit\Paging\Compiler\Operator\Inject',
        'pager' => 'Facula\Unit\Paging\Compiler\Operator\Pager',
    );

    /** Interface that needs to be implemented by tag handlers */
    protected static $operatorsInterface =
        'Facula\Unit\Paging\Compiler\OperatorImplement';

    /** Tag handlers */
    protected static $tagHandlers = array();

    /** Instance of content parser */
    protected $parser = null;

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

    /** Data exchanger */
    protected $data = null;

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
        static::checkTagOperators();

        return new static(
            $pool,
            $sourceContent
        );

        return false;
    }

    /**
     * Check all defined operators
     *
     * @return mixed Return true when all passed, false otherwise.
     */
    protected static function checkTagOperators()
    {
        $class = '';

        if (!empty(static::$tagHandlers)) {
            return true;
        }

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

            static::$tagHandlers[$operator] = $operatorClass;
        }

        return true;
    }

    /**
     * Get a new parser instance
     *
     * Execute Parser init, tag register to make compiler ready to compile
     *
     * @param string $sourceContent The content to be parsed
     *
     * @return bool Return true when succeed, or false otherwise.
     */
    protected static function getParser(&$sourceContent)
    {
        $parser = null;
        $class = '';
        $registerData = array();

        $parser = new Parser(
            $sourceContent,
            array(
                'DelimiterStart' => static::$config['Delimiter']['Begin'],
                'DelimiterEnd' => static::$config['Delimiter']['End'],
                'TagEnderSymbol' => static::$config['Ender'],
                'TagSkipperSymbol' => static::$config['Skipper']
            )
        );

        // Do not directly use array to register, use more standardized getOperator instead
        foreach (static::$tagHandlers as $tag => $class) {
            $registerData = $class::register();

            if (isset($registerData['Wrapped']) && $registerData['Wrapped']) {
                $parser->registerTag($tag, false);

                if (isset($registerData['Middles']) && is_array($registerData['Middles'])) {
                    foreach ($registerData['Middles'] as $midTag => $hasParameter) {
                        $parser->registerMiddleTag($tag, $midTag, $hasParameter);
                    }
                }
            } else {
                $parser->registerTag($tag, true);
            }
        }

        return $parser;
    }

    /**
     * Compile a tag according to tag parameters
     *
     * @param array $tagParameters Tag parameters in array
     * @param string $content Dynamic content that changing during compile
     * @param array $pool Pool data that will be use in tag compile
     * @param DataContainer $compileDataContainer Data used for compile this template
     *
     * @return string Return the compile result of the tag
     */
    public static function compileTag(
        array $tagParameters,
        &$content,
        array $pool,
        DataContainer $compileDataContainer
    ) {
        $result = '';
        $class = '';
        $handler = new static::$tagHandlers[$tagParameters['Tag']](
            $pool,
            static::$config,
            $compileDataContainer
        );

        if (isset($tagParameters['Parameter']['Main'])) {
            $handler->setParameter('Main', ltrim(substr(
                $content,
                $tagParameters['Parameter']['Main'][0],
                $tagParameters['Parameter']['Main'][2]
            )), ' ');
        }

        if (isset($tagParameters['Parameter']['End'])
        && $tagParameters['Parameter']['End'][2]) {
            $handler->setParameter('End', ltrim(substr(
                $content,
                $tagParameters['Parameter']['End'][0],
                $tagParameters['Parameter']['End'][2]
            )), ' ');
        }

        if (isset($tagParameters['Data']['Field'])
        && $tagParameters['Data']['Field'][2]) {
            $handler->setData(substr(
                $content,
                $tagParameters['Data']['Field'][0],
                $tagParameters['Data']['Field'][2]
            ));
        }

        if (isset($tagParameters['Data']['Middle'])) {
            foreach ($tagParameters['Data']['Middle'] as $middleTag => $middles) {
                foreach ($middles as $middleKey => $middlesVal) {
                    $handler->setMiddle(
                        $middleTag,
                        !$middlesVal['Parameter'][2] ? '' : ltrim(substr(
                            $content,
                            $middlesVal['Parameter'][0],
                            $middlesVal['Parameter'][2]
                        ), ' '),
                        !$middlesVal['Data'][2] ? '' : substr(
                            $content,
                            $middlesVal['Data'][0],
                            $middlesVal['Data'][2]
                        )
                    );
                }
            }
        }

        return $handler->compile();
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

        // Add \n in the end of file
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

        $this->parser = static::getParser($sourceContent);
        $this->data = new DataContainer();
    }

    /**
     * Convert sourceContent position to line and column
     *
     * @param integer $pos The position in sourceContent
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
     * @return array Return an array contains all parsed tags
     */
    protected function parse()
    {
        $errorLine = array();

        try {
            return $this->parser->parse();
        } catch (ParserException\MaxNestLevelReached $e) {
            $errorLine = $this->getLineByPosition(
                $e->getParameter(1)
            );

            throw new Exception\MaxNestLevelReached(
                $e->getParameter(0),
                $errorLine['Line'],
                $errorLine['Column']
            );
        } catch (ParserException\MaxTagLimitReached $e) {
            $errorLine = $this->getLineByPosition(
                $e->getParameter(1)
            );

            throw new Exception\MaxTagLimitReached(
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

        return array();
    }

    /**
     * Compile and get result
     *
     * @return string Compiled string.
     */
    public function result()
    {
        $result = $this->sourceContent;
        $resultLen = strlen($this->sourceContent);
        $oldLength = $newLength = $newEndPos = $newPosShift = 0;
        $newResult = '';
        $tags = $this->parse();
        $tag = $errorLine = array();

        foreach ($tags as $tagKey => $tagVal) {
            $tags[$tagKey]['OrgStart'] = $tagVal['Start'];
            $tags[$tagKey]['OrgEnd'] = $tagVal['End'];
        }

        while (($tag = array_shift($tags)) !== null) {
            $oldLength = $tag['End'] - $tag['Start'];

            try {
                if (!($newResult = static::compileTag(
                    $tag,
                    $result,
                    $this->sourcePool,
                    $this->data
                )) && (is_bool($newResult) || is_null($newResult))) {
                    $errorLine = $this->getLineByPosition(
                        $tag['OrgStart']
                    );

                    throw new Exception\TagCompileEmptyResult(
                        $tag['Tag'],
                        $errorLine['Line'],
                        $errorLine['Column']
                    );

                    return false;
                }
            } catch (TagOperatorException $e) {
                $errorLine = $this->getLineByPosition(
                    $tag['OrgStart']
                );

                throw new TagOperatorException(
                    $errorLine['Line'],
                    $errorLine['Column'],
                    $tag['Tag'],
                    $e->getMessage()
                );
            }

            $newLength = strlen($newResult);

            $newPosShift = $newLength - $oldLength;

            // Shift all the positions of tag that behind current one
            foreach ($tags as $tagPosSK => $tagPosShift) {
                if ($tagPosShift['Start'] >= $tag['End']) {
                    $tags[$tagPosSK]['Start'] += $newPosShift;
                }

                if ($tagPosShift['End'] >= $tag['End']) {
                    $tags[$tagPosSK]['End'] += $newPosShift;
                }

                // Shift positions in parameter
                foreach ($tagPosShift['Parameter'] as $tagPosSParamKey => $tagPosShiftParam) {
                    if ($tagPosShiftParam[0] >= $tag['End']) {
                        $tags[$tagPosSK]['Parameter'][$tagPosSParamKey][0] += $newPosShift;
                    }

                    if ($tagPosShiftParam[1] >= $tag['End']) {
                        $tags[$tagPosSK]['Parameter'][$tagPosSParamKey][1] += $newPosShift;
                    }

                     $tags[$tagPosSK]['Parameter'][$tagPosSParamKey][1] =
                        $tags[$tagPosSK]['Parameter'][$tagPosSParamKey][1]
                        - $tags[$tagPosSK]['Parameter'][$tagPosSParamKey][0];
                }

                // Shift positions in data
                if ($tagPosShift['Data']['Field'][0] >= $tag['End']) {
                    $tags[$tagPosSK]['Data']['Field'][0] += $newPosShift;
                }

                if ($tagPosShift['Data']['Field'][1] >= $tag['End']) {
                    $tags[$tagPosSK]['Data']['Field'][1] += $newPosShift;
                }

                $tags[$tagPosSK]['Data']['Field'][2] =
                    $tags[$tagPosSK]['Data']['Field'][1]
                    - $tags[$tagPosSK]['Data']['Field'][0];

                // Middle tag if existed
                if (isset($tagPosShift['Data']['Middle'])) {
                    foreach ($tagPosShift['Data']['Middle'] as $tagPosMKey => $tagPosSMVals) {
                        foreach ($tagPosSMVals as $mTKey => $mTParam) {
                            if ($mTParam['Parameter'][0] >= $tag['End']) {
                                $tags[$tagPosSK]['Data']['Middle'][$tagPosMKey][$mTKey]['Parameter'][0]
                                    += $newPosShift;
                            }

                            if ($mTParam['Parameter'][1] >= $tag['End']) {
                                $tags[$tagPosSK]['Data']['Middle'][$tagPosMKey][$mTKey]['Parameter'][1]
                                    += $newPosShift;
                            }

                            $tags[$tagPosSK]['Data']['Middle'][$tagPosMKey][$mTKey]['Parameter'][2] =
                                $tags[$tagPosSK]['Data']['Middle'][$tagPosMKey][$mTKey]['Parameter'][1]
                                - $tags[$tagPosSK]['Data']['Middle'][$tagPosMKey][$mTKey]['Parameter'][0];

                            if ($mTParam['Data'][0] >= $tag['End']) {
                                $tags[$tagPosSK]['Data']['Middle'][$tagPosMKey][$mTKey]['Data'][0]
                                    += $newPosShift;
                            }

                            if ($mTParam['Data'][1] >= $tag['End']) {
                                $tags[$tagPosSK]['Data']['Middle'][$tagPosMKey][$mTKey]['Data'][1]
                                    += $newPosShift;
                            }

                            $tags[$tagPosSK]['Data']['Middle'][$tagPosMKey][$mTKey]['Data'][2] =
                                $tags[$tagPosSK]['Data']['Middle'][$tagPosMKey][$mTKey]['Data'][1]
                                - $tags[$tagPosSK]['Data']['Middle'][$tagPosMKey][$mTKey]['Data'][0];
                        }
                    }
                }
            }

            $result = substr($result, 0, $tag['Start'])
                . $newResult
                . substr($result, $tag['End'], $resultLen - $tag['End']);

            $resultLen += $newPosShift;
        }

        return $result;
    }
}
