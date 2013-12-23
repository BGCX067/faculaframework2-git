<?php

/**
 * Text Formater
 *
 * Facula Framework 2013 (C) Rain Lee
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
 * @copyright  2013 Rain Lee
 * @package    Facula
 * @version    2.2 prototype
 * @see        https://github.com/raincious/facula FYI
 */

namespace Facula\Unit;

/**
 * Text Formater
 */
class Formated
{
    /** Anit-reinit Tag */
    private static $inited = false;

    /** Declaration of default delimiters */
    private static $delimiters = array(
        'Tag' => array(
            'Start' => '(',
            'End' => ')',
        ),
        'Property' => array(
            'Start' => '[',
            'End' => ']',
        )
    );

    /** Declaration of default settings */
    private static $defaults = array(
        'Tag' => array(),
        'Setting' => array(
            'MaxNests' => 1,
            'MaxTags' => 30,
        ),
    );

    /** Declared tags */
    private $tags = array();

    /** Assigned datas */
    private $assign = array();

    /** Setting for current instance */
    private $setting = array();

    /** Contents that will be render */
    private $content = '';

    /** Scanned tags */
    private $tagMap = array();

    /**
     * Create instance by string
     *
     * @param string $textContent Content to be formated
     * @param array $setting Array of setting
     *
     * @return object A new object instance of this class
     */
    public static function get($textContent, array $setting = array())
    {
        return new self($textContent, $setting);
    }

    /**
     * Create instance by string
     *
     * @param string $file Path to file that will be rendered
     * @param array $setting Array of setting
     *
     * @return object A new object instance of this class
     */
    public static function getFromFile($file, array $setting = array())
    {
        $fileContent = '';

        if (is_readable($file)) {
            if ($fileContent = file_get_contents($file)) {
                return new self($fileContent, $setting);
            }
        }

        return false;
    }

    /**
     * Add new tag in to format
     *
     * @param string $processerType The tag type by one character
     * @param closure $processer Processor that will be use to process the tag on content parsing
     * @param closure $assigner Assigner that will be use to parse data when assigning
     *
     * @return object A new object instance of this class
     */
    public static function newTag($processerType, \Closure $processer, \Closure $assigner)
    {
        if (!isset(self::$defaults['Tag'][$processerType[0]])) {
            self::$defaults['Tag'][$processerType[0]] = array(
                'Processer' => $processer,
                'Assigner' => $assigner,
            );

            return true;
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_FORMATED_DEFAULT_TAG_EXISTED|' . $processerType[0],
                'formated',
                true
            );
        }

        return false;
    }

    /**
     * Preparing the class
     *
     * @return bool Return true when success, false on fail (For exp when class already inited)
     */
    private static function selfInit()
    {
        if (static::$inited) {
            return true;
        } else {
            static::$inited = true;
        }

        static::newTag(
            '%',
            function ($value, $selected, $param, $pool) {
                $poolRef = null;
                $result = $value;

                if (is_null($value)) {
                    $locator = explode('.', trim($selected), 2);

                    if (isset($pool['%'][$locator[0]])) {
                        if (!isset($locator[1])) {
                            $result = $pool['%'][$locator[0]];
                        } elseif (isset($pool['%'][$locator[0]]['.' . $locator[1]])) {
                            $result = $pool['%'][$locator[0]]['.' . $locator[1]];
                        }
                    }
                }

                if (is_string($result) || is_numeric($result)) {
                    foreach (explode(' ', $param) as $currentParam) {
                        switch (trim($currentParam)) {
                            case 'Lower':
                                $result = strtolower(
                                    $result
                                );
                                break;

                            case 'Upper':
                                $result = strtoupper(
                                    $result
                                );
                                break;

                            case 'Uppercase':
                                $result = ucfirst(
                                    $result
                                );
                                break;

                            case 'Lowercase':
                                $result = lcfirst(
                                    $result
                                );
                                break;

                            case 'Number':
                                $result = number_format(
                                    (int)($result),
                                    2,
                                    '.',
                                    ','
                                );
                                break;

                            case 'Html':
                                $result = htmlspecialchars(
                                    $result
                                );
                                break;

                            case 'URL':
                                $result = urlencode(
                                    $result
                                );
                                break;

                            case 'Slashes':
                                $result = addslashes(
                                    $result
                                );
                                break;

                            case 'Br':
                                $result = nl2br(
                                    $result
                                );
                                break;

                            default:
                                if (isset($pool['!'][$param])) {
                                    $result = sprintf(
                                        $pool['!'][$param],
                                        $result
                                    );
                                }
                                break;
                        }
                    }

                    return $result;
                }

                return false;
            },
            function ($value) {
                $flated = array();
                $lastKey = '';

                if (is_array($value)) {
                    $recursive_array = function ($array, $tag) use (&$recursive_array, &$flated) {
                        foreach ($array as $key => $val) {
                            $key = '.' . $key;

                            if (is_array($val)) {
                                $recursive_array($val, $key);
                            } else {
                                $flated[$tag . $key] = $val;
                            }
                        }
                    };

                    $recursive_array($value, '');

                    return $flated;
                } else {
                    return $value;
                }

                return false;
            }
        );

        static::newTag(
            '!',
            function ($value, $selected, $param) {
                if ($value) {
                    return $value;
                }

                return $param;
            },
            function ($value) {
                return $value;
            }
        );

        return true;
    }

    /**
     * Constructor
     *
     * @param string $text The content will be formated
     * @param array $setting setting for this instance
     *
     * @return void
     */
    protected function __construct(&$text, &$setting)
    {
        static::selfInit();

        $this->content = $text;
        $this->tags = self::$defaults['Tag'];

        $this->setting = array(
            'MaxNests' => isset($setting['MaxNests'])
                ? (int)($setting['MaxNests']) : self::$defaults['Setting']['MaxNests'],

            'MaxTags' => isset($setting['MaxTags'])
                ? (int)($setting['MaxTags']) : self::$defaults['Setting']['MaxTags'],
        );
    }

    /**
     * Assign a variable into current instance
     *
     * @param string $processerType Type of the tag in one character
     * @param string $name Name of the tag
     * @param string $value Value of the tag
     *
     * @return bool return true when success, false on fail
     */
    public function assign($processerType, $name, $value)
    {
        $assigner = $result = null;

        if (isset($this->tags[$processerType[0]])) {
            $assigner = $this->tags[$processerType[0]]['Assigner'];

            if ($result = $assigner($value)) {
                $this->assign[$processerType[0]][$name] = $result;

                return true;
            }
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_FORMATED_TAG_NOT_EXISTE|' . $processerType[0],
                'formated',
                true
            );
        }

        return false;
    }

    /**
     * Add a new tag into current instance
     *
     * @param string $processerType The tag type by one character
     * @param closure $processer Processor that will be use to process the tag on content parsing
     * @param closure $assigner Assigner that will be use to parse data when assigning
     *
     * @return bool return true when success, false on fail
     */
    public function addTag($processerType, \Closure $processer, \Closure $assigner)
    {
        $this->tags[$processerType[0]] = array(
            'Processer' => $processer,
            'Assigner' => $assigner,
        );

        return true;
    }

    /**
     * Add a new tag into current instance
     *
     * @param array $error A reference to get detailed error report
     *
     * @return mixed return true when rendered, or false otherwise
     */
    public function render(array &$error = array())
    {
        if ($rendered = $this->parseTags($error)) {
            return $rendered;
        }

        return false;
    }

    /**
     * Parse all tags
     *
     * @param array $error A reference to get detailed error report
     *
     * @return mixed return true when rendered, or false otherwise
     */
    protected function parseTags(array &$error)
    {
        $newValue = $processer = $tempValue = null;
        $tagPos = $tempData = array();

        $tagMap = array();

        if ($this->scanTags($error)) {
            // Reset Tag mark map
            $tagMap = $this->tagMap;

            // Reset rendered content
            $rendered = $this->content;

            foreach ($tagMap as $key => $val) {
                if (isset($this->tags[$val['Tag']])) {
                    $tagPos = array();

                    $tagPos['TagStart'] = $tagMap[$key]['TagStart'];
                    $tagPos['FullStart'] = $tagPos['TagStart'] - 1;

                    if ($tagMap[$key]['PropertyStart']
                        && $tagMap[$key]['PropertyEnd']) {
                        $tagPos['TagEnd'] = $tagMap[$key]['TagEnd'];

                        $tagPos['PropertyStart'] = $tagMap[$key]['PropertyStart'];
                        $tagPos['FullEnd'] = ($tagPos['PropertyEnd'] = $tagMap[$key]['PropertyEnd']) + 1;

                        $tagPos['PropertyLen'] = $tagPos['PropertyEnd'] - $tagPos['PropertyStart'];

                        $tagPos['Param'] = substr(
                            $rendered,
                            $tagPos['PropertyStart'] + 1,
                            $tagPos['PropertyLen'] - 1
                        );
                    } else {
                        $tagPos['FullEnd'] = ($tagPos['TagEnd'] = $tagMap[$key]['TagEnd']) + 1;

                        $tagPos['PropertyLen'] = $tagPos['PropertyStart'] = $tagPos['PropertyEnd'] = 0;
                        $tagPos['Param'] = null;
                    }

                    $tagPos['TagLen'] = $tagPos['TagEnd'] - $tagPos['TagStart'];
                    $tagPos['FullLen'] = $tagPos['FullEnd'] - $tagPos['FullStart'];
                    $tagPos['Value'] = substr(
                        $rendered,
                        $tagPos['TagStart'] + 1,
                        $tagPos['TagLen'] - 1
                    );

                    $processer = $this->tags[$val['Tag']]['Processer'];

                    if (isset($this->assign[$val['Tag']][$tagPos['Value']])) {
                        $tempValue = $this->assign[$val['Tag']][$tagPos['Value']];
                    } else {
                        $tempValue = null;
                    }

                    if (($newValue = $processer(
                        $tempValue,
                        $tagPos['Value'],
                        $tagPos['Param'],
                        $this->assign))
                        && (is_string($newValue) || is_numeric($newValue))) {
                        $rendered = substr($rendered, 0, $tagPos['FullStart'])
                                    . $newValue
                                    . substr(
                                        $rendered,
                                        $tagPos['FullEnd'],
                                        strlen($rendered)
                                    );

                        $newLen = strlen($newValue);

                        if ($tagPos['FullLen'] != $newLen) {
                            $tagNewEnd = $newLen - $tagPos['FullLen'];

                            foreach ($tagMap as $posModifyKey => $posModifyVal) {
                                if ($key != $posModifyKey) {
                                    if ($posModifyVal['TagStart'] > $tagPos['TagStart']) {
                                        $tagMap[$posModifyKey]['TagStart'] += $tagNewEnd;
                                    }

                                    if ($posModifyVal['TagEnd'] > $tagPos['TagEnd']) {
                                        $tagMap[$posModifyKey]['TagEnd'] += $tagNewEnd;
                                    }

                                    if ($posModifyVal['PropertyStart'] > $tagPos['PropertyStart']) {
                                        $tagMap[$posModifyKey]['PropertyStart'] += $tagNewEnd;
                                    }

                                    if ($posModifyVal['PropertyEnd'] > $tagPos['PropertyEnd']) {
                                        $tagMap[$posModifyKey]['PropertyEnd'] += $tagNewEnd;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return $rendered;
        }

        return false;
    }

    /**
     * Scan tags for parse
     *
     * @param array $error A reference to get detailed error report
     *
     * @return mixed return true when scan complete, or false otherwise
     */
    private function scanTags(array &$error)
    {
        $lastCharOffset = $nextCharOffset = $tagID = $contentLen = $contentMaxPos = 0;

        $lastNests = $tagInfos = $tagInfoMap = $tagTempInfo =
        $keyPosMarks = $scannedTags = $keyChars = array();

        $lastNested = null;
        $remainTags = $this->setting['MaxTags'];

        if (empty($this->tagMap)) {
            $keyChars = array_keys($this->tags);

            // Add tag start and end char to key
            $keyChars[] = self::$delimiters['Tag']['Start'];
            $keyChars[] = self::$delimiters['Tag']['End'];

            // Add property start and end to key
            $keyChars[] = self::$delimiters['Property']['Start'];
            $keyChars[] = self::$delimiters['Property']['End'];

            // Scan all key mark from the content
            foreach ($keyChars as $keyChar) {
                $lastKeyPos = $lastPickUpKeyPos = 0;

                while (($lastKeyPos = strpos(
                    $this->content,
                    $keyChar,
                    $lastPickUpKeyPos
                )) !== false) {
                    $keyPosMarks[] = $lastKeyPos;
                    $lastPickUpKeyPos = $lastKeyPos + 1;
                }
            }

            asort($keyPosMarks);

            $contentMaxPos = ($contentLen = strlen($this->content)) - 1;

            foreach ($keyPosMarks as $charOffset) {
                $lastCharOffset = $charOffset > 0 ? $charOffset - 1 : 0;
                $nextCharOffset = $charOffset < $contentMaxPos - 1 ? $charOffset + 1 : $contentMaxPos;

                switch ($this->content[$charOffset]) {
                    case self::$delimiters['Tag']['Start']:
                        if (isset($lastNested['Data'])) {
                            $lastNested['Data']['D.T']++;

                            if ($lastNested['Data']['T.T.Start']) {
                                $lastNested['Data']['T.T.Start'] = false;

                                if ($lastNested['Data']['Start'] == $lastCharOffset) {
                                    $lastNested['TagStart'] = $charOffset;

                                    $lastNested['Data']['T.T.End'] = true;
                                } else {
                                    unset($lastNests[$tagID--]);
                                    $lastNested = &$lastNests[$tagID];
                                }
                            }
                        }
                        break;

                    case self::$delimiters['Tag']['End']:
                        if (isset($lastNested['Data']) && $lastNested['Data']['T.T.End']) {
                            if (--$lastNested['Data']['D.T'] == 0) {
                                $lastNested['TagEnd'] = $charOffset;

                                $lastNested['Data']['T.T.End'] = false;

                                if ($nextCharOffset != $charOffset
                                    && $this->content[$nextCharOffset] ==
                                    self::$delimiters['Property']['Start']) {
                                    $lastNested['Data']['T.P.Start'] = true;
                                } else {
                                    unset($lastNested['Data'], $lastNests[$tagID--]);

                                    $scannedTags[] = $lastNested; // Add tag information to result array
                                    $lastNested = &$lastNests[$tagID];
                                }
                            }
                        }
                        break;

                    case self::$delimiters['Property']['Start']:
                        if (isset($lastNested['Data'])) {
                            $lastNested['Data']['D.P']++;

                            if ($lastNested['Data']['T.P.Start']) {
                                $lastNested['Data']['T.P.Start'] = false;

                                $lastNested['PropertyStart'] = $charOffset;

                                if ($this->content[$lastCharOffset] == self::$delimiters['Tag']['End']) {
                                    $lastNested['Data']['D.P.End'] = true;
                                } else {
                                    // If something went wrong, Close this tag
                                    $lastNested['PropertyEnd'] = $charOffset;

                                    unset($lastNested['Data'], $lastNests[$tagID--]);

                                    // Add tag information to result array
                                    $scannedTags[] = $lastNested;
                                    $lastNested = &$lastNests[$tagID];
                                }
                            }
                        }
                        break;

                    case self::$delimiters['Property']['End']:
                        if (isset($lastNested['Data']) && $lastNested['Data']['D.P.End']) {
                            if (--$lastNested['Data']['D.P'] == 0) {
                                $lastNested['Data']['D.P.End'] = false;

                                $lastNested['PropertyEnd'] = $charOffset;

                                unset($lastNested['Data'], $lastNests[$tagID--]);

                                // Add tag information to result array
                                $scannedTags[] = $lastNested;
                                $lastNested = &$lastNests[$tagID];
                            }
                        }
                        break;

                    default:
                        if (isset($this->tags[$this->content[$charOffset]])
                            && $this->content[$nextCharOffset] == self::$delimiters['Tag']['Start']) {
                            if (--$remainTags < 0) {
                                $splitPrvContent = explode("\n", substr($this->content, 0, $charOffset));
                                $splitPrvTotalLines = count($splitPrvContent);

                                unset($splitPrvContent[$splitPrvTotalLines - 1]);

                                $prvContentlastLen = $charOffset - strlen(implode("\n", $splitPrvContent));

                                $error = array(
                                            'Tag' => $this->content[$charOffset],
                                            'Error' => 'GENERAL_ERROR_TAG_OVERLIMIT',
                                            'Arg' => array (
                                                        'Line' => $splitPrvTotalLines,
                                                        'Char' => $prvContentlastLen,
                                                        'Max' => $this->setting['MaxTags'],
                                                    ),
                                        );

                                return false;
                            }

                            $tagTempInfo = array(
                                'Data' => array(
                                    'T.T.Start' => true,
                                    'T.T.End' => false,
                                    'T.P.Start' => false,
                                    'T.P.End' => false,
                                    'D.T' => 0,
                                    'D.P' => 0,
                                    'Start' => $charOffset,
                                ),
                                'Tag' => $this->content[$charOffset],
                                'TagStart' => 0,
                                'TagEnd' => 0,
                                'PropertyStart' => 0,
                                'PropertyEnd' => 0,
                            );

                            if (is_null($lastNested)) {
                                $tagInfos[$charOffset] = $tagTempInfo;

                                $lastNests[++$tagID] = &$tagInfos[$charOffset];
                                $tagInfoMap[$charOffset] = &$tagInfos[$charOffset];

                                $lastNested = &$lastNests[$tagID];
                            } else {
                                if (isset($lastNested['Data']['Level'])) {
                                    $tagTempInfo['Data']['Level'] = $lastNested['Data']['Level'] + 1;
                                } else {
                                    $tagTempInfo['Data']['Level'] = 1;
                                }

                                if ($tagTempInfo['Data']['Level'] <= $this->setting['MaxNests']) {
                                    $lastNested['Subs'][$charOffset] = $tagTempInfo;

                                    $lastNests[++$tagID] = &$lastNested['Subs'][$charOffset];
                                    $tagInfoMap[$charOffset] = &$lastNested['Subs'][$charOffset];

                                    $lastNested = &$lastNests[$tagID];
                                }
                            }
                        }
                        break;
                }
            }

            foreach ($tagInfoMap as $tag) {
                if (isset($tag['Data'])) {
                    $error['Tag'] = $tag['Tag'];

                    if ($tag['Data']['T.T.End']) {
                        $errorOffset = $tag['TagStart'];

                        $error['Error'] = 'SYNTAX_ERROR_TAG_UNCLOSE';
                        $error['Arg'] = array(
                            'Remain' => $tag['Data']['D.T'],
                        );
                    } elseif ($tag['Data']['D.P.End']) {
                        $errorOffset = $tag['PropertyStart'];

                        $error['Error'] = 'SYNTAX_ERROR_PROPERTY_UNCLOSE';
                        $error['Arg'] = array(
                            'Remain' => $tag['Data']['D.P'],
                        );
                    }

                    $splitedContent = explode("\r\n", substr($this->content, 0, $errorOffset));
                    $splitedContentLens = count($splitedContent);

                    $error['Arg']['Line'] = $splitedContentLens;

                    unset($splitedContent[$splitedContentLens - 1]);

                    $error['Arg']['Char'] = $errorOffset - strlen(
                        implode("\r\n", $splitedContent)
                    );

                    return false;
                }
            }

            $this->tagMap = $scannedTags;

            return true;
        } else {
            return true;
        }

        return false;
    }
}
