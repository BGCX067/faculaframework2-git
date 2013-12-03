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
error_reporting(E_ALL | E_STRICT);
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
	
	private $tagMap = array();
	
	private $assign = array();
	private $processers = array();
	
	private $setting = array(
		'MaxNests' => 5,
	);
	
	private $content = '';
	
	private $rendered = '';
	
	static public function newTag($processerType, Closure $processer) {
		if (!isset(self::$defaults['Tag'][$processerType])) {
			self::$defaults['Tag'][$processerType] = $processer;
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_FORMATED_DEFAULT_TAG_EXISTED|' . $processerType, 'formated', true);
		}
		
		return false;
	}
	
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
	
	public function __construct(&$textMsg = '') {
		$this->content = $textMsg;
		$this->tags = self::$defaults['Tag'];
		
		return true;
	}
	
	public function assign($processerType, $name, $value) {
		if (isset($this->tags[$processerType])) {
			$this->assign[$processerType][$name] = $value;
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_FORMATED_TAG_NOT_EXISTE|' . $processerType, 'formated', true);
		}
		
		return false;
	}
	
	public function addTag($processerType, Closure $processer) {
		$this->processers[$processerType] = $processer;
		
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
		
		if ($tagMap = $this->scanTags()) {
			foreach($tagMap['Flat'] AS $tag) {
				if (isset($tag['Runtime'])) {
					$error['Tag'] = $tag['Tag'];
					
					if ($tag['Runtime']['Triggers']['Tag']['End']) {
						$errorOffset = $tag['Positions']['Tag']['Start'];
						
						$error['Error'] = 'SYNTAX_ERROR_TAG_UNCLOSE';
						$error['Arg'] = array(
							'Remain' => $tag['Runtime']['Dismisser']['Tag'],
						);
					} elseif ($tag['Runtime']['Triggers']['Property']['End']) {
						$errorOffset = $tag['Positions']['Property']['Start'];
						
						$error['Error'] = 'SYNTAX_ERROR_PROPERTY_UNCLOSE';
						$error['Arg'] = array(
							'Remain' => $tag['Runtime']['Dismisser']['Property'],
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
		$newValue = $processer = $newConBefore = $newConAfter = null;
		$tagPos = array();
		
		$paramValue = $paramParam = '';
		
		foreach($tagMap AS $key => $val) {
			if (isset($val['Subs'])) {
				$this->walkTags($tagMap[$key]['Subs'], $tagFlatMap);
			}
			
			if (isset($this->tags[$val['Tag']])) {
				$tagPos = array();
				
				$tagPos['FullStart'] = $tagPos['TagStart'] = $tagMap[$key]['Positions']['Tag']['Start'];
				
				if ($tagMap[$key]['Positions']['Property']['Start'] && $tagMap[$key]['Positions']['Property']['End']) {
					$tagPos['TagEnd'] = $tagMap[$key]['Positions']['Tag']['End'];
					
					$tagPos['PropertyStart'] = $tagMap[$key]['Positions']['Property']['Start'];
					$tagPos['FullEnd'] = ($tagPos['PropertyEnd'] = $tagMap[$key]['Positions']['Property']['End']) + 1;
					
					$tagPos['PropertyLen'] = $tagPos['PropertyEnd'] - $tagPos['PropertyStart'];
					
					$tagPos['Param'] = substr($this->content, $tagPos['PropertyStart'] + 1, $tagPos['PropertyLen'] - 1);
				} else {
					$tagPos['FullEnd'] = ($tagPos['TagEnd'] = $tagMap[$key]['Positions']['Tag']['End']) + 1;
					
					$tagPos['PropertyLen'] = $tagPos['PropertyStart'] = $tagPos['PropertyEnd'] = 0;
					
					$tagPos['Param'] = null;
				}
				
				$tagPos['TagLen'] = $tagPos['TagEnd'] - $tagPos['TagStart'];
				
				$tagPos['FullLen'] = $tagPos['FullEnd'] - $tagPos['FullStart'];
				
				$tagPos['Value'] = substr($this->content, $tagPos['TagStart'] + 1, $tagPos['TagLen'] - 1);
				
				$processer = $this->tags[$tagMap[$key]['Tag']];
				if ($newValue = $processer($tagPos['Value'], $tagPos['Param'])) {
					$this->content = substr($this->content, 0, $tagPos['FullStart']) . $newValue . substr($this->content, $tagPos['FullEnd'], strlen($this->content));
					
					$newLen = strlen($newValue);
					
					if ($tagPos['FullLen'] != $newLen) {
						$tagNewEnd = $newLen - $tagPos['FullLen'];
						
						foreach($tagFlatMap AS $posModifyKey => $posModifyVal) {
							if ($key != $posModifyKey) {
								if ($posModifyVal['Positions']['Tag']['Start'] >= $tagPos['TagStart']) {
									$tagFlatMap[$posModifyKey]['Positions']['Tag']['Start'] += $tagNewEnd;
								}
								
								if ($posModifyVal['Positions']['Tag']['End'] >= $tagPos['TagEnd']) {
									$tagFlatMap[$posModifyKey]['Positions']['Tag']['End'] += $tagNewEnd;
								}
								
								if ($posModifyVal['Positions']['Property']['Start'] >= $tagPos['PropertyStart']) {
									$tagFlatMap[$posModifyKey]['Positions']['Property']['Start'] += $tagNewEnd;
								}
								
								if ($posModifyVal['Positions']['Property']['End'] >= $tagPos['PropertyEnd']) {
									$tagFlatMap[$posModifyKey]['Positions']['Property']['End'] += $tagNewEnd;
								}
							}
						}
					}
				}
			}
		}
		
		return true;
	}
	
	private function scanTags() {
		$lastCharOffset = $nextCharOffset = $tagID = $lastMarkMapPos = $lastMarkMapFindPos = $contentLen = 0;
		$lastNests = $tagInfos = $tagInfo = $positionMap = $tags = array();
		$lastNested = null;

		$contentLen = strlen($this->content);
		
		for ($offset = 0; $offset < $contentLen; $offset++) {
			$lastCharOffset = $offset > 0 ? $offset - 1 : 0;
			$nextCharOffset = $offset < $contentLen ? $offset + 1 : $contentLen;
			
			switch($this->content[$offset]) {
				case self::$delimiters['Tag']['Start']:
					if (isset($lastNested['Runtime'])) {
						$lastNested['Runtime']['Dismisser']['Tag']++;
						
						if ($lastNested['Runtime']['Triggers']['Tag']['Start']) {
							if ($lastNested['Runtime']['CurrentOffset'] == $lastCharOffset) {
								$lastNested['Positions']['Tag']['Start'] = $offset;
								
								$lastNested['Runtime']['Triggers']['Tag']['End'] = true;
								$lastNested['Runtime']['Triggers']['Tag']['Start'] = false;
							} else {
								unset($lastNested['Runtime'], $lastNests[$tagID--]);
								$lastNested = &$lastNests[$tagID];
							}
						}
					}
					break;
					
				case self::$delimiters['Tag']['End']:
					if (isset($lastNested['Runtime']) && $lastNested['Runtime']['Triggers']['Tag']['End']) {
						if (--$lastNested['Runtime']['Dismisser']['Tag'] == 0) {
							$lastNested['Positions']['Tag']['End'] = $offset;
							
							$lastNested['Runtime']['Triggers']['Tag']['End'] = false;
							
							if ($nextCharOffset != $offset && $this->content[$nextCharOffset] == self::$delimiters['Property']['Start']) {
								$lastNested['Runtime']['Triggers']['Property']['Start'] = true;
							} else {
								unset($lastNested['Runtime'], $lastNests[$tagID--]);
								$lastNested = &$lastNests[$tagID];
							}
						}
					}
					break;
					
				case self::$delimiters['Property']['Start']:
					if (isset($lastNested['Runtime'])) {
						$lastNested['Runtime']['Dismisser']['Property']++;
						
						if ($lastNested['Runtime']['Triggers']['Property']['Start']) {
							$lastNested['Positions']['Property']['Start'] = $offset;
							
							$lastNested['Runtime']['Triggers']['Property']['Start'] = false;
							
							if ($this->content[$lastCharOffset] == self::$delimiters['Tag']['End']) {
								$lastNested['Runtime']['Triggers']['Property']['End'] = true;
							} else {
								// If something went wrong, Close this tag
								$lastNested['Positions']['Property']['End'] = $offset;
								
								unset($lastNested['Runtime'], $lastNests[$tagID--]);
								$lastNested = &$lastNests[$tagID];
							}
						}
					}
					break;
					
				case self::$delimiters['Property']['End']:
					if (isset($lastNested['Runtime']) && $lastNested['Runtime']['Triggers']['Property']['End']) {
						if (--$lastNested['Runtime']['Dismisser']['Property'] == 0) {
							$lastNested['Positions']['Property']['End'] = $offset;
							
							$lastNested['Runtime']['Triggers']['Property']['End'] = false;
							
							unset($lastNested['Runtime'], $lastNests[$tagID--]);
							$lastNested = &$lastNests[$tagID];
						}
					}
					break;
					
				default:
					if (isset($this->tags[$this->content[$offset]])) {
						$tagInfo = array(
							'Runtime' => array(
								'Triggers' => array(
									'Tag' => array(
										'Start' => true,
										'End' => false,
									),
									'Property' => array(
										'Start' => false,
										'End' => false,
									),
								),
								'Dismisser' => array(
									'Tag' => 0,
									'Property' => 0,
								),
								'CurrentOffset' => $offset,
							),
							'Tag' => $this->content[$offset],
							'Positions' => array(
								'Tag' => array(
									'Start' => 0,
									'End' => 0,
								),
								'Property' => array(
									'Start' => 0,
									'End' => 0,
								),
							),

						);
						
						if (is_null($lastNested)) {
							$tagInfos['Dim'][$offset] = $tagInfo;
							
							$lastNests[++$tagID] = &$tagInfos['Dim'][$offset];
							$tagInfos['Flat'][$offset] = &$tagInfos['Dim'][$offset];
							
							$lastNested = &$lastNests[$tagID];
						} else {
							if (isset($lastNested['Runtime']['Level'])) {
								$tagInfo['Runtime']['Level'] = $lastNested['Runtime']['Level'] + 1;
							} else {
								$tagInfo['Runtime']['Level'] = 1;
							}
							
							if ($tagInfo['Runtime']['Level'] <= $this->setting['MaxNests']) {
								$lastNested['Subs'][$offset] = $tagInfo;
								
								$lastNests[++$tagID] = &$lastNested['Subs'][$offset];
								$tagInfos['Flat'][$offset] = &$lastNested['Subs'][$offset];
								
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


Formated::newTag('%', function($value, $param) {
	return "[New %{$value}:{$param}]";
});

Formated::newTag('!', function($value, $param) {
	return "[New !{$value}:{$param}]";
});

Formated::newTag('*', function($value, $param) {
	return "[New *{$value}:{$param}]";
});


?>