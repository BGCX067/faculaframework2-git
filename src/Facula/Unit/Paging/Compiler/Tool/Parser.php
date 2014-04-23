<?php

/**
 * Tag Parser for Page Compiler
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

namespace Facula\Unit\Paging\Compiler\Tool;

use Facula\Unit\Paging\Compiler\Exception\Parser as Exception;

/**
 * Parser tags.
 */
class Parser
{
    /** Type of the begin of a opening tag */
    const TAG_TYPE_OPENER_BEGIN = 0;

    /** Type of the end of a opening tag */
    const TAG_TYPE_OPENER_END = 1;

    /** Type of the begin of a closing tag */
    const TAG_TYPE_CLOSER_BEGIN = 4;

    /** Type of the end of a closing tag */
    const TAG_TYPE_CLOSER_END = 5;

    /** Type of the begin of a inline tag */
    const TAG_TYPE_PASS_BEGIN = 2;

    /** Type of the end of a inline tag */
    const TAG_TYPE_PASS_END = 3;

    /** Type of the begin of a child tag */
    const TAG_TYPE_MIDDLE_BEGIN = 6;

    /** Type of the end of a child tag */
    const TAG_TYPE_MIDDLE_END = 7;

    /**
     * Type of the begin of a child tag
     * which don't have parameter field
     */
    const TAG_TYPE_MIDDLE_PASS = 8;

    /** Array index of the tag beginning */
    const TAG_ARR_IDX_BEGIN = 0;

    /** Array index of the tag end */
    const TAG_ARR_IDX_END = 1;

    /** Array index of the length of the tag begin */
    const TAG_ARR_IDX_BEGIN_LENGTH = 3;

    /** Array index of the length of the tag end */
    const TAG_ARR_IDX_END_LENGTH = 4;

    /** Array index of position offset of the tag end */
    const TAG_ARR_IDX_BEGIN_OFFSET = 5;
    const TAG_ARR_IDX_END_OFFSET = 6;

    /** Opening delimiter, like <tag */
    protected static $delimiterStart = '<';

    /** Ending delimiter, like > or /> */
    protected static $delimiterEnd = '>';

    /** The ending qualifier of the closing tag */
    protected static $tagEnderSymbol = '/';

    /** The character of white space */
    protected static $tagBlankSymbol = ' ';

    /** The character of escape */
    protected static $tagSkipperSymbol = '\\';

    /** Container of all registered tags */
    protected static $registeredTags = array();

    /** Container of all registered tags */
    protected static $tagSeekTable = array();

    /** Container of all ending (not closing) tags */
    protected static $tagEndSeekTable = array();

    /** Container of all tags, indexed by search key string, like <tag or </tag */
    protected static $tagSeekArbitTab = array();

    /** Contains all tags positions */
    protected $tagPositionRaw = array();

    /** Max nest level, over this level will throw a error, 0 = not limited */
    protected $maxNests = 0;

    /**
     * Method for configure parser
     *
     * @param array $config Configure array
     *
     * @return bool Always true
     */
    public static function config(array $config)
    {
        if (isset($config['DelimiterStart'][0])) {
            static::$delimiterStart = $config['DelimiterStart'];
        }

        if (isset($config['DelimiterEnd'][0])) {
            static::$delimiterEnd = $config['DelimiterEnd'];
        }

        if (isset($config['TagEnderSymbol'][0])) {
            static::$tagEnderSymbol = $config['TagEnderSymbol'][0];
        }

        if (isset($config['TagBlankSymbol'][0])) {
            static::$tagBlankSymbol = $config['TagBlankSymbol'][0];
        }

        if (isset($config['TagSkipperSymbol'][0])) {
            static::$tagSkipperSymbol = $config['TagSkipperSymbol'][0];
        }

        return true;
    }

    /**
     * Register a tag into parser
     *
     * @param string $tagName The name of the tag
     * @param bool $inline Is this an inline tag?
     *
     * @return bool Return true when succeed, false otherwise
     */
    public static function registerTag($tagName, $inline)
    {
        $addingTag = array();

        if (isset(static::$registeredTags[$tagName])) {
            throw new Exception\TagAlreadyRegistered($tagName);

            return false;
        }

        $addingTag['Tag'] = $tagName;
        $addingTag['Inline'] = $inline;
        $addingTag['Middle'] = array();

        if ($inline) {
            $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN] =
                static::$delimiterStart . $tagName . static::$tagBlankSymbol;

            $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END] =
                static::$tagBlankSymbol . static::$tagEnderSymbol . static::$delimiterEnd;

            $addingTag['Fraged']['Close'][static::TAG_ARR_IDX_BEGIN] = '';

            $addingTag['Fraged']['Close'][static::TAG_ARR_IDX_END] = '';
        } else {
            $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN] =
                static::$delimiterStart . $tagName . static::$tagBlankSymbol;

            $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END] =
                static::$delimiterEnd;

            $addingTag['Fraged']['Close'][static::TAG_ARR_IDX_BEGIN] =
                static::$delimiterStart . static::$tagEnderSymbol . $tagName;

            $addingTag['Fraged']['Close'][static::TAG_ARR_IDX_END] =
                static::$delimiterEnd;
        }

        // Length
        $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN_LENGTH] =
            strlen($addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN]);

        $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END_LENGTH] =
            strlen($addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END]);

        $addingTag['Fraged']['Close'][static::TAG_ARR_IDX_BEGIN_LENGTH] =
            strlen($addingTag['Fraged']['Close'][static::TAG_ARR_IDX_BEGIN]);

        $addingTag['Fraged']['Close'][static::TAG_ARR_IDX_END_LENGTH] =
            strlen($addingTag['Fraged']['Close'][static::TAG_ARR_IDX_END]);

        // Offset
        $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN_OFFSET] =
            $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN_LENGTH] - 1;

        $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END_OFFSET] =
            $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END_LENGTH] - 1;

        $addingTag['Fraged']['Close'][static::TAG_ARR_IDX_BEGIN_OFFSET] =
            $addingTag['Fraged']['Close'][static::TAG_ARR_IDX_BEGIN_LENGTH] - 1;

        $addingTag['Fraged']['Close'][static::TAG_ARR_IDX_END_OFFSET] =
            $addingTag['Fraged']['Close'][static::TAG_ARR_IDX_END_LENGTH] - 1;

        // Add to register
        static::$registeredTags[$tagName] = $addingTag;

        // Add tag to seek table
        if ($inline) {
            static::$tagSeekArbitTab[$addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN]][$tagName]
                =
            static::$tagSeekTable[$addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN]]
                = array(
                    'Tag' => $tagName,
                    'Key' => $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN],
                    'KeyLen' => strlen(
                        $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN]
                    ),
                    'Type' => static::TAG_TYPE_PASS_BEGIN,
                    'Expecting' => static::setTagExpect(static::TAG_TYPE_PASS_BEGIN),
                );

            static::$tagSeekArbitTab[$addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END]][$tagName]
                =
            static::$tagEndSeekTable[$tagName]['Pass']
                = array(
                    'Tag' => $tagName,
                    'Key' => $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END],
                    'KeyLen' => strlen(
                        $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END]
                    ),
                    'Type' => static::TAG_TYPE_PASS_END,
                    'Expecting' => static::setTagExpect(static::TAG_TYPE_PASS_END),
                );
        } else {
            static::$tagSeekArbitTab[$addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN]][$tagName]
                =
            static::$tagSeekTable[$addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN]]
                = array(
                    'Tag' => $tagName,
                    'Key' => $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN],
                    'KeyLen' => strlen(
                        $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_BEGIN]
                    ),
                    'Type' => static::TAG_TYPE_OPENER_BEGIN,
                    'Expecting' => static::setTagExpect(static::TAG_TYPE_OPENER_BEGIN),
                );

            static::$tagSeekArbitTab[$addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END]][$tagName]
                =
            static::$tagEndSeekTable[$tagName]['Open']
                = array(
                    'Tag' => $tagName,
                    'Key' => $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END],
                    'KeyLen' => strlen(
                        $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END]
                    ),
                    'Type' => static::TAG_TYPE_OPENER_END,
                    'Expecting' => static::setTagExpect(static::TAG_TYPE_OPENER_END),
                );

            static::$tagSeekArbitTab[$addingTag['Fraged']['Close'][static::TAG_ARR_IDX_BEGIN]][$tagName]
                =
            static::$tagSeekTable[$addingTag['Fraged']['Close'][static::TAG_ARR_IDX_BEGIN]]
                = array(
                    'Tag' => $tagName,
                    'Key' => $addingTag['Fraged']['Close'][static::TAG_ARR_IDX_BEGIN],
                    'KeyLen' => strlen(
                        $addingTag['Fraged']['Close'][static::TAG_ARR_IDX_BEGIN]
                    ),
                    'Type' => static::TAG_TYPE_CLOSER_BEGIN,
                    'Expecting' => static::setTagExpect(static::TAG_TYPE_CLOSER_BEGIN),
                );

            static::$tagSeekArbitTab[$addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END]][$tagName]
                =
            static::$tagEndSeekTable[$tagName]['Close']
                = array(
                    'Tag' => $tagName,
                    'Key' => $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END],
                    'KeyLen' => strlen(
                        $addingTag['Fraged']['Open'][static::TAG_ARR_IDX_END]
                    ),
                    'Type' => static::TAG_TYPE_CLOSER_END,
                    'Expecting' => static::setTagExpect(static::TAG_TYPE_CLOSER_END),
                );
        }

        return true;
    }

    /**
     * Register child tag into the main tag
     *
     * @param string $middleTagOf The name of main tag
     * @param string $tagName The name of the tag
     * @param bool $hasParamter This tag will have parameter or not
     *
     * @return bool Return true when succeed, false otherwise
     */
    public static function registerMiddleTag($middleTagOf, $tagName, $hasParamter)
    {
        $fragedTag = array();

        if (!isset(static::$registeredTags[$middleTagOf])) {
            throw new Exception\MiddleTagParentNotFound($tagName, $middleTagOf);

            return false;
        }

        if (isset(static::$registeredTags[$tagName])) {
            throw new Exception\TagParentExisted($tagName, $middleTagOf);

            return false;
        }

        if (isset(static::$registeredTags[$middleTagOf]['Middle'][$tagName])) {
            throw new Exception\MiddleTagAleadyReigstered($tagName, $middleTagOf);

            return false;
        }

        if (static::$registeredTags[$middleTagOf]['Inline']) {
            throw new Exception\MiddleTagParentTagIsInline($tagName, $middleTagOf);

            return false;
        }

        static::$registeredTags[$middleTagOf]['Middle'][$tagName] = $hasParamter;

        if ($hasParamter) {
            $fragedTag[static::TAG_ARR_IDX_BEGIN] =
                static::$delimiterStart
                . $tagName
                . static::$tagBlankSymbol;

            $fragedTag[static::TAG_ARR_IDX_END] =
                static::$tagBlankSymbol
                . static::$tagEnderSymbol
                . static::$delimiterEnd;
        } else {
            $fragedTag[static::TAG_ARR_IDX_BEGIN] =
                static::$delimiterStart
                . $tagName
                . static::$tagBlankSymbol
                . static::$tagEnderSymbol
                . static::$delimiterEnd;

            $fragedTag[static::TAG_ARR_IDX_END] = '';
        }

        // Length
        $fragedTag[static::TAG_ARR_IDX_BEGIN_LENGTH] =
            strlen($fragedTag[static::TAG_ARR_IDX_BEGIN]);

        $fragedTag[static::TAG_ARR_IDX_END_LENGTH] =
            strlen($fragedTag[static::TAG_ARR_IDX_END]);

        // Offset
        $fragedTag[static::TAG_ARR_IDX_BEGIN_OFFSET] =
            $fragedTag[static::TAG_ARR_IDX_BEGIN_LENGTH] - 1;

        $fragedTag[static::TAG_ARR_IDX_END_OFFSET] =
            $fragedTag[static::TAG_ARR_IDX_END_LENGTH] - 1;

        static::$registeredTags[$middleTagOf]['Fraged']['Middle'][$tagName] = $fragedTag;

        if ($hasParamter) {
            static::$tagSeekArbitTab[$fragedTag[static::TAG_ARR_IDX_BEGIN]][$tagName]
                =
            static::$tagSeekTable[$fragedTag[static::TAG_ARR_IDX_BEGIN]]
                = array(
                    'Tag' => $tagName,
                    'Key' => $fragedTag[static::TAG_ARR_IDX_BEGIN],
                    'KeyLen' => strlen(
                        $fragedTag[static::TAG_ARR_IDX_BEGIN]
                    ),
                    'Type' => static::TAG_TYPE_MIDDLE_BEGIN,
                    'Expecting' => static::setTagExpect(static::TAG_TYPE_MIDDLE_BEGIN),
                );

            static::$tagSeekArbitTab[$fragedTag[static::TAG_ARR_IDX_END]][$tagName]
                =
            static::$tagEndSeekTable[$tagName]['Close']
                = array(
                    'Tag' => $tagName,
                    'Key' => $fragedTag[static::TAG_ARR_IDX_END],
                    'KeyLen' => strlen(
                        $fragedTag[static::TAG_ARR_IDX_END]
                    ),
                    'Type' => static::TAG_TYPE_MIDDLE_END,
                    'Expecting' => static::setTagExpect(static::TAG_TYPE_MIDDLE_END),
                );
        } else {
            static::$tagSeekArbitTab[$fragedTag[static::TAG_ARR_IDX_BEGIN]][$tagName]
                =
            static::$tagSeekTable[$fragedTag[static::TAG_ARR_IDX_BEGIN]]
                = array(
                    'Tag' => $tagName,
                    'Key' => $fragedTag[static::TAG_ARR_IDX_BEGIN],
                    'KeyLen' => strlen(
                        $fragedTag[static::TAG_ARR_IDX_BEGIN]
                    ),
                    'Type' => static::TAG_TYPE_MIDDLE_PASS,
                    'Expecting' => static::setTagExpect(static::TAG_TYPE_MIDDLE_PASS),
                );
        }

        return true;
    }

    /**
     * Get all positions of a string in another string
     *
     * @param string $key The key to search
     * @param string $content The string to be search
     * @param integer $startFrom Where to start from
     * @param array $exclude Don't pickup the position in this array
     *
     * @return array Return the search array contains all position
     */
    protected static function getAllPositionsFromString(
        $key,
        &$content,
        $startFrom,
        array &$exclude
    ) {
        $result = array();
        $cursorPos = $startFrom;
        $keyLenOffset = strlen($key) - 1;

        while (($cursorPos = strpos($content, $key, $cursorPos)) !== false) {
            if (isset($exclude[$cursorPos])) {
                $cursorPos++;

                continue;
            }

            if ($cursorPos < 1
            || $content[$cursorPos - 1] != static::$tagSkipperSymbol) {
                for ($markOut = $cursorPos + $keyLenOffset; $markOut >= $cursorPos; $markOut--) {
                    $exclude[$markOut] = true;
                }

                $result[] = $cursorPos;
            }

            $cursorPos++;
        }

        return $result;
    }

    /**
     * Set expecting mark to a tag
     *
     * @param integer $key Type of tag
     *
     * @return array Return array of expect table in $Type => true | false
     */
    protected static function setTagExpect($type)
    {
        $expecting = array(
            static::TAG_TYPE_OPENER_BEGIN => false,
            static::TAG_TYPE_OPENER_END => false,
            static::TAG_TYPE_PASS_BEGIN => false,
            static::TAG_TYPE_PASS_END => false,
            static::TAG_TYPE_CLOSER_BEGIN => false,
            static::TAG_TYPE_CLOSER_END => false,
            static::TAG_TYPE_MIDDLE_BEGIN => false,
            static::TAG_TYPE_MIDDLE_END => false,
            static::TAG_TYPE_MIDDLE_PASS => false,
        );

        switch ($type) {
            case static::TAG_TYPE_PASS_BEGIN:
                // Allow another tag opener
                $expecting[static::TAG_TYPE_OPENER_BEGIN] = true;

                // Allow another pass tag opener
                $expecting[static::TAG_TYPE_PASS_BEGIN] = true;

                // Allow to close this pass
                $expecting[static::TAG_TYPE_PASS_END] = true;
                break;

            case static::TAG_TYPE_PASS_END:
                // Allow another tag opener
                $expecting[static::TAG_TYPE_OPENER_BEGIN] = true;

                // Allow another pass tag opener
                $expecting[static::TAG_TYPE_PASS_BEGIN] = true;
                break;

            case static::TAG_TYPE_OPENER_BEGIN:
                // Allow The end of current opener tag
                $expecting[static::TAG_TYPE_OPENER_END] = true;

                // Allow the nested opening tag
                $expecting[static::TAG_TYPE_OPENER_BEGIN] = true;

                // Allow the nested pass opening tag
                $expecting[static::TAG_TYPE_PASS_BEGIN] = true;
                break;

            case static::TAG_TYPE_OPENER_END:
                // Allow the end tag
                $expecting[static::TAG_TYPE_CLOSER_BEGIN] = true;

                // Allow another opening tag
                $expecting[static::TAG_TYPE_OPENER_BEGIN] = true;

                // Allow another pass open tag
                $expecting[static::TAG_TYPE_PASS_BEGIN] = true;

                // Allow the open of a middle tag
                $expecting[static::TAG_TYPE_MIDDLE_BEGIN] = true;

                // Allow the pass open of a middle tag
                $expecting[static::TAG_TYPE_MIDDLE_PASS] = true;
                break;

            case static::TAG_TYPE_CLOSER_BEGIN:
                // Allow the end tag of current tag
                $expecting[static::TAG_TYPE_CLOSER_END] = true;

                // Allow a nested open tag
                $expecting[static::TAG_TYPE_OPENER_BEGIN] = true;

                // Allow a nested pass open tag
                $expecting[static::TAG_TYPE_PASS_BEGIN] = true;
                break;

            case static::TAG_TYPE_CLOSER_END:
                // Allow another open tag
                $expecting[static::TAG_TYPE_OPENER_BEGIN] = true;

                // Allow another pass open tag
                $expecting[static::TAG_TYPE_PASS_BEGIN] = true;
                break;


            case static::TAG_TYPE_MIDDLE_BEGIN:
                // Allow the end tag of current pass tag
                $expecting[static::TAG_TYPE_MIDDLE_END] = true;

                // Allow a nested open tag
                $expecting[static::TAG_TYPE_OPENER_BEGIN] = true;

                // Allow a nested pass open tag
                $expecting[static::TAG_TYPE_PASS_BEGIN] = true;
                break;

            // Middle pass just like the middle end
            case static::TAG_TYPE_MIDDLE_PASS:
            case static::TAG_TYPE_MIDDLE_END:
                // Allow a new middle tag open
                $expecting[static::TAG_TYPE_MIDDLE_BEGIN] = true;

                // Allow a new middle pass tag open
                $expecting[static::TAG_TYPE_MIDDLE_PASS] = true;

                // Allow a new tag to open
                $expecting[static::TAG_TYPE_OPENER_BEGIN] = true;

                // Allow a new pass tag to open
                $expecting[static::TAG_TYPE_PASS_BEGIN] = true;

                // Allow a close tag to close current tag.
                $expecting[static::TAG_TYPE_CLOSER_BEGIN] = true;
                break;

            default:
                break;
        }

        return $expecting;
    }

    /**
     * Constructor
     *
     * @param string $content Content that will be parse
     * @param integer $maxNests Max nest level
     *
     * @return void
     */
    public function __construct(&$content, $maxNests = 0)
    {
        $this->content = &$content;
        $this->maxNests = $maxNests;
    }

    /**
     * Parse and get result.
     *
     * @return array Return the array of parsed results
     */
    public function parse()
    {
        if (!$this->tagPositionRaw = $this->getTagPositions()) {
            return array();
        }

        return $this->assemble($this->pairing());
    }

    /**
     * Search all position of tags
     *
     * @return mixed Return the array of parsed result when succeed, false otherwise
     */
    public function getTagPositions()
    {
        $nestLevel = 0;
        $totalPositions = $resultPositions = $tagSearchTab = array();
        $expectedEndingTag = $tagSearchExcludes = $nest = array();

        foreach (static::$tagSeekArbitTab as $tagKey => $tags) {
            foreach ($tags as $tagName => $tagProperty) {
                $tagSearchTab[$tagProperty['KeyLen']][$tagKey]
                    = & static::$tagSeekArbitTab[$tagKey][$tagName];
            }
        }

        // Long string first
        krsort($tagSearchTab);

        // Pick up all start and end positions
        foreach ($tagSearchTab as $keyLength => $tagInfos) {
            foreach ($tagInfos as $tag => $tagInfo) {
                foreach (static::getAllPositionsFromString(
                    $tagInfo['Key'],
                    $this->content,
                    0,
                    $tagSearchExcludes
                ) as $tagPosition) {
                    switch ($tagInfo['Type']) {
                        case static::TAG_TYPE_PASS_BEGIN:
                            $totalPositions[$tagPosition]
                                = & static::$tagSeekTable[$tagInfo['Key']];

                            $expectedEnding[$tagPosition] =
                                static::$tagEndSeekTable[$tagInfo['Tag']]['Pass'];
                            break;

                        case static::TAG_TYPE_OPENER_BEGIN:
                            $totalPositions[$tagPosition]
                                = & static::$tagSeekTable[$tagInfo['Key']];

                            $expectedEnding[$tagPosition] =
                                static::$tagEndSeekTable[$tagInfo['Tag']]['Open'];
                            break;

                        case static::TAG_TYPE_CLOSER_BEGIN:
                            $totalPositions[$tagPosition]
                                = & static::$tagSeekTable[$tagInfo['Key']];

                            $expectedEnding[$tagPosition] =
                                static::$tagEndSeekTable[$tagInfo['Tag']]['Close'];
                            break;

                        case static::TAG_TYPE_MIDDLE_BEGIN:
                            $totalPositions[$tagPosition]
                                = & static::$tagSeekTable[$tagInfo['Key']];

                            $expectedEnding[$tagPosition] =
                                static::$tagEndSeekTable[$tagInfo['Tag']]['Close'];
                            break;

                        case static::TAG_TYPE_MIDDLE_PASS:
                            $totalPositions[$tagPosition]
                                = & static::$tagSeekTable[$tagInfo['Key']];

                            $expectedEnding[$tagPosition] = array();
                            break;

                        // Other type if tag, should be end tags
                        default:
                            // Save the ender's string key
                            $totalPositions[$tagPosition] = $tagInfo['Key'];
                            break;
                    }
                }
            }
        }

        // Resort as position
        ksort($totalPositions);

        // Try to pair the ending tag with starting tag.
        foreach ($totalPositions as $position => $tagInfo) {
            if (isset($expectedEnding[$position])) {
                // TagInfo here will be tag info[Tag, Key, KeyLen] etc
                switch ($tagInfo['Type']) {
                    case static::TAG_TYPE_PASS_BEGIN:
                    case static::TAG_TYPE_OPENER_BEGIN:
                    case static::TAG_TYPE_CLOSER_BEGIN:
                    case static::TAG_TYPE_MIDDLE_BEGIN:
                        if ($this->maxNests > $nestLevel) {
                            throw new Exception\MaxNestLevelReached(
                                $tagInfo['Tag'],
                                $position
                            );
                        }

                        $nest[++$nestLevel] = array(
                            'ExpectedEnding' => & $expectedEnding[$position],
                            'TagInfo' => & $totalPositions[$position]
                        );

                        $resultPositions[$position] = & $totalPositions[$position];
                        break;

                    // Middle, don't do anything
                    case static::TAG_TYPE_MIDDLE_PASS:
                        $resultPositions[$position] = & $totalPositions[$position];
                        break;

                    default:
                        break;
                }
            } elseif (isset($nest[$nestLevel])) {
                if (isset(static::$tagSeekArbitTab[$tagInfo][$nest[$nestLevel]['TagInfo']['Tag']])) {
                    if (static::$tagSeekArbitTab[$tagInfo][$nest[$nestLevel]['TagInfo']['Tag']]['Key']
                    == $nest[$nestLevel]['ExpectedEnding']['Key']) {
                        $resultPositions[$position] = $nest[$nestLevel]['ExpectedEnding'];
                        unset($nest[$nestLevel--]);
                    } else {
                        echo " nop!";
                    }
                }
            }
        }

        return $resultPositions;
    }

    /**
     * Pairing all begin and ending tags
     *
     * @return mixed Return the array of paired result when succeed, false otherwise
     */
    protected function pairing()
    {
        $nestLevel = $tagPaired = 0;
        $nest = $paired = $tempMiddleTags = $middleTags = array();
        $last = array(
            'Tag' => null,
            'Type' => null,
            'Expecting' => null,
        );
        $lastType = null;

        foreach ($this->tagPositionRaw as $position => $current) {
            switch ($current['Type']) {
                // Opening a new tag
                case static::TAG_TYPE_PASS_BEGIN:
                case static::TAG_TYPE_OPENER_BEGIN:
                    if (isset($nest[$nestLevel]['LastType'])
                    && $nest[$nestLevel]['LastExpecting'][$current['Type']]) {
                        throw new Exception\UnexpectedOpeningTag(
                            $current['Tag'],
                            $position
                        );

                        return false;
                    }

                    if ($this->maxNests && $nestLevel >= $this->maxNests) {
                        throw new Exception\MaxNestLevelReached(
                            $current['Tag'],
                            $position
                        );

                        return false;
                    }

                    $nest[++$nestLevel] = array(
                        'Tag' => $current['Tag'],
                        'NestLevel' => $nestLevel,
                        'Position' => array(
                            'Opener' => array(
                                'Start' => $position,
                                'End' => null,
                                'StarterLen' => $current['KeyLen'],
                                'EnderLen' => null,
                            ),
                            'Closer' => array(
                                'Start' => null,
                                'End' => null,
                                'StarterLen' => null,
                                'EnderLen' => null,
                            ),
                            'Middle' => array(),
                        ),
                        'LastExpecting' => $current['Expecting'],
                    );
                    break;

                // Closing the last opening tag
                case static::TAG_TYPE_PASS_END:
                    $nest[$nestLevel]['Position']['Opener']['End'] = $position;
                    $nest[$nestLevel]['Position']['Opener']['EnderLen'] = 0;

                    $nest[$nestLevel]['Position']['Closer']['Start'] = $position;
                    $nest[$nestLevel]['Position']['Closer']['StarterLen'] = 0;

                    // Fall through, TAG_TYPE_PASS_END acting like a TAG_TYPE_CLOSER_END

                case static::TAG_TYPE_CLOSER_END:
                    if (!$nestLevel
                    || !$nest[$nestLevel]['LastExpecting'][$current['Type']]) {
                        throw new Exception\UnexpectedEndOfAClosingTag(
                            $current['Tag'],
                            $position
                        );

                        return false;
                    }

                    $nest[$nestLevel]['Position']['Closer']['End'] = $position;
                    $nest[$nestLevel]['Position']['Closer']['EnderLen'] = $current['KeyLen'];

                    if (isset($nest[$nestLevel]['Temp'])) {
                        unset($nest[$nestLevel]['Temp']);
                    }

                    unset($nest[$nestLevel]['LastExpecting']);

                    $paired[$nestLevel . '.' . $tagPaired++] = $nest[$nestLevel];

                    unset($nest[$nestLevel]);

                    --$nestLevel;
                    continue 2;
                    break;

                // Reached the end of the opener tag
                case static::TAG_TYPE_OPENER_END:
                    if (!$nestLevel
                    || !$nest[$nestLevel]['LastExpecting'][$current['Type']]) {
                        throw new Exception\UnexpectedEndOfAnOpeningTag(
                            $current['Tag'],
                            $position
                        );

                        return false;
                    }

                    $nest[$nestLevel]['Position']['Opener']['End'] = $position;
                    $nest[$nestLevel]['Position']['Opener']['EnderLen'] = $current['KeyLen'];

                    break;

                // Reached the begin of the closer tag
                case static::TAG_TYPE_CLOSER_BEGIN:
                    if (!$nestLevel
                    || !$nest[$nestLevel]['LastExpecting'][$current['Type']]) {
                        throw new Exception\UnexpectedClosingTag(
                            $current['Tag'],
                            $position
                        );

                        return false;
                    }

                    $nest[$nestLevel]['Position']['Closer']['Start'] = $position;
                    $nest[$nestLevel]['Position']['Closer']['StarterLen'] = $current['KeyLen'];

                    break;

                // Reached the begin of a middle tag
                case static::TAG_TYPE_MIDDLE_BEGIN:
                    if (!$nestLevel
                    || !$nest[$nestLevel]['LastExpecting'][$current['Type']]
                    || !isset(
                        static::$registeredTags[$nest[$nestLevel]['Tag']]['Middle'][$current['Tag']]
                    )) {
                        throw new Exception\UnexpectedMiddleTag(
                            $current['Tag'],
                            $position
                        );

                        return false;
                    }

                    $nest[$nestLevel]['Temp']['MiddleStart'] = $position;
                    $nest[$nestLevel]['Temp']['MiddleTag'] = $current['Tag'];
                    $nest[$nestLevel]['Temp']['MiddleParamStart']
                        = $current['KeyLen'] + $position;
                    break;

                // Reached the end of a middle tag (middle pass just like middle end)
                case static::TAG_TYPE_MIDDLE_PASS:
                    if (!$nestLevel
                    || !$nest[$nestLevel]['LastExpecting'][$current['Type']]
                    || !isset(
                        static::$registeredTags[$nest[$nestLevel]['Tag']]['Middle'][$current['Tag']]
                    )) {
                        throw new Exception\UnexpectedEndOfAnMiddleTag(
                            $current['Tag'],
                            $position
                        );

                        return false;
                    }

                    $nest[$nestLevel]['Position']['Middle'][]
                        = array(
                            $current['Tag'],
                            $position,
                            $position + $current['KeyLen'],
                            $position + $current['KeyLen'],
                            $position + $current['KeyLen'],
                        );
                    break;

                case static::TAG_TYPE_MIDDLE_END:
                    if (!$nestLevel || !$nest[$nestLevel]['LastExpecting'][$current['Type']]) {
                        throw new Exception\UnexpectedEndOfAnMiddleTag(
                            $current['Tag'],
                            $position
                        );

                        return false;
                    }

                    $nest[$nestLevel]['Temp']['MiddleStop'] = $position;

                    $nest[$nestLevel]['Position']['Middle'][]
                        = array(
                            $nest[$nestLevel]['Temp']['MiddleTag'],
                            $nest[$nestLevel]['Temp']['MiddleStart'],
                            $nest[$nestLevel]['Temp']['MiddleStop'],
                            $nest[$nestLevel]['Temp']['MiddleParamStart'],
                            $position + $current['KeyLen']
                        );
                    break;

                default:
                    break;
            }

            $nest[$nestLevel]['LastExpecting'] = $current['Expecting'];
        }

        if (!empty($nest)) {
            throw new Exception\TagNeedToBeClosed(
                $nest[$nestLevel]['Tag'],
                $nest[$nestLevel]['Position']['Opener']['Start']
            );
        }

        krsort($paired);

        return array_values($paired);
    }

    /**
     * Assemble paired array to output format
     *
     * @return array Return the assembled array
     */
    protected function assemble(array $pairedRaw)
    {
        $result = $tempResult = array();

        foreach ($pairedRaw as $paired) {
            // Init and Tag Name
            $tempResult = array(
                'Tag' => $paired['Tag'],
            );

            // Tag start position
            $tempResult['Start'] =
                $paired['Position']['Opener']['Start'];

            // Tag end position
            $tempResult['End'] =
                $paired['Position']['Closer']['End'] + $paired['Position']['Closer']['EnderLen'];

            // Parameter: Main
            $tempResult['Parameter']['Main'][0] =
                $paired['Position']['Opener']['Start'] + $paired['Position']['Opener']['StarterLen'];

            $tempResult['Parameter']['Main'][1] =
                $paired['Position']['Opener']['End'];

            // Parameter: End
            if ($paired['Position']['Closer']['StarterLen']) { // If it don't have the ending starter
                $tempResult['Parameter']['End'][0] =
                    $paired['Position']['Closer']['Start'] + $paired['Position']['Closer']['StarterLen'];

                $tempResult['Parameter']['End'][1] =
                    $paired['Position']['Closer']['End'];
            } else {
                $tempResult['Parameter']['End'] = array(0, 0);
            }

            if (!empty($paired['Position']['Middle'])) {
                $tempResult['DataMethod'] = 'Middle';

                $tempResult['Data']['Field'] = array(
                    $paired['Position']['Opener']['End'] + $paired['Position']['Opener']['EnderLen'],
                    0
                );

                $lastMid = array();
                foreach ($paired['Position']['Middle'] as $midKey => $midVal) {
                    if (!$midKey) {
                        $tempResult['Data']['Field'][1] =
                            $midVal[1];
                    } elseif ($lastMid) {
                        $lastMid['Data'][1]
                            = $midVal[1];
                    }

                    $tempResult['Data']['Middle'][$midVal[0]][$midKey]
                        = array(
                            'Parameter' => array(
                                $midVal[3],
                                $midVal[2]
                            ),
                            'Data' => array(
                                $midVal[4],
                                0
                            ),
                        );

                    $lastMid = & $tempResult['Data']['Middle'][$midVal[0]][$midKey];
                }

                $lastMid['Data'][1]
                    = $paired['Position']['Closer']['Start'];
            } else {
                $tempResult['Data']['Field'] = array(
                    $paired['Position']['Opener']['End'] + $paired['Position']['Opener']['EnderLen'],
                    $paired['Position']['Closer']['Start']
                );
            }

            $result[] = $tempResult;
        }

        return $result;
    }
}
