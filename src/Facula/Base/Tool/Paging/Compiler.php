<?php

/**
 * Page Compiler
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

namespace Facula\Base\Tool\Paging;

/**
 * Provide a space to compile Facula pages
 */
class Compiler implements \Facula\Base\Implement\Core\Template\Compiler
{
    /*
        Format rules and parse priority:

        // Include another template
        {+ path/templatename set="string1=replacewith1" set="string2=replacewith2" +}

        // Targeting an area for content inject
        {# Name empty="Message for empty" wrap="<div>(CODE)</div>" #}

        {! LANGUAGE_KEY !} // Inject content from auto loaded inline files (for language etc)

        {% $Variable %} // Simplely display the value of that variable
        {% $Variable|format %} // Display the value of that variable with specified format

        // Display page switcher
        {~
            name="IDName"
            classname="ClassName"
            current="$CurrentPage"
            total="$TotalPages"
            max="$MaxPagesToDisplay"
            format="URLFORMAT/%PAGE%/"
        ~}

        {* LoopName $Variable *}
            <!--HTML CONTENTS-->
        {* EMPTY LoopName *}
            <!--HTML CONTENTS WHEN LOOP IS EMPTY-->
        {* EOF LoopName *} // Loop the Variable if it is an array

        {? LogicName $Variable > 1 ?}
        <!--HTML CONTENTS-->
        {? ELSEIF LogicName $Variable > 2 ?}
        <!--HTML CONTENTS-->
        {? ELSE LogicName ?}
        <!--HTML CONTENTS-->
        {? EOF LogicName ?} // Simple logic

        {^ SwitcherName $Variable ^}
        <!--HTML CONTENTS: DEFAULT-->
        {^ CASE SwitcherName 1 ^}
        <!--HTML CONTENTS: WHEN $Variable == 1-->
        {^ CASE SwitcherName 2 ^}
        <!--HTML CONTENTS: WHEN $Variable == 2-->
        {^ CASE SwitcherName 3 ^}
        <!--HTML CONTENTS: WHEN $Variable == 3-->
        {^ EOF SwitcherName ^}
    */

    /** Default setting of this class */
    private static $setting = array(
        'Delimiter' => '{}',
        'Formats' => array(
            array('Tag' => '+', 'Command' => 'doInclude', 'IsExternal' => false),
            array('Tag' => '#', 'Command' => 'doInjectArea', 'IsExternal' => false),
            array('Tag' => '!', 'Command' => 'doLanguage', 'IsExternal' => false),
            array('Tag' => '%', 'Command' => 'doVariable', 'IsExternal' => false),
            array('Tag' => '~', 'Command' => 'doPageSwitcher', 'IsExternal' => false),
            array('Tag' => '*', 'Command' => 'doLoop', 'IsExternal' => false),
            array('Tag' => '?', 'Command' => 'doLogic', 'IsExternal' => false),
            array('Tag' => '^', 'Command' => 'doCase', 'IsExternal' => false),
        ),
    );

    /** Assigned data */
    private $pool = array();

    /** The content that will be compile */
    private $sourceContent = '';

    /** Position map */
    private $tagPositionMaps = array();

    /**
     * Constructor of compiler
     *
     * @param array $tag Assigned data
     * @param string $sourceTpl Content of the template file
     *
     * @return void
     */
    public function __construct(array &$pool, &$sourceTpl)
    {
        $this->pool = $pool;
        $this->sourceContent = $sourceTpl;

        return true;
    }

    /**
     * Compile the template
     *
     * @return string Compiled template in PHP syntax.
     */
    public function compile()
    {
        $matchedTags = array();
        $tempResult = array();
        $unclosedTag = '';

        if ($this->sourceContent) { // If file has been successfully readed
            $content = $this->sourceContent;

            foreach (self::$setting['Formats'] as $format) {
                $format['Preg'] = '/'
                                . preg_quote(
                                    self::$setting['Delimiter'][0]. $format['Tag'],
                                    '/'
                                )
                                . '\s(.*)\s'
                                . preg_quote(
                                    $format['Tag'] . self::$setting['Delimiter'][1],
                                    '/'
                                )
                                . '/sU';

                if ($format['IsExternal']) {
                    $format['Function'] = $format['Command'];
                } else {
                    $format['Function'] = array(&$this, $format['Command']);
                }

                while (preg_match($format['Preg'], $content, $matchedTags, PREG_OFFSET_CAPTURE)) {

                    // Get Original content info
                    $tempResult['OriginalLen'] = strlen($matchedTags[0][0]);
                    $tempResult['StartPos'] = $matchedTags[0][1];
                    $tempResult['EndPos'] = $tempResult['StartPos'] + $tempResult['OriginalLen'];

                    // Generate replacement
                    if (!($tempResult['Result'] = $format['Function'](
                        $matchedTags[1][0],
                        $tempResult['StartPos'],
                        $this->tagPositionMaps
                    )) && is_bool($tempResult['Result'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_UNKNOWNERROR',
                            'template',
                            true
                        );
                        return false;

                        break;
                        break;
                    }

                    $tempResult['ResultLen'] = strlen($tempResult['Result']);
                    $tempResult['ResultEndPos'] = $tempResult['StartPos'] + $tempResult['ResultLen'];

                    // Cuting out the original content
                    $contentBefore = $contentAfter = '';

                    $contentBefore = substr($content, 0, $tempResult['StartPos']);
                    $contentAfter = substr($content, $tempResult['EndPos'], strlen($content));

                    // Reassembling content with new result
                    $content = $contentBefore . $tempResult['Result'] . $contentAfter;

                    $tempResult['LenDifference'] = $tempResult['ResultLen'] - $tempResult['OriginalLen'];

                    foreach ($this->tagPositionMaps as $tagKey => $tagPos) {
                        // If target tag's start position after current tag's start position.
                        // increase the target tag's start position as move it back.
                        if (isset($tagPos['Start']) && $tagPos['Start'] > $tempResult['StartPos']) {
                            $this->tagPositionMaps[$tagKey]['Start'] += $tempResult['LenDifference'];
                        }

                        // If target tag's start position after current tag's start position.
                        // increase the target tag's end also

                        // And if target tag's start position small than current tag and end position
                        // larget than current tag. The current tag must with in the target tag. So we
                        // need to move target tag's end position back.
                        if (isset($tagPos['End'])
                            && (
                                $tagPos['Start'] > $tempResult['StartPos']
                                || (
                                        $tagPos['Start'] < $tempResult['StartPos']
                                        && $tagPos['End'] > $tempResult['EndPos']
                                    )
                            )
                        ) {
                            $this->tagPositionMaps[$tagKey]['End'] += $tempResult['LenDifference'];
                        }

                        if (isset($tagPos['Middle'])) {
                            foreach ($tagPos['Middle'] as $tagMidKey => $tagMidVal) {
                                if ($tagMidVal > $tempResult['StartPos']) {
                                    $this->tagPositionMaps[$tagKey]['Middle'][$tagMidKey] +=
                                    $tempResult['LenDifference'];
                                }
                            }
                        }
                    }
                }
            }

            if ((!$unclosedTag = $this->doCheckUnclosedTags())) {
                return $content;
            } else {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_TEMPLATE_COMPILER_TAG_UNCLOSE|' . $unclosedTag,
                    'template',
                    true
                );
            }
        }

        return false;
    }

    /**
     * Check unclosed tag
     *
     * @return mixed Return tag and tag name paired with ':' when found unclosed tag, or false otherwise.
     */
    protected function doCheckUnclosedTags()
    {
        foreach ($this->tagPositionMaps as $refKey => $refRecord) {
            if (!isset($refRecord['Start']) || !isset($refRecord['End'])) {
                return $refRecord['Name'];
            }

            foreach ($this->tagPositionMaps as $tagKey => $tagRecord) {
                if ($refKey != $tagKey) {
                    if (!isset($tagRecord['Start']) || !isset($tagRecord['End'])) {
                        return $tagRecord['Name'];
                    }

                    if ($refRecord['Start'] < $tagRecord['Start']
                        && $refRecord['End'] > $tagRecord['Start']
                        && $refRecord['End'] < $tagRecord['End']) {
                        return $refRecord['Name'] . ':' . $tagRecord['Name'];
                    } elseif ($refRecord['Start'] < $tagRecord['End']
                        && $refRecord['End'] > $tagRecord['End']
                        && $refRecord['Start'] > $tagRecord['Start']) {
                        return $refRecord['Name'] . ':' . $tagRecord['Name'];
                    } elseif (isset($tagRecord['Middle'])) {
                        array_unshift($tagRecord['Middle'], $tagRecord['Start']);

                        $tagRecord['Middles'] = count($tagRecord['Middle']);

                        for ($i = 1; $i < $tagRecord['Middles']; $i++) {
                            if ($refRecord['Start'] < $tagRecord['Middle'][$i - 1]
                                && $refRecord['End'] > $tagRecord['Middle'][$i - 1]
                                && $refRecord['End'] < $tagRecord['Middle'][$i]) {
                                return $refRecord['Name'] . ':' . $tagRecord['Name'];
                            } elseif ($refRecord['Start'] < $tagRecord['Middle'][$i]
                                && $refRecord['End'] > $tagRecord['Middle'][$i]
                                && $refRecord['Start'] > $tagRecord['Middle'][$i - 1]) {
                                return $refRecord['Name'] . ':' . $tagRecord['Name'];
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if variable name is valid
     *
     * @param string $name Variable name
     *
     * @return mixed Return true if it's valid, false otherwise
     */
    protected function doCheckVariableName($name)
    {
        if (preg_match('/^(\$[A-Za-z0-9\_\'\"\[\]]+)$/', $name)) {
            return true;
        }

        return false;
    }

    /**
     * Parser: Include another template
     *
     * @param string $format Format string from template file
     * @param integer $pos Current position of tag
     *
     * @return mixed Return true when succeed, false otherwise
     */
    protected function doInclude($format, $pos)
    {
        $param = explode(' ', $format, 2);

        if (isset($this->pool['File']['Tpl'][$param[0]]['default'])) {
            $tplFileContent = file_get_contents(
                $this->pool['File']['Tpl'][$param[0]]['default']
            );

            $params = new \Facula\Base\Tool\Paging\Compiler\Parameters(
                isset($param[1]) ? $param[1] : '',
                array(
                    'set' => 'default',
                )
            );

            $setParams = array();
            $setReplaceGroup = array(
                'Search' => array(),
                'Replace' => array()
            );

            foreach ($params->getAll('set') as $setParam) {
                $setParams = explode('=', $setParam, 2);

                if (isset($setParams[0], $setParams[1])) {
                    $setReplaceGroup['Search'][] = trim($setParams[0]);
                    $setReplaceGroup['Replace'][] = trim($setParams[1]);
                }
            }

            $tplFileContent = str_replace(
                $setReplaceGroup['Search'],
                $setReplaceGroup['Replace'],
                $tplFileContent
            );

            $newCompiler = new self($this->pool, $tplFileContent);

            if ($tplContent = $newCompiler->compile()) {
                return $tplContent;
            } else {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_TEMPLATE_COMPILER_INCLUDE_TPL_EMPTY|' . $param[0],
                    'template',
                    true
                );
            }
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_TEMPLATE_COMPILER_INCLUDE_TPL_NOTFOUND|' . $param[0],
                'template',
                true
            );
        }

        return false;
    }

    /**
     * Parser: Inject template content
     *
     * @param string $format Format string from template file
     * @param integer $pos Current position of tag
     *
     * @return mixed Return true when succeed, false otherwise
     */
    protected function doInjectArea($format, $pos)
    {
        $wrapCode = false;
        $phpcode = '';
        $formats = explode(' ', $format, 2);

        $params = new \Facula\Base\Tool\Paging\Compiler\Parameters(
            isset($formats[1]) ? $formats[1] : '',
            array(
                'wrap' => 'default',
                'empty' => 'default',
            )
        );

        if ($params->has('wrap')
        && (strpos($params->get('wrap'), '(CODE)') !== false)) {
            $wrapCode = true;
        }

        if (isset($this->pool['Injected'][$formats[0]])) {
            if (!$wrapCode) {
                foreach ($this->pool['Injected'][$formats[0]] as $code) {
                    $phpcode .= $code;
                }
            } else {
                foreach ($this->pool['Injected'][$formats[0]] as $code) {
                    $phpcode .= str_replace(
                        '(CODE)',
                        $code,
                        $params->get('wrap')
                    );
                }
            }
        } elseif ($params->get('empty')) {
            $phpcode .= $params->get('empty');
        }

        return $phpcode;
    }

    /**
     * Parser: Fill language string into template
     *
     * @param string $format Format string from template file
     * @param integer $pos Current position of tag
     *
     * @return mixed Return true when succeed, false otherwise
     */
    protected function doLanguage($format, $pos)
    {
        if (isset($this->pool['LanguageMap'][$format][0])) {
            return $this->pool['LanguageMap'][$format];
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_TEMPLATE_COMPILER_LANGUAGE_NOTFOUND|' . $format,
                'template',
                true
            );
        }

        return false;
    }

    /**
     * Parser: Set variable into template
     *
     * @param string $format Format string from template file
     * @param integer $pos Current position of tag
     *
     * @return mixed Return true when succeed, false otherwise
     */
    protected function doVariable($format, $pos)
    {
        $param = explode('|', $format);
        $phpcode = $phpBeginStr = $phpBeginDft = '';

        if (isset($param[0])) {
            if (!$this->doCheckVariableName($param[0])) {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_TEMPLATE_COMPILER_VARIABLE_NAME_INVALID|' . $param[0],
                    'template',
                    true
                );

                return false;
            }

            // Code begin and end for string
            $phpBeginStr = '<?php if (isset(' . $param[0] . '[0])) { ';
            $phpEndsStr = ' } elseif (isset(' . $param[0] . ')) { echo(' . $param[0] . '); ';

            // Code begin for other data type
            $phpBeginDft = '<?php if (isset(' . $param[0] . ')) { ';

            if (!isset($param[1])) {
                $phpcode .= $phpBeginStr
                        . 'echo(htmlspecialchars(' . $param[0] . ', ENT_QUOTES));'
                        . $phpEndsStr;
            } else {
                switch ($param[1]) {
                    case 'date':
                        if (isset($param[2])
                            && isset($this->pool['LanguageMap']['FORMAT_DATE_' . $param[2]])) {
                            $phpcode .= $phpBeginDft
                                    . 'echo(date(\''
                                    . $this->pool['LanguageMap']['FORMAT_DATE_' . $param[2]]
                                    . '\', (int)('
                                    . $param[0]
                                    . ')));';
                        } else {
                            \Facula\Framework::core('debug')->exception(
                                'ERROR_TEMPLATE_COMPILER_VARIABLE_DATE_LANG_MISSED',
                                'template',
                                true
                            );

                            return false;
                        }
                        break;

                    case 'friendlyTime':
                        if (isset($this->pool['LanguageMap']['FORMAT_TIME_DEFAULT']) &&
                            isset($this->pool['LanguageMap']['FORMAT_TIME_BEFORE_DAY']) &&
                            isset($this->pool['LanguageMap']['FORMAT_TIME_BEFORE_HR']) &&
                            isset($this->pool['LanguageMap']['FORMAT_TIME_BEFORE_MIN']) &&
                            isset($this->pool['LanguageMap']['FORMAT_TIME_BEFORE_SND']) &&
                            isset($this->pool['LanguageMap']['FORMAT_TIME_AFTER_SND']) &&
                            isset($this->pool['LanguageMap']['FORMAT_TIME_AFTER_MIN']) &&
                            isset($this->pool['LanguageMap']['FORMAT_TIME_AFTER_HR']) &&
                            isset($this->pool['LanguageMap']['FORMAT_TIME_AFTER_DAY'])) {
                            $phpcode .= $phpBeginDft
                                    . '$tempTime = $Time - (' . $param[0]. ');'
                                    // If small than 0, means after time
                                    . 'if ($tempTime < 0) { $tempTime = abs($tempTime); '

                                    . 'if ($tempTime < 60) { printf(\''
                                    . $this->pool['LanguageMap']['FORMAT_TIME_AFTER_SND']
                                    . '\', $tempTime); '

                                    . '} elseif ($tempTime < 3600) { printf(\''
                                    . $this->pool['LanguageMap']['FORMAT_TIME_AFTER_MIN']
                                    . '\', (int)($tempTime / 60)); '

                                    . '} elseif ($tempTime < 86400) { printf(\''
                                    . $this->pool['LanguageMap']['FORMAT_TIME_AFTER_HR']
                                    . '\', (int)($tempTime / 3600)); '

                                    . '} elseif ($tempTime < 604800) { printf(\''
                                    . $this->pool['LanguageMap']['FORMAT_TIME_AFTER_DAY']
                                    . '\', (int)($tempTime / 86400)); '

                                    . '} elseif ($tempTime) { echo(date(\''
                                    . $this->pool['LanguageMap']['FORMAT_TIME_DEFAULT']
                                    . '\', (int)(' . $param[0] . '))); } '

                                    . '} else { ' // Or, if larger than 0 means before

                                    . 'if ($tempTime < 60) { printf(\''
                                    . $this->pool['LanguageMap']['FORMAT_TIME_BEFORE_SND']
                                    . '\', $tempTime); '

                                    . '} elseif ($tempTime < 3600) { printf(\''
                                    . $this->pool['LanguageMap']['FORMAT_TIME_BEFORE_MIN']
                                    . '\', (int)($tempTime / 60)); '

                                    . '} elseif ($tempTime < 86400) { printf(\''
                                    . $this->pool['LanguageMap']['FORMAT_TIME_BEFORE_HR']
                                    . '\', (int)($tempTime / 3600)); '

                                    . '} elseif ($tempTime < 604800) { printf(\''
                                    . $this->pool['LanguageMap']['FORMAT_TIME_BEFORE_DAY']
                                    . '\', (int)($tempTime / 86400)); '

                                    . '} elseif ($tempTime) { echo(date(\''
                                    . $this->pool['LanguageMap']['FORMAT_TIME_DEFAULT']
                                    . '\', (int)(' . $param[0] . '))); } '

                                    . '} $tempTime = 0;';
                        } else {
                            \Facula\Framework::core('debug')->exception(
                                'ERROR_TEMPLATE_COMPILER_VARIABLE_FRIENDLYTIME_LANG_MISSED',
                                'template',
                                true
                            );

                            return false;
                        }
                        break;

                    case 'bytes':
                        if (isset($this->pool['LanguageMap']['FORMAT_FILESIZE_BYTES']) &&
                            isset($this->pool['LanguageMap']['FORMAT_FILESIZE_KILOBYTES']) &&
                            isset($this->pool['LanguageMap']['FORMAT_FILESIZE_MEGABYTES']) &&
                            isset($this->pool['LanguageMap']['FORMAT_FILESIZE_GIGABYTES']) &&
                            isset($this->pool['LanguageMap']['FORMAT_FILESIZE_TRILLIONBYTES'])) {
                            $phpcode .= $phpBeginDft
                                    . '$tempsize = '
                                    . $param[0]
                                    . '; if ($tempsize < 1024) { echo (($tempsize).\''
                                    . $this->pool['LanguageMap']['FORMAT_FILESIZE_BYTES']
                                    . '\'); } elseif ($tempsize < 1048576) {'
                                    . ' echo ((int)($tempsize / 1024).\''
                                    . $this->pool['LanguageMap']['FORMAT_FILESIZE_KILOBYTES']
                                    . '\'); } elseif ($tempsize < 1073741824) {'
                                    . ' echo (round($tempsize / 1048576, 1).\''
                                    . $this->pool['LanguageMap']['FORMAT_FILESIZE_MEGABYTES']
                                    . '\'); } elseif ($tempsize < 1099511627776) {'
                                    . ' echo (round($tempsize / 1073741824, 2).\''
                                    . $this->pool['LanguageMap']['FORMAT_FILESIZE_GIGABYTES']
                                    . '\'); } elseif ($tempsize < 1125899906842624) {'
                                    . ' echo (round($tempsize / 1099511627776, 3).\''
                                    . $this->pool['LanguageMap']['FORMAT_FILESIZE_TRILLIONBYTES']
                                    . '\'); } $tempsize = 0;';
                        } else {
                            \Facula\Framework::core('debug')->exception(
                                'ERROR_TEMPLATE_COMPILER_VARIABLE_BYTE_LANG_MISSED',
                                'template',
                                true
                            );

                            return false;
                        }
                        break;

                    case 'json':
                        $phpcode .= $phpBeginDft
                                . 'echo(json_encode('
                                . $param[0]
                                . ', JSON_HEX_QUOT | JSON_HEX_APOS'
                                . '| JSON_HEX_AMP | JSON_HEX_TAG));';
                        break;

                    case 'jsonData':
                        $phpcode .= $phpBeginDft
                                . 'echo(htmlspecialchars(json_encode(' . $param[0] . ')));';
                        break;

                    case 'urlChar':
                        $phpcode .= $phpBeginStr
                                . 'echo(urlencode(' . $param[0] . '));'
                                . $phpEndsStr;
                        break;

                    case 'slashed':
                        $phpcode .= $phpBeginStr
                                . 'echo(addslashes(' . $param[0] . '));'
                                . $phpEndsStr;
                        break;

                    case 'nl':
                        $phpcode .= $phpBeginStr
                                . 'echo(nl2br(htmlspecialchars(' . $param[0] . ', ENT_QUOTES)));'
                                . $phpEndsStr;
                        break;

                    case 'pure':
                        $phpcode .= $phpBeginStr
                                . 'echo(' . $param[0] . ');'
                                . $phpEndsStr;
                        break;

                    case 'pureNl':
                        $phpcode .= $phpBeginStr
                                . 'echo(nl2br(' . $param[0] . '));'
                                . $phpEndsStr;
                        break;

                    case 'number':
                        $phpcode .= $phpBeginDft
                                . 'echo(number_format(' . $param[0] . '));';
                        break;

                    case 'friendlyNumber':
                        if (isset($this->pool['LanguageMap']['FORMAT_NUMBER_HUNDRED']) &&
                            isset($this->pool['LanguageMap']['FORMAT_NUMBER_THOURSAND']) &&
                            isset($this->pool['LanguageMap']['FORMAT_NUMBER_MILLION']) &&
                            isset($this->pool['LanguageMap']['FORMAT_NUMBER_BILLION']) &&
                            isset($this->pool['LanguageMap']['FORMAT_NUMBER_TRILLION']) &&
                            isset($this->pool['LanguageMap']['FORMAT_NUMBER_QUADRILLION']) &&
                            isset($this->pool['LanguageMap']['FORMAT_NUMBER_QUINTILLION']) &&
                            isset($this->pool['LanguageMap']['FORMAT_NUMBER_SEXTILLION'])) {
                            $phpcode .= $phpBeginDft
                                    . 'if ('
                                    . $param[0]
                                    . ' > 1000000000000000000000) { echo(round(('
                                    . $param[0]
                                    . ' / 1000000000000000000000) , 2) . \''
                                    . $this->pool['LanguageMap']['FORMAT_NUMBER_SEXTILLION']
                                    . '\'); } elseif ('
                                    . $param[0]
                                    . ' > 1000000000000000000) { echo(round(('
                                    . $param[0]
                                    . ' / 1000000000000000000) , 2) . \''
                                    . $this->pool['LanguageMap']['FORMAT_NUMBER_QUINTILLION']
                                    . '\'); } elseif ('
                                    . $param[0]
                                    . ' > 1000000000000000) { echo(round(('
                                    . $param[0]
                                    . ' / 1000000000000000) , 2) . \''
                                    . $this->pool['LanguageMap']['FORMAT_NUMBER_QUADRILLION']
                                    . '\'); } elseif ('
                                    . $param[0]
                                    . ' > 1000000000000) { echo(round(('
                                    . $param[0]
                                    . ' / 1000000000000) , 2) . \''
                                    . $this->pool['LanguageMap']['FORMAT_NUMBER_TRILLION']
                                    . '\'); } elseif ('
                                    . $param[0]
                                    . ' > 1000000000) { echo(round(('
                                    . $param[0]
                                    . ' / 1000000000) , 2) . \''
                                    . $this->pool['LanguageMap']['FORMAT_NUMBER_BILLION']
                                    . '\'); } elseif ('
                                    . $param[0]
                                    . ' > 1000000) { echo(round(('
                                    . $param[0]
                                    . ' / 1000000) , 2) . \''
                                    . $this->pool['LanguageMap']['FORMAT_NUMBER_MILLION']
                                    . '\'); } elseif ('
                                    . $param[0]
                                    . ' > 1000) { echo(round(('
                                    . $param[0]
                                    . ' / 1000) , 2) . \''
                                    . $this->pool['LanguageMap']['FORMAT_NUMBER_THOURSAND']
                                    . '\'); } elseif ('
                                    . $param[0]
                                    . ' > 100) { echo(round(('
                                    . $param[0]
                                    . ' / 100) , 2) . \''
                                    . $this->pool['LanguageMap']['FORMAT_NUMBER_HUNDRED']
                                    . '\'); } else { echo('
                                    . $param[0]
                                    . '); }';
                        } else {
                            \Facula\Framework::core('debug')->exception(
                                'ERROR_TEMPLATE_COMPILER_VARIABLE_FRIENDLYNUMBER_LANG_MISSED',
                                'template',
                                true
                            );

                            return false;
                        }
                        break;

                    case 'floatNumber':
                        $phpcode .= $phpBeginDft
                                . 'echo(number_format('
                                . $param[0]
                                . ', '
                                . (isset($param[2]) ? (int)($param[2]) : 2)
                                . '));';
                        break;

                    default:
                        $variableName = array_shift($param);

                        $phpcode .= $phpBeginStr
                                . 'printf('
                                . 'htmlspecialchars(' . $variableName . ', ENT_QUOTES)'
                                . ', '
                                . implode(', ', $param)
                                . ');'
                                . $phpEndsStr;
                        break;
                }
            }

            $phpcode .= ' } ?>';

            return $phpcode;
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_TEMPLATE_COMPILER_VARIABLE_MUST_DEFINED',
                'template',
                true
            );
        }

        return false;
    }

    /**
     * Parser: Set page switcher into template
     *
     * @param string $format Format string from template file
     * @param integer $pos Current position of tag
     *
     * @return mixed Return true when succeed, false otherwise
     */
    protected function doPageSwitcher($format)
    {
        $formatMatched = array();
        $formatVariables = array('Search' => array(), 'Replace' => array());

        $params = new \Facula\Base\Tool\Paging\Compiler\Parameters(
            $format,
            array(
                'name' => 'default',
                'classname' => 'default',
                'current' => 'default',
                'max' => 'default',
                'total' => 'default',
                'format' => 'default',
            )
        );

        if ($params->has('name')
        && $params->has('classname')
        && $params->has('current')
        && $params->has('max')
        && $params->has('total')
        && $params->has('format')) {
            $phpcode = '<?php ';

            $name = $params->get('name');
            $classname = $params->get('classname');
            $currentpage = $params->get('current');
            $totalpage = $params->get('total');
            $maxdisplay = $params->get('max');
            $linkFormat = $params->get('format');

            $name = htmlspecialchars($name, ENT_QUOTES);
            $classname = htmlspecialchars($classname, ENT_QUOTES);

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

            $phpcode = '<?php if (' . $totalpage. ' > 1) { echo(\'<ul id="'
                    . $name . '" class="' . $classname . '">\'); if ('
                    . $totalpage . ' > 0 && ' . $currentpage . ' <= ' . $totalpage . ') { if ('
                    . $currentpage . ' > 1) echo(\'<li><a href="'
                    . str_replace('%PAGE%', '1', $linkFormat)
                    . '">&laquo;</a></li><li><a href="\' . str_replace(\'%PAGE%\', ('
                    . $currentpage . ' - 1), \'' . $linkFormat
                    . '\') . \'">&lsaquo;</a></li>\'); $loop = (int)(' . $maxdisplay
                    . ' / 2); if (' . $currentpage . ' - $loop > 0) { for ($i = '
                    . $currentpage . ' - $loop; $i <= ' . $totalpage . ' && $i <= '
                    . $currentpage . ' + $loop; $i++) { if ($i == ' . $currentpage
                    . ') { echo(\'<li class="this"><a href="\' . str_replace(\'%PAGE%\', $i, \''
                    . $linkFormat . '\'). \'">\' . $i . \'</a></li>\'); } '
                    . ' else { echo(\'<li><a href="\' . str_replace(\'%PAGE%\', $i, \''
                    . $linkFormat . '\') . \'">\' . $i . \'</a></li>\'); } } } else '
                    . '{ for ($i = 1; $i <= ' . $totalpage . ' && $i <= ' . $maxdisplay
                    . '; $i++) { if ($i == ' . $currentpage
                    . ') { echo(\'<li class="this"><a href="\' . str_replace(\'%PAGE%\', $i, \''
                    . $linkFormat . '\'). \'">\' . $i . \'</a></li>\'); } else'
                    . ' { echo(\'<li><a href="\' . str_replace(\'%PAGE%\', $i, \''
                    . $linkFormat . '\') . \'">\' . $i . \'</a></li>\'); } } } unset($loop); if ('
                    . $totalpage . ' > ' . $currentpage
                    . ') echo(\'<li><a href="\' . str_replace(\'%PAGE%\', ('
                    . $currentpage . ' + 1), \'' . $linkFormat
                    . '\') . \'">&rsaquo;</a></li><li><a href="\' . str_replace(\'%PAGE%\', ('
                    . $totalpage . '), \'' . $linkFormat
                    . '\') . \'">&raquo;</a></li>\'); } echo(\'</ul>\'); } ?>';

            $phpcode = str_replace(array("\r", "\r\n", "\t",'  '), '', $phpcode);

            return $phpcode;
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_TEMPLATE_COMPILER_PAGER_FORMAT_INVALID|' . $format,
                'template',
                true
            );
        }

        return false;
    }

    /**
     * Parser: Set loop into template
     *
     * @param string $format Format string from template file
     * @param integer $pos Current position of tag
     *
     * @return mixed Return true when succeed, false otherwise
     */
    protected function doLoop($format, $pos)
    {
        $params = explode(' ', $format);
        $matched = array();
        $phpcode = $unclosed = '';

        switch ($params[0]) {
            case 'EMPTY':
                if (isset($params[1]) && preg_match('/^([A-Za-z0-9]+)$/', $params[1], $matched)) {

                    // Check if we already opened the tag
                    if (!isset($this->tagPositionMaps['Loop:' . $params[1]]['Start'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOOP_NOT_OPEN|' . $params[1],
                            'template',
                            true
                        );

                        return false;
                    }

                    // Check if we already closed this loop
                    if (isset($this->tagPositionMaps['Loop:' . $params[1]]['End'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOOP_ALREADY_CLOSED|' . $params[1],
                            'template',
                            true
                        );

                        return false;
                    }

                    // Check if we already emptied this foreach
                    if (isset($this->tagPositionMaps['Loop:' . $params[1]]['Emptied'])
                        && $this->tagPositionMaps['Loop:' . $params[1]]['Emptied']) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOOP_ALREADY_EMPTY|' . $params[1],
                            'template',
                            true
                        );

                        return false;
                    }

                    // Well, not yet. So we can empty it right now
                    $phpcode .= '<?php } } else { ?>'; // Close foreach and Open else case

                    // Tag this loop to emptied
                    $this->tagPositionMaps['Loop:' . $params[1]]['Emptied'] = true;

                    // Save current pos to Previous for futher use
                    $this->tagPositionMaps['Loop:' . $params[1]]['Middle'][] = $pos + strlen($phpcode);

                    return $phpcode;
                } else {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_TEMPLATE_COMPILER_LOOP_FORMAT_INVALID|' . $format,
                        'template',
                        true
                    );
                }
                break;

            case 'EOF':
                if (isset($params[1]) && preg_match('/^([A-Za-z0-9]+)$/', $params[1], $matched)) {
                    if (!isset($this->tagPositionMaps['Loop:' . $params[1]]['Start'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOOP_NOT_OPEN|' . $params[1],
                            'template',
                            true
                        );

                        return false;
                    }

                    if (isset($this->tagPositionMaps['Loop:' . $params[1]]['End'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOOP_ALREADY_CLOSED|' . $params[1],
                            'template',
                            true
                        );

                        return false;
                    }

                    if (isset($this->tagPositionMaps['Loop:' . $params[1]]['Emptied'])
                        && $this->tagPositionMaps['Loop:' . $params[1]]['Emptied']) {
                        // If we have empty section in this loop

                        $phpcode .= '<?php } ?>'; // We just need to close empty one (The first if)
                    } else {
                        $phpcode .= '<?php }} ?>'; // We need to both two, the first if, and foreach;
                    }

                    // Tag this loop to ended
                    $this->tagPositionMaps['Loop:' . $params[1]]['End'] = $pos + strlen($phpcode);

                    return $phpcode;
                } else {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_TEMPLATE_COMPILER_LOOP_FORMAT_INVALID|' . $format,
                        'template',
                        true
                    );
                }
                break;

            default:
                if (preg_match('/^([A-Za-z0-9]+) (\$[A-Za-z0-9\_\'\"\[\]]+)$/', $format, $matched)) {
                    list($org, $name, $valuename) = $matched;

                    if (!isset($this->tagPositionMaps['Loop:' . $name])) {
                        $phpcode .= '<?php if (isset(' . $valuename
                                . ') &&'. ' is_array(' . $valuename . ') && !empty('
                                . $valuename . ')) { ';
                        $phpcode .= 'foreach (' . $valuename
                                . ' as $_' . $name . ' => $' . $name . ') { ?>';

                        $this->tagPositionMaps['Loop:' . $name] = array(
                            'Start' => $pos,
                            'Name' => $name,
                        );

                        return $phpcode;
                    } else {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOOP_FORMAT_EXISTED|' . $name,
                            'template',
                            true
                        );
                    }

                } else {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_TEMPLATE_COMPILER_LOOP_FORMAT_INVALID|' . $format,
                        'template',
                        true
                    );
                }
                break;
        }

        return false;
    }

    /**
     * Parser: Set logic into template
     *
     * @param string $format Format string from template file
     * @param integer $pos Current position of tag
     *
     * @return mixed Return true when succeed, false otherwise
     */
    protected function doLogic($format, $pos)
    {
        $params = explode(' ', $format, 2);
        $matched = array();
        $phpcode = $unclosed = '';

        switch ($params[0]) {
            case 'ELSEIF':
                if (isset($params[1])
                    && preg_match(
                        '/^([A-Za-z0-9]+) (.*)$/',
                        $params[1],
                        $matched
                    )) {
                    list($org, $name, $condition) = $matched;

                    if (!isset($this->tagPositionMaps['Logic:' . $name]['Start'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOGIC_NOT_OPEN|' . $name,
                            'template',
                            true
                        );

                        return false;
                    }

                    if (isset($this->tagPositionMaps['Logic:' . $name]['Elsed'])
                        && $this->tagPositionMaps['Logic:' . $name]['Elsed']) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_ELSED|' . $name,
                            'template',
                            true
                        );

                        return false;
                    }

                    if (isset($this->tagPositionMaps['Logic:' . $name]['End'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_CLOSED|' . $name,
                            'template',
                            true
                        );

                        return false;
                    }

                    $phpcode .= '<?php } elseif (' . $condition . ') { ?>';

                    $this->tagPositionMaps['Logic:' . $name]['Middle'][] = $pos + strlen($phpcode);

                    return $phpcode;
                } else {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_TEMPLATE_COMPILER_LOGIC_FORMAT_INVALID|' . $format,
                        'template',
                        true
                    );
                }
                break;

            case 'ELSE':
                if (isset($params[1])
                    && preg_match(
                        '/^([A-Za-z0-9]+)$/',
                        $params[1],
                        $matched
                    )) {
                    list($org, $name) = $matched;

                    if (!isset($this->tagPositionMaps['Logic:' . $name]['Start'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOGIC_NOT_OPEN|' . $name,
                            'template',
                            true
                        );

                        return false;
                    }

                    if (isset($this->tagPositionMaps['Logic:' . $name]['Elsed'])
                        && $this->tagPositionMaps['Logic:' . $name]['Elsed']) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_ELSED|' . $name,
                            'template',
                            true
                        );

                        return false;
                    }

                    if (isset($this->tagPositionMaps['Logic:' . $name]['End'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_CLOSED|' . $name,
                            'template',
                            true
                        );

                        return false;
                    }

                    $phpcode .= '<?php } else { ?>';

                    $this->tagPositionMaps['Logic:' . $name]['Elsed'] = true;
                    $this->tagPositionMaps['Logic:' . $name]['Middle'][] = $pos + strlen($phpcode);

                    return $phpcode;
                } else {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_TEMPLATE_COMPILER_LOGIC_FORMAT_INVALID|' . $format,
                        'template',
                        true
                    );
                }
                break;

            case 'EOF':
                if (isset($params[1]) && preg_match('/^([A-Za-z0-9]+)$/', $params[1], $matched)) {
                    list($org, $name) = $matched;

                    if (!isset($this->tagPositionMaps['Logic:' . $name]['Start'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOGIC_NOT_OPEN|' . $name,
                            'template',
                            true
                        );
                        return false;
                    }

                    if (isset($this->tagPositionMaps['Logic:' . $name]['End'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_CLOSED|' . $name,
                            'template',
                            true
                        );

                        return false;
                    }

                    $phpcode .= '<?php } ?>';

                    $this->tagPositionMaps['Logic:' . $name]['End'] = $pos + strlen($phpcode);

                    return $phpcode;
                } else {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_TEMPLATE_COMPILER_LOGIC_FORMAT_INVALID|' . $format,
                        'template',
                        true
                    );
                }
                break;

            default:
                if (preg_match('/^([A-Za-z0-9]+) (.*)$/', $format, $matched)) {
                    list($org, $name, $condition) = $matched;

                    if (!isset($this->tagPositionMaps['Logic:' . $name])) {
                        $phpcode .= '<?php if (' . $condition . ') { ?>';

                        $this->tagPositionMaps['Logic:' . $name] = array(
                            'Start' => $pos,
                            'Name' => $name,
                        );

                        return $phpcode;
                    } else {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_LOGIC_FORMAT_EXISTED|' . $name,
                            'template',
                            true
                        );
                    }

                } else {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_TEMPLATE_COMPILER_LOGIC_FORMAT_INVALID|' . $format,
                        'template',
                        true
                    );
                }
                break;
        }

        return false;
    }

    /**
     * Parser: Set case into template
     *
     * @param string $format Format string from template file
     * @param integer $pos Current position of tag
     *
     * @return mixed Return true when succeed, false otherwise
     */
    protected function doCase($format, $pos)
    {
        $params = explode(' ', $format, 2);
        $matched = array();
        $phpcode = $unclosed = '';

        switch ($params[0]) {
            case 'CASE':
                if (isset($params[1]) && preg_match(
                    '/^([A-Za-z0-9]+) (.*)$/',
                    $params[1],
                    $matched
                )) {
                    list($org, $name, $value) = $matched;

                    if (!isset($this->tagPositionMaps['Case:' . $name]['Start'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_CASE_NOT_OPEN|' . $name,
                            'template',
                            true
                        );

                        return false;
                    }

                    if (isset($this->tagPositionMaps['Case:' . $name]['End'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_CASE_ALREADY_CLOSED|' . $name,
                            'template',
                            true
                        );

                        return false;
                    }

                    $phpcode .= '<?php break; case \'' . addslashes($value) . '\': ?>';

                    $this->tagPositionMaps['Case:' . $name]['Middle'][] = $pos + strlen($phpcode);

                    return $phpcode;
                } else {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_TEMPLATE_COMPILER_CASE_FORMAT_INVALID|' . $format,
                        'template',
                        true
                    );
                }
                break;

            case 'EOF':
                if (isset($params[1]) && preg_match('/^([A-Za-z0-9]+)$/', $params[1], $matched)) {
                    list($org, $name) = $matched;

                    if (!isset($this->tagPositionMaps['Case:' . $name]['Start'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_CASE_NOT_OPEN|' . $name,
                            'template',
                            true
                        );

                        return false;
                    }

                    if (isset($this->tagPositionMaps['Case:' . $name]['End'])) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_CASE_ALREADY_CLOSED|' . $name,
                            'template',
                            true
                        );

                        return false;
                    }

                    $phpcode .= '<?php break; }} ?>';

                    $this->tagPositionMaps['Case:' . $name]['End'] = $pos + strlen($phpcode);

                    return $phpcode;
                } else {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_TEMPLATE_COMPILER_CASE_FORMAT_INVALID|' . $format,
                        'template',
                        true
                    );
                }
                break;

            default:
                if (preg_match('/^([A-Za-z0-9]+) (\$[A-Za-z0-9\_\'\"\[\]]+)$/', $format, $matched)) {
                    list($org, $name, $variable) = $matched;

                    if (!isset($this->tagPositionMaps['Case:' . $name])) {
                        $phpcode .= '<?php if (isset(' . $variable . ')) { switch ('
                                . $variable . ') { default: ?>';

                        $this->tagPositionMaps['Case:' . $name] = array(
                            'Start' => $pos,
                            'Name' => $name,
                        );

                        return $phpcode;
                    } else {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_TEMPLATE_COMPILER_CASE_FORMAT_EXISTED|' . $name,
                            'template',
                            true
                        );
                    }

                } else {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_TEMPLATE_COMPILER_CASE_FORMAT_INVALID|' . $format,
                        'template',
                        true
                    );
                }
                break;
        }

        return false;
    }
}
