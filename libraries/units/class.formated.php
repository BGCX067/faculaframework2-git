<?php

/*****************************************************************************
	Facula Framework Text Format
	
	FaculaFramework 2013 (C) Rain Lee <raincious@gmail.com>
	
	@Copyright 2013 Rain Lee <raincious@gmail.com>
	@Author Rain Lee <raincious@gmail.com>
	@Package FaculaFramework
	@Version 2.0 prototype
	
	This file is part of Facula Framework.
	
	Facula Framework is free software: you can redistribute it and/or modify
	it under the terms of the GNU Lesser General Public License as published 
	by the Free Software Foundation, version 3.
	
	Facula Framework is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Lesser General Public License for more details.
	
	You should have received a copy of the GNU Lesser General Public License
	along with Facula Framework. If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************/

class Formated {
	static private $delimiters = array(
		'Tag' => array(
			'Start' => '(',
			'End' => ')',
		),
		'Property' => array(
			'Start' => '[',
			'End' => ']',
		)
	);
	
	static private $defaults = array(
		'Tag' => array(),
	);
	
	private $tags = array();
	
	private $assign = array();
	
	private $setting = array(
		'MaxNests' => 5,
		'MaxTags' => 30,
	);
	
	private $content = '';
	
	private $rendered = '';
	
	static public function get($textContent) {
		return new self($textContent);
	}
	
	static public function getFromFile($file) {
		$fileContent = '';
		
		if (is_readable($file)) {
			if ($fileContent = file_get_contents($file)) {
				return new self($fileContent);
			}
		}
		
		return false;
	}
	
	static public function newTag($processerType, Closure $processer, Closure $assigner) {
		if (!isset(self::$defaults['Tag'][$processerType[0]])) {
			self::$defaults['Tag'][$processerType[0]] = array(
				'Processer' => $processer,
				'Assigner' => $assigner,
			);
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_FORMATED_DEFAULT_TAG_EXISTED|' . $processerType[0], 'formated', true);
		}
		
		return false;
	}
	
	public function __construct(&$textMsg = '') {
		$this->content = $textMsg;
		$this->tags = self::$defaults['Tag'];
		
		return true;
	}
	
	public function assign($processerType, $name, $value) {
		$assigner = $result = null;
		
		if (isset($this->tags[$processerType[0]])) {
			$assigner = $this->tags[$processerType[0]]['Assigner'];
			
			if ($result = $assigner($value)) {
				if (is_array($result)) {
					foreach($result AS $key => $val) {
						$this->assign[$processerType[0]][$name . '.' . $key] = $value;
					}
				} else {
					$this->assign[$processerType[0]][$name] = $result;
				}
			}
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_FORMATED_TAG_NOT_EXISTE|' . $processerType[0], 'formated', true);
		}
		
		return false;
	}
	
	public function addTag($processerType, Closure $processer, Closure $assigner) {
		$this->tags[$processerType[0]] = array(
			'Processer' => $processer,
			'Assigner' => $assigner,
		);
		
		return true;
	}
	
	public function render(&$error = '') {
		if ($this->parseTags($error)) {
			return $this->content;
		}
		
		return false;
	}
	
	private function parseTags(&$error = array()) {
		$tagMap = $splitedContent = array();
		$splitedContentLens = $errorOffset = 0;
		
		if ($tagMap = $this->scanTags($error)) {
			foreach($tagMap['Flat'] AS $tag) {
				if (isset($tag['Data'])) {
					$error['Tag'] = $tag['Tag'];
					
					if ($tag['Data']['T.T.End']) {
						$errorOffset = $tag['Positions']['Tag']['Start'];
						
						$error['Error'] = 'SYNTAX_ERROR_TAG_UNCLOSE';
						$error['Arg'] = array(
							'Remain' => $tag['Data']['D.T'],
						);
					} elseif ($tag['Data']['D.P.End']) {
						$errorOffset = $tag['Positions']['Property']['Start'];
						
						$error['Error'] = 'SYNTAX_ERROR_PROPERTY_UNCLOSE';
						$error['Arg'] = array(
							'Remain' => $tag['Data']['D.P'],
						);
					}
					
					$splitedContent = explode("\n", substr($this->content, 0, $errorOffset));
					$splitedContentLens = count($splitedContent);
					
					$error['Arg']['Line'] = $splitedContentLens;
					
					unset($splitedContent[$splitedContentLens - 1]);
					$error['Arg']['Char'] = $errorOffset - strlen(implode("\n", $splitedContent));
					
					return false;
				}
			}
			
			if (!$error) {
				return $this->walkTags($tagMap['Dim'], $tagMap['Flat']);
			}
		}
		
		return false;
	}
	
	private function walkTags(array &$tagMap, array &$tagFlatMap) {
		$newValue = $processer = $newConBefore = $newConAfter = $tempValue = null;
		$tagPos = $tempData = array();
		
		$paramValue = $paramParam = '';
		
		foreach($tagMap AS $key => $val) {
			if (isset($val['Subs'])) {
				$this->walkTags($tagMap[$key]['Subs'], $tagFlatMap);
			}
			
			if (isset($this->tags[$val['Tag']])) {
				$tagPos = array();
				
				$tagPos['TagStart'] = $tagMap[$key]['TagStart'];
				$tagPos['FullStart'] = $tagPos['TagStart'] - 1;
				
				if ($tagMap[$key]['PropertyStart'] && $tagMap[$key]['PropertyEnd']) {
					$tagPos['TagEnd'] = $tagMap[$key]['TagEnd'];
					
					$tagPos['PropertyStart'] = $tagMap[$key]['PropertyStart'];
					$tagPos['FullEnd'] = ($tagPos['PropertyEnd'] = $tagMap[$key]['PropertyEnd']) + 1;
					
					$tagPos['PropertyLen'] = $tagPos['PropertyEnd'] - $tagPos['PropertyStart'];
					
					$tagPos['Param'] = substr($this->content, $tagPos['PropertyStart'] + 1, $tagPos['PropertyLen'] - 1);
				} else {
					$tagPos['FullEnd'] = ($tagPos['TagEnd'] = $tagMap[$key]['TagEnd']) + 1;
					
					$tagPos['PropertyLen'] = $tagPos['PropertyStart'] = $tagPos['PropertyEnd'] = 0;
					$tagPos['Param'] = null;
				}
				
				$tagPos['TagLen'] = $tagPos['TagEnd'] - $tagPos['TagStart'];
				$tagPos['FullLen'] = $tagPos['FullEnd'] - $tagPos['FullStart'];
				$tagPos['Value'] = substr($this->content, $tagPos['TagStart'] + 1, $tagPos['TagLen'] - 1);
				
				$processer = $this->tags[$val['Tag']]['Processer'];
				
				if (isset($this->assign[$val['Tag']][$tagPos['Value']])) {
					$tempValue = &$this->assign[$val['Tag']][$tagPos['Value']];
				} else {
					$tempValue = array();
				}
				
				if ($newValue = $processer($tempValue, $tagPos['Param'], $this->assign)) {
					$this->content = substr($this->content, 0, $tagPos['FullStart']) . $newValue . substr($this->content, $tagPos['FullEnd'], strlen($this->content));
					
					$newLen = strlen($newValue);
					
					if ($tagPos['FullLen'] != $newLen) {
						$tagNewEnd = $newLen - $tagPos['FullLen'];
						
						foreach($tagFlatMap AS $posModifyKey => $posModifyVal) {
							if ($key != $posModifyKey) {
								if ($posModifyVal['TagStart'] >= $tagPos['TagStart']) {
									$tagFlatMap[$posModifyKey]['TagStart'] += $tagNewEnd;
								}
								
								if ($posModifyVal['TagEnd'] >= $tagPos['TagEnd']) {
									$tagFlatMap[$posModifyKey]['TagEnd'] += $tagNewEnd;
								}
								
								if ($posModifyVal['PropertyStart'] >= $tagPos['PropertyStart']) {
									$tagFlatMap[$posModifyKey]['PropertyStart'] += $tagNewEnd;
								}
								
								if ($posModifyVal['PropertyEnd'] >= $tagPos['PropertyEnd']) {
									$tagFlatMap[$posModifyKey]['PropertyEnd'] += $tagNewEnd;
								}
							}
						}
					}
				}
			}
		}
		
		return true;
	}
	
	private function scanTags(&$error = '') {
		$lastCharOffset = $nextCharOffset = $tagID = $contentLen = 0;
		$lastNests = $tagInfos = $tagInfo = $positionMap = $tags = $keyPosMarks = array();
		$lastNested = null;
		$remainTags = $this->setting['MaxTags'];
		
		$keyChars = array_keys($this->tags);
		
		// Add tag start and end char to key
		$keyChars[] = self::$delimiters['Tag']['Start'];
		$keyChars[] = self::$delimiters['Tag']['End'];
		
		// Add property start and end to key
		$keyChars[] = self::$delimiters['Property']['Start'];
		$keyChars[] = self::$delimiters['Property']['End'];
		
		// Scan all key mark from the content
		foreach ($keyChars AS $keyChar) {
			$lastKeyPos = $lastPickUpKeyPos = 0;
			
			while(($lastKeyPos = strpos($this->content, $keyChar, $lastPickUpKeyPos)) !== false) {
				$keyPosMarks[] = $lastKeyPos;
				$lastPickUpKeyPos = $lastKeyPos + 1;
			}
		}
		
		asort($keyPosMarks);
		
		$contentLen = strlen($this->content);
		
		foreach($keyPosMarks AS $charOffset) {
			$lastCharOffset = $charOffset > 0 ? $charOffset - 1 : 0;
			$nextCharOffset = $charOffset < $contentLen ? $charOffset + 1 : $contentLen;
			
			switch($this->content[$charOffset]) {
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
							
							if ($nextCharOffset != $charOffset && $this->content[$nextCharOffset] == self::$delimiters['Property']['Start']) {
								$lastNested['Data']['T.P.Start'] = true;
							} else {
								unset($lastNested['Data'], $lastNests[$tagID--]);
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
								$lastNested['Positions']['Property']['End'] = $charOffset;
								
								unset($lastNested['Data'], $lastNests[$tagID--]);
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
							$lastNested = &$lastNests[$tagID];
						}
					}
					break;
					
				default:
					if (isset($this->tags[$this->content[$charOffset]]) && $this->content[$nextCharOffset] == self::$delimiters['Tag']['Start']) {
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
					
						$tagInfo = array(
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
							$tagInfos['Dim'][$charOffset] = $tagInfo;
							
							$lastNests[++$tagID] = &$tagInfos['Dim'][$charOffset];
							$tagInfos['Flat'][$charOffset] = &$tagInfos['Dim'][$charOffset];
							
							$lastNested = &$lastNests[$tagID];
						} else {
							if (isset($lastNested['Data']['Level'])) {
								$tagInfo['Data']['Level'] = $lastNested['Data']['Level'] + 1;
							} else {
								$tagInfo['Data']['Level'] = 1;
							}
							
							if ($tagInfo['Data']['Level'] <= $this->setting['MaxNests']) {
								$lastNested['Subs'][$charOffset] = $tagInfo;
								
								$lastNests[++$tagID] = &$lastNested['Subs'][$charOffset];
								$tagInfos['Flat'][$charOffset] = &$lastNested['Subs'][$charOffset];
								
								$lastNested = &$lastNests[$tagID];
							}
						}
					}
					break;
			}
		}
		
		return $tagInfos;
	}
}

Formated::newTag('%',
	function($value, $param, $pool) {
		return $value;
	},
	function($value) {
		$result = array();
		
		if (is_array($value)) {
			foreach($value AS $key => $val) {
				$result[$key] = $val;
			}
		} else {
			return $value;
		}

		return $result;
	}
);

Formated::newTag('!',
	function($value, $param, $pool) {
		return $value . " ({$param})";
	},
	function($value) {
		return $value;
	}
);

?>