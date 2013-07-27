<?php

/*****************************************************************************
	Facula Framework HTML Template Render
	
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

interface faculaTemplateInterface {
	public function _inited();
	public function assign($key, $val);
	public function inject($key, $templatecontent);
	public function render($templateName, $templateSet = '', $expire = null, $expiredCallback = null, $cacheFactor = '');
	public function importTemplateFile($name, $path);
	public function importLanguageFile($languageCode, $path);
	public function getLanguageString($key);
}

class faculaTemplate extends faculaCoreFactory {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static public function checkInstance($instance) {
		if ($instance instanceof faculaTemplateInterface) {
			return true;
		} else {
			throw new Exception('Facula core ' . get_class($instance) . ' needs to implements interface \'faculaTemplateInterface\'');
		}
		
		return  false;
	}
}

class faculaTemplateDefault implements faculaTemplateInterface {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static private $setting = array(
		'TemplateFileSafeCode' => array(
			'<?php if (!defined(\'IN_FACULA\')) {exit(\'Access Denied\');} ',
			' ?>',
		)
	);
	
	private $response = null;
	
	private $configs = array();
	
	private $pool = array();
	
	private $assigned = array();
	
	public function __construct(&$cfg, $common, facula $facula) {
		$files = $fileNameSplit = array();
		// General settings
		$this->configs = array(
			'Cache' => isset($cfg['CacheTemplate']) && $cfg['CacheTemplate'] ? true : false,
			'Compress' => isset($cfg['CompressOutput']) && $cfg['CompressOutput'] ? true : false,
			'Renew' => isset($cfg['ForceRenew']) && $cfg['ForceRenew'] ? true : false
		);
	
		// TemplatePool 
		if (isset($cfg['TemplatePool'][0]) && is_dir($cfg['TemplatePool'])) {
			$this->configs['TplPool'] = $cfg['TemplatePool'];
		} else {
			throw new Exception('TemplatePool must be defined and existed.');
		}
		
		// CompiledTemplate 
		if (isset($cfg['CompiledTemplate'][0]) && is_dir($cfg['CompiledTemplate'])) {
			$this->configs['Compiled'] = $cfg['CompiledTemplate'];
		} else {
			throw new Exception('CompiledTemplate must be defined and existed.');
		}
		
		if ($this->configs['Cache']) {
			if (isset($cfg['CachePath']) && is_dir($cfg['CachePath'])) {
				$this->configs['Cached'] = $cfg['CachePath'];
			} else {
				throw new Exception('CachePath must be defined and existed.');
			}
		}
		
		// Scan for template files
		if ($files = $facula->scanModuleFiles($this->configs['TplPool'])) {
			foreach($files AS $file) {
				$fileNameSplit = explode('+', $file['Name'], 2);
				
				switch($file['Prefix']) {
					case 'language':
						$this->pool['File']['Lang'][$fileNameSplit[0]][] = $file['Path'];
						break;
						
					case 'template':
						if (isset($fileNameSplit[1])) { // If this is a ab testing file
							if (!isset($this->pool['File']['Tpl'][$fileNameSplit[0]][$fileNameSplit[1]])) {
								$this->pool['File']['Tpl'][$fileNameSplit[0]][$fileNameSplit[1]] = $file['Path'];
							} else {
								throw new Exception('Template file ' . $this->pool['File']['Tpl'][$fileNameSplit[0]][$fileNameSplit[1]] . ' conflicted with ' . $file['Path'] . '.');
								return false;
							}
						} elseif (!isset($this->pool['File']['Tpl'][$file['Name']]['default'])) { // If not, save current file to the default
							$this->pool['File']['Tpl'][$file['Name']]['default'] = $file['Path'];
						} else {
							throw new Exception('Template file ' . $this->pool['File']['Tpl'][$file['Name']]['default'] . ' conflicted with ' . $file['Path'] . '.');
							return false;
						}
						break;
				}
			}
			
			if (!isset($this->pool['File']['Lang']['default'])) {
				throw new Exception('Default file for language (language.default.txt) must be defined.');
			}
		}
		
		$cfg = null;
		unset($cfg);
		
		return true;
	}
	
	public function _inited() {
		$error = '';
		
		// Determine what language can be used for this client
		$siteLanguage = facula::core('request')->getClientInfo('languages');
		
		if (isset($this->pool['File']['Lang'])) {
			$clientLanguage = array_keys($this->pool['File']['Lang']);
			
			$selectedLanguage = array_values(array_intersect($siteLanguage, $clientLanguage)); // Use $siteLanguage as the first param so we can follow clients priority
		}

		if (isset($selectedLanguage[0][0])) {
			$this->pool['Language'] = $selectedLanguage[0];
		} else {
			$this->pool['Language'] = 'default';
		}
		
		// Set Essential assign value
		$this->assigned['Time'] = FACULA_TIME;
		$this->assigned['RootURL'] = facula::core('request')->getClientInfo('rootURL');
		$this->assigned['AbsRootURL'] = facula::core('request')->getClientInfo('absRootURL');
		$this->assigned['Message'] = array();
		
		facula::core('object')->runHook('template_inited', array(), $error);
		
		return true;
	}
	
	public function assign($key, $val) {
		if (!isset($this->assigned[$key])) {
			$this->assigned[$key] = $val;
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_ASSIGN_KEY_EXISTED', 'template', true);
		}
		
		return false;
	}
	
	public function inject($key, $templatecontent) {
		$this->pool['Injected'][$key][] = $templatecontent;
		
		return true;
	}
	
	public function insertMessage($message) {
		if (!empty($message)) {
			if (isset($message['Code'])) {
				if (isset($message['Args'])) {
					$msgString = vsprintf($this->getLanguageString('MESSAGE_' . $message['Code']), $message['Args']);
				} else {
					$msgString = $this->getLanguageString('MESSAGE_' . $message['Code']);
				}
			} elseif (isset($message['Message'])) {
				$msgString = $message['Message'];
			} else {
				facula::core('debug')->exception('ERROR_TEMPLATE_MESSAGE_NOCONTENT', 'template', true);
				return false;
			}
			
			$messageContent = array(
				'Message' => $msgString ? $msgString : 'ERROR_UNKNOWN_ERROR',
				'Type' => isset($message['Type']) ? $message['Type'] : 'UNKNOWN'
			);
			
			if (isset($message['Name'])) {
				$this->assigned['Message'][$message['Name']][] = $messageContent;
			} else {
				$this->assigned['Message']['Default'][] = $messageContent;
			}
			
			return true;
		}
		
		return false;
	}
	
	public function render($templateName, $templateSet = '', $expire = null, $expiredCallback = null, $cacheFactor = '') {
		$templatePath = $content = '';
		
		if ($expire === null || $expiredCallback) { // If $expire not null or $expiredCallback not set, means, this is a cache call
			if ($templatePath = $this->getCacheTemplate($templateName, $templateSet, $expire, $expiredCallback, $cacheFactor)) {
				return $this->doRender($templatePath);
			}
		} else { // Or it just a normal call
			if ($templatePath = $this->getCompiledTemplate($templateName, $templateSet)) {
				return $this->doRender($templatePath);
			}
		}
		
		return false;
	}
	
	private function getCacheTemplate($templateName, $templateSet = '', $expire = null, $expiredCallback = null, $cacheFactor = '') {
		$templatePath = $templateContent = $cachedPagePath = $cachedPageRoot = $cachedPageFactor = $cachedPageFile = $cachedPageFactorDir = $cachedTmpPage = $renderCachedContent = $renderCachedOutputContent = '';
		$splitedCompiledContentIndexLen = $splitedRenderedContentLen = 0;
		$splitedCompiledContent = $splitedRenderedContent = array();
		
		if (isset($this->configs['Cached'][0])) {
			$cachedPageFactor = !$cacheFactor ? 'default' : str_replace(array('/', '\\', '|'), '#', $cacheFactor);
			$cachedPageFactorDir = !$cacheFactor ? 'default' : $this->getCacheSubPath($cacheFactor);
			
			$cachedPageRoot = $this->configs['Cached'] . DIRECTORY_SEPARATOR . $cachedPageFactorDir . DIRECTORY_SEPARATOR;
			$cachedPageFile = 'cachedPage.' . $templateName . ($templateSet ? '+' . $templateSet : '') . '.' . $this->pool['Language'] . '.' . $cachedPageFactor. '.php';
			$cachedPagePath = $cachedPageRoot . $cachedPageFile;
			
			if (is_readable($cachedPagePath) && (!$expire || filemtime($cachedPagePath) > FACULA_TIME - $expire)) {
				return $cachedPagePath;
			} else {
				if ($templatePath = $this->getCompiledTemplate($templateName, $templateSet)) {
					if ($expiredCallback && is_callable($expiredCallback) && !$expiredCallback()) {
						return false;
					}
					
					if ($templateContent = file_get_contents($templatePath)) {
						// Spilt using no cache 
						$splitedCompiledContent = explode('<!-- NOCACHE -->', $templateContent);
						$splitedCompiledContentIndexLen = count($splitedCompiledContent) - 1;
						
						// Deal with area which need to be cached
						foreach($splitedCompiledContent AS $key => $val) {
							if ($key > 0 && $key < $splitedCompiledContentIndexLen && $key%2) {
								$splitedCompiledContent[$key] = '<?php echo(stripslashes(\'' . addslashes($val) . '\')); ?>';
							}
						}
						
						// Reassembling compiled content;
						$compiledContentForCached = implode('<!-- NOCACHE -->', $splitedCompiledContent);
						
						// Save compiled content to a temp file
						unset($templateContent, $splitedCompiledContent, $splitedCompiledContentIndexLen);
						
						if (is_dir($cachedPageRoot) || mkdir($cachedPageRoot, 0, true)) {
							$cachedTmpPage = $cachedPagePath . '.temp.php';
							
							if (file_put_contents($cachedTmpPage, $compiledContentForCached)) {
								$render = new faculaTemplateDefaultRender($cachedTmpPage, $this->assigned);
								
								// Render nocached compiled content
								if (($renderCachedContent = $render->getResult()) && unlink($cachedTmpPage)) {
									/*
										Beware the renderCachedContent as it may contains code that assigned by user. After render and cache, the php code may will 
										turn to executable.
										
										Web ui designer should filter those code to avoid danger by using compiler's variable format, but they usually know nothing 
										about how to keep user input safe.
										
										So: belowing code will help you to filter those code if the web ui designer not filter it by their own.
									*/
									$splitedRenderedContent = explode('<!-- NOCACHE -->', $renderCachedContent);
									$splitedRenderedContentLen = count($splitedRenderedContent) - 1;
									
									foreach($splitedRenderedContent AS $key => $val) {
										if (!($key > 0 && $key < $splitedRenderedContentLen && $key%2)) { // Inverse as above to tag and select cached area.
											$splitedRenderedContent[$key] = str_replace(array('<?', '?>'), array('&lt;?', '?&gt;'), $val); // Replace php code tag to unexecutable tag before save file.
										}
									}
									
									$renderCachedOutputContent = self::$setting['TemplateFileSafeCode'][0] . self::$setting['TemplateFileSafeCode'][1] . implode('', $splitedRenderedContent);
									
									unset($splitedRenderedContent, $splitedRenderedContentLen);
									
									if (file_put_contents($cachedPagePath, $renderCachedOutputContent)) {
										return $cachedPagePath;
									}
								}
							}
						}
					}
				}
			}
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_CACHE_DISABLED', 'template', true);
		}
		
		return false;
	}
	
	private function getCacheSubPath($cacheName) {
		$current = 0;
		$path = array();
		
		$path[] = $current = abs(crc32($cacheName));
		
		while(1) {
			if ($current > 1024) {
				$path[] = $current = intval($current / 1024);
			} else {
				break;
			}
		}
		
		return implode(DIRECTORY_SEPARATOR, $path);
	}
	
	// Get template and compile it to PHP code if needed
	private function getCompiledTemplate($templateName, $templateSet) {
		$content = $error = $templatePath = '';
		$compiledTpl = $this->configs['Compiled'] . DIRECTORY_SEPARATOR . 'compiledTemplate.' . $templateName . ($templateSet ? '+' . $templateSet : '') . '.' . $this->pool['Language'] . '.php';
		
		if (!$this->configs['Renew'] && is_readable($compiledTpl)) {
			facula::core('object')->runHook('template_render_*', array(), $error);
			facula::core('object')->runHook('template_render_' . $templateName, array(), $error);
		
			return $compiledTpl;
		} else {
			facula::core('object')->runHook('template_compile_*', array(), $error);
			facula::core('object')->runHook('template_compile_' . $templateName, array(), $error);
			
			if ($templateSet && isset($this->pool['File']['Tpl'][$templateName][$templateSet])) {
				$templatePath = $this->pool['File']['Tpl'][$templateName][$templateSet];
			} elseif (isset($this->pool['File']['Tpl'][$templateName]['default'])) {
				$templatePath = $this->pool['File']['Tpl'][$templateName]['default'];
			} else {
				facula::core('debug')->exception('ERROR_TEMPLATE_NOTFOUND|' . $templateName, 'template', true);
				
				return false;
			}
			
			if ($this->doCompile($templatePath, $compiledTpl)) {
				return $compiledTpl;
			}
		}
		
		return false;
	}
	
	public function importTemplateFile($name, $path, $templateSet = 'default') {
		if (!isset($this->pool['File']['Tpl'][$name][$templateSet])) {
			$this->pool['File']['Tpl'][$name][$templateSet] = $path;
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_IMPORT_TEMPLATE_EXISTED|' . $name, 'template', true);
		}
		
		return false;
	}
	
	public function importLanguageFile($languageCode, $path) {
		if (isset($this->pool['File']['Lang'][$languageCode])) {
			$this->pool['File']['Lang'][$languageCode][] = $path;
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_IMPORT_LANGUAGE_UNSPPORTED|' . $name, 'template', true);
		}
		
		return false;
	}
	
	public function getLanguageString($key) {
		if (!isset($this->pool['LanguageMap'])) {
			$this->loadLangMap();
		}
		
		if (isset($this->pool['LanguageMap'][$key])) {
			return $this->pool['LanguageMap'][$key];
		} else {
			return $key;
		}
		
		return false;
	}
	
	/* Load setting for compiling */
	private function doRender(&$compiledTpl) {
		$render = new faculaTemplateDefaultRender($compiledTpl, $this->assigned);
		
		return $render->getResult();
	}
	
	private function doCompile($sourceTpl, $resultTpl) {
		$sourceContent = $compiledContent = '';
		
		if (!isset($this->pool['LanguageMap'])) {
			$this->loadLangMap();
		}
		
		if ($sourceContent = trim(file_get_contents($sourceTpl))) {
			$compiler = new faculaTemplateDefaultCompiler($this->pool, $sourceContent);
			
			if ($compiledContent = $compiler->compile()) {
				if ($this->configs['Compress']) {
					$compiledContent = str_replace(array('  ', "\r", "\n", "\t"), '', $compiledContent);
				}
				
				return file_put_contents($resultTpl, self::$setting['TemplateFileSafeCode'][0] . self::$setting['TemplateFileSafeCode'][1] . $compiledContent);
			} else {
				facula::core('debug')->exception('ERROR_TEMPLATE_COMPILE_FAILED|' . $sourceTpl, 'template', true);
			}
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_COMPILE_OPEN_FAILED|' . $sourceTpl, 'template', true);
		}
		
		return false;
	}
	
	private function loadLangMap() {
		$this->pool['LanguageMap'] = $langMap = $langMapPre = $langMapTemp = array(); // Set LanguageMap first, because we need to tell application, we already tried to get lang file so it will not waste time retrying it.
		
		$compiledLangFile = $this->configs['Compiled'] . DIRECTORY_SEPARATOR . 'compiledLanguage.' . $this->pool['Language'] . '.php';
		
		$langContent = '';
		
		if (!$this->configs['Renew'] && is_readable($compiledLangFile)) { // Try load lang cache first
			require($compiledLangFile); // require for opcode optimizing
			
			if (!empty($langMap)) {
				$this->pool['LanguageMap'] = $langMap;
				return true;
			}
		} else { // load default lang file then client lang file
			facula::core('object')->runHook('template_load_language', array(), $error);
		
			// Must load default lang first
			foreach($this->pool['File']['Lang']['default'] AS $file) {
				$langContent .= file_get_contents($file) . "\r\n";
			}
			
			// And then, the client lang
			if ($this->pool['Language'] != 'default') {
				foreach($this->pool['File']['Lang'][$this->pool['Language']] AS $file) {
					$langContent .= file_get_contents($file) . "\r\n";
				}
			}
			
			$langMapPre = explode("\n", $langContent);
			
			foreach($langMapPre AS $lang) {
				$langMapTemp = explode('=', $lang);
				
				if (isset($langMapTemp[1])) { // If $langMapTemp[1] not set, may means this is just a comment.
					$this->pool['LanguageMap'][trim($langMapTemp[0])] = trim($langMapTemp[1]);
				}
			}
			
			if (file_put_contents($compiledLangFile, self::$setting['TemplateFileSafeCode'][0] . ' $langMap = ' . var_export($this->pool['LanguageMap'], true) . '; ' . self::$setting['TemplateFileSafeCode'][1])) {
				return true;
			}
		}
		
		return false;
	}
}

// Security cover for anit any accesses to private variables and methods in side templating object
class faculaTemplateDefaultRender {
	private $content = '';
	
	public function __construct(&$targetTpl, &$assigned = array()) {
		$oldContent = ob_get_clean();
		
		ob_start();
		
		if (isset($oldContent[0])) {
			echo($oldContent);
		}
		
		extract($assigned);
		
		facula::core('debug')->criticalSection(true);
		
		require($targetTpl);
		
		facula::core('debug')->criticalSection(false);
		
		$this->content = ob_get_clean();
		
		return true;
	}
	
	public function getResult() {
		return $this->content;
	}
}

// Template compiler, convert formated content in to php code
class faculaTemplateDefaultCompiler {
	/*
		Format rules and parse priority:
		{+ path/templatename (string1=replacewith1;string2=replacewith2) +} // Include another template
		
		{# Name #} // Targeting an area for content inject
		
		{! LANGUAGE_KEY !} // Inject content from auto loaded inline files (for language etc)
		
		{% $Variable %} // Simplely display the value of that variable
		{% $Variable|format %} // Display the value of that variable with specified format
		
		{~ IDName (ClassName) CurrentPage TotalPages MaxPagesToDisplay (URLFORMAT/%PAGE%/) ~} // Display page switcher
		
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
	
	static private $setting = array(
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
	
	private $pool = array();
	
	private $sourceContent = '';
	
	private $tagPositionMaps = array();
	
	public function __construct(&$pool, &$sourceTpl) {
		$this->pool = $pool;
		$this->sourceContent = $sourceTpl;
		
		return true;
	}
	
	public function addTag($tag, $command) {
		foreach(self::$setting AS $format) {
			if ($format['Tag'] == $tag) {
				facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_TAG_EXISTED|' . $tag, 'template', true);
				
				return false;
				break;
			}
		}
		
		if (is_callable($command)) {
			self::$setting[] = array(
				'Tag' => $tag,
				'Command' => $command,
				'IsExternal' => true,
			);
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_TAG_COMMAND_UNCALLABLE|' . $tag, 'template', true);
		}
		
		return false;
	}
	
	public function compile() {
		$matchedTags = array();
		$tempResult = array();
		$unclosedTag = '';
		
		if ($this->sourceContent) { // If file has been successfully readed
			$content = $this->sourceContent;
			
			foreach(self::$setting['Formats'] AS $format) {
				$format['Preg'] = '/' . preg_quote(self::$setting['Delimiter'][0] . $format['Tag'], '/') . '\s(.*)\s' . preg_quote($format['Tag'] .self::$setting['Delimiter'][1], '/') . '/sU';
				
				if ($format['IsExternal']) {
					$format['Function'] = $format['Command'];
				} else {
					$format['Function'] = array(&$this, $format['Command']);
				}
				
				while(preg_match($format['Preg'], $content, $matchedTags, PREG_OFFSET_CAPTURE)) {
					
					// Get Original content info
					$tempResult['OriginalLen']		= strlen($matchedTags[0][0]);
					$tempResult['StartPos']			= $matchedTags[0][1];
					$tempResult['EndPos']			= $tempResult['StartPos'] + $tempResult['OriginalLen'];
					
					// Generate replacement
					if (!$tempResult['Result'] = $format['Function']($matchedTags[1][0], $tempResult['StartPos'], $this->tagPositionMaps)) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_UNKNOWNERROR', 'template', true);
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
					
					foreach($this->tagPositionMaps AS $tagKey => $tagPos) {
						// If target tag's start position after current tag's start position. increase the target tag's start position as move it back.
						if (isset($tagPos['Start']) && $tagPos['Start'] > $tempResult['StartPos']) {
							$this->tagPositionMaps[$tagKey]['Start'] += $tempResult['LenDifference'];
						}
						
						// If target tag's start position after current tag's start position. increase the target tag's end also
						// And if target tag's start position small than current tag and end position larget than current tag. The current tag must with in the target tag. So we need to move target tag's end position back.
						if (isset($tagPos['End']) && ($tagPos['Start'] > $tempResult['StartPos'] || ($tagPos['Start'] < $tempResult['StartPos'] && $tagPos['End'] > $tempResult['EndPos']))) {
							$this->tagPositionMaps[$tagKey]['End'] += $tempResult['LenDifference'];
						}
						
						if (isset($tagPos['Middle'])) {
							foreach($tagPos['Middle'] AS $tagMidKey => $tagMidVal) {
								if ($tagMidVal > $tempResult['StartPos']) {
									$this->tagPositionMaps[$tagKey]['Middle'][$tagMidKey] += $tempResult['LenDifference'];
								}
							}
						}
					}
				}
			}
			
			if ((!$unclosedTag = $this->doCheckUnclosedTags())) {
				return $content;
			} else {
				facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_TAG_UNCLOSE|' . $unclosedTag, 'template', true);
			}
		}
		
		return false;
	}
	
	/* Compile Tools */
	public function doCheckUnclosedTags() {
		foreach($this->tagPositionMaps AS $refKey => $refRecord) {
			foreach($this->tagPositionMaps AS $tagKey => $tagRecord) {
				if ($refKey != $tagKey) {
					if ($refRecord['Start'] < $tagRecord['Start'] && $refRecord['End'] > $tagRecord['Start'] && $refRecord['End'] < $tagRecord['End']) {
						return $refRecord['Name'] . ':' . $tagRecord['Name'];
					} elseif ($refRecord['Start'] < $tagRecord['End'] && $refRecord['End'] > $tagRecord['End'] && $refRecord['Start'] > $tagRecord['Start']) {
						return $refRecord['Name'] . ':' . $tagRecord['Name'];
					} elseif (isset($tagRecord['Middle'])) {
						array_unshift($tagRecord['Middle'], $tagRecord['Start']);
						
						$tagRecord['Middles'] = count($tagRecord['Middle']);
						
						for($i = 1; $i < $tagRecord['Middles']; $i++) {
							if ( $refRecord['Start'] < $tagRecord['Middle'][$i - 1] && $refRecord['End'] > $tagRecord['Middle'][$i - 1] && $refRecord['End'] < $tagRecord['Middle'][$i]) {
								return $refRecord['Name'] . ':' . $tagRecord['Name'];
							} elseif ($refRecord['Start'] < $tagRecord['Middle'][$i] && $refRecord['End'] > $tagRecord['Middle'][$i] && $refRecord['Start'] > $tagRecord['Middle'][$i - 1]) {
								return $refRecord['Name'] . ':' . $tagRecord['Name'];
							}
						}
					}
				}
			}
		}
		
		return false;
	}
	
	public function doCheckVariableName($name) {
		if (preg_match('/^(\$[A-Za-z0-9\_\'\"\[\]]+)$/', $name)) {
			return true;
		}
		
		return false;
	}
	
	/* Compile functions */
	private function doInclude($format, $pos) {
		$param = explode(' ', $format, 2);
		$replaces = $temprepleaces = array();
		
		if (isset($this->pool['File']['Tpl'][$param[0]]['default'])) {
			$tplFileContent = file_get_contents($this->pool['File']['Tpl'][$param[0]]['default']);
			
			if (isset($param[1])) {
				$param[1][strlen($param[1]) - 1] = $param[1][0] = null;
			
				$temprepleaces['Items'] = explode(';', $param[1]);
				
				foreach($temprepleaces['Items'] AS $replace) {
					$temprepleaces['Thing'] = explode('=', $replace);
					
					if (isset($temprepleaces['Thing'][0][0]) && isset($temprepleaces['Thing'][1][0])) {
						$replaces['Search'][] = trim($temprepleaces['Thing'][0]);
						$replaces['Replace'][] = trim($temprepleaces['Thing'][1]);
					}
				}
				
				$tplFileContent = str_replace($replaces['Search'], $replaces['Replace'], $tplFileContent);
			}
			
			$newCompiler = new self($this->pool, $tplFileContent);
			
			if ($tplContent = $newCompiler->compile()) {
				return $tplContent;
			} else {
				facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_INCLUDE_TPL_EMPTY|' . $param[0], 'template', true);
			}
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_INCLUDE_TPL_NOTFOUND|' . $param[0], 'template', true);
		}
		
		return false;
	}
	
	private function doInjectArea($format, $pos) {
		$phpcode = '';
		
		if (isset($this->pool['Injected'][$format])) {
			foreach($this->pool['Injected'][$format] AS $code) {
				$phpcode .= $code;
			}
			
			return $phpcode;
		}
		
		return false;
	}
	
	private function doLanguage($format, $pos) {
		if (isset($this->pool['LanguageMap'][$format][0])) {
			return $this->pool['LanguageMap'][$format];
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LANGUAGE_NOTFOUND|' . $format, 'template', true);
		}
		
		return false;
	}
	
	private function doVariable($format, $pos) {
		$param = explode('|', $format);
		$phpcode = '';
		
		if (isset($param[0])) {
			if (!$this->doCheckVariableName($param[0])) {
				facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_VARIABLE_NAME_INVALID|' . $param[0], 'template', true);
				return false;
			}
			
			$phpcode .= '<?php if (isset(' . $param[0] . ')) { ';
			
			if (!isset($param[1])) {
				$phpcode .= 'echo(' . $param[0] . ');';
			} else {
				switch($param[1]) {
					case 'date':
						if (isset($this->pool['LanguageMap']['FORMAT_DATE_' . $param[2]])) {
							$phpcode .= 'echo(date(\'' . $this->pool['LanguageMap']['FORMAT_DATE_' . $param[2]] . '\', intval(' . $param[0] . ')));';
						} else {
							facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_VARIABLE_DATE_LANG_MISSED', 'template', true);
							return false;
						}
						break;
					
					case 'friendlyTime':
						if (isset($this->pool['LanguageMap']['FORMAT_TIME_SNDBEFORE']) && 
							isset($this->pool['LanguageMap']['FORMAT_TIME_MINBEFORE']) && 
							isset($this->pool['LanguageMap']['FORMAT_TIME_HRBEFORE']) &&
							isset($this->pool['LanguageMap']['FORMAT_TIME_DAYBEFORE']) &&
							isset($this->pool['LanguageMap']['FORMAT_TIME_MOREBEFORE'])) {
							$phpcode .= '$temptime = $Time - (' . $param[0] . '); if ($temptime < 60) { printf(\'' . $this->pool['LanguageMap']['FORMAT_TIME_SNDBEFORE'] . '\', $temptime); } elseif ($temptime < 3600) { printf(\'' . $this->pool['LanguageMap']['FORMAT_TIME_MINBEFORE'] . '\', intval($temptime / 60)); } elseif ($temptime < 86400) { printf(\'' . $this->pool['LanguageMap']['FORMAT_TIME_HRBEFORE'] . '\', intval($temptime / 3600)); } elseif ($temptime < 604800) { printf(\'' . $this->pool['LanguageMap']['FORMAT_TIME_DAYBEFORE'] . '\', intval($temptime / 86400)); } elseif ($temptime) { echo(date(\'' . $this->pool['LanguageMap']['FORMAT_TIME_MOREBEFORE'] . '\', intval(' . $param[0] . '))); } $temptime = 0;';
						} else {
							facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_VARIABLE_BYTE_LANG_MISSED', 'template', true);
							return false;
						}
						break;
					
					case 'bytes':
						if (isset($this->pool['LanguageMap']['FORMAT_FILESIZE_BYTES']) && 
							isset($this->pool['LanguageMap']['FORMAT_FILESIZE_KILOBYTES']) && 
							isset($this->pool['LanguageMap']['FORMAT_FILESIZE_MEGABYTES']) &&
							isset($this->pool['LanguageMap']['FORMAT_FILESIZE_GIGABYTES']) &&
							isset($this->pool['LanguageMap']['FORMAT_FILESIZE_TRILLIONBYTES'])) {
							$phpcode .= '$tempsize = ' . $param[0] . '; if ($tempsize < 1024) { echo (($tempsize).\'' . $this->pool['LanguageMap']['FORMAT_FILESIZE_BYTES'] . '\'); } elseif ($tempsize < 1048576) { echo (intval($tempsize / 1024).\'' . $this->pool['LanguageMap']['FORMAT_FILESIZE_KILOBYTES'] . '\'); } elseif ($tempsize < 1073741824) { echo (round($tempsize / 1048576, 1).\'' . $this->pool['LanguageMap']['FORMAT_FILESIZE_MEGABYTES'] . '\'); } elseif ($tempsize < 1099511627776) { echo (round($tempsize / 1073741824, 2).\'' . $this->pool['LanguageMap']['FORMAT_FILESIZE_GIGABYTES'] . '\'); } elseif ($tempsize < 1125899906842624) { echo (round($tempsize / 1099511627776, 3).\'' . $this->pool['LanguageMap']['FORMAT_FILESIZE_TRILLIONBYTES'] . '\'); } $tempsize = 0;';
						} else {
							facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_VARIABLE_BYTE_LANG_MISSED', 'template', true);
							return false;
						}
						break;
						
					case 'json':
						$phpcode .= 'echo(json_encode(' . $param[0] . ', JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG));';
						break;
						
					case 'jsonData':
						$phpcode .= 'echo(htmlspecialchars(json_encode(' . $param[0] . ')));';
						break;
						
					case 'urlChar':
						$phpcode .= 'echo(urlencode(' . $param[0] . '));';
						break;
						
					case 'slashed':
						$phpcode .= 'echo(addslashes(' . $param[0] . '));';
						break;
						
					case 'html':
						$phpcode .= 'echo(htmlspecialchars(' . $param[0] . ', ENT_QUOTES));';
						break;
						
					case 'htmlnl':
						$phpcode .= 'echo(nl2br(htmlspecialchars(' . $param[0] . ', ENT_QUOTES)));';
						break;
						
					case 'number':
						$phpcode .= 'echo(number_format(' . $param[0] . '));';
						break;
						
					case 'floatNumber':
						$phpcode .= 'echo(number_format(' . $param[0] . ', ' . (isset($param[2]) ? intval($param[2]) : 2) . '));';
						break;
					
					default:
						$variableName = array_shift($param);
						$phpcode .= 'printf(' . $variableName . ', ' . implode(', ', $param) . ');';
						break;
				}
			}
			
			$phpcode .= ' } ?>';
			
			return $phpcode;
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_VARIABLE_MUST_DEFINED', 'template', true);
		}
	
		return false;
	}
	
	private function doPageSwitcher($format) {
		$maxpage = 20;
		$matched = $formatMatched = array();
		$formatVariables = array('Search' => array(), 'Replace' => array());
		
		$phpcode = '<?php ';
		
		if (preg_match('/^([A-Za-z0-9_-]+) \(([A-Za-z0-9_-\s]+)\) (\$[A-Za-z0-9\_\'\"\[\]]+) (\$[A-Za-z0-9\_\'\"\[\]]+) (\$[A-Za-z0-9\_\'\"\[\]]+) \((.*)\)$/', $format, $matched)) {
			list($org, $name, $classname, $currentpage, $totalpage, $maxdisplay, $format) = $matched;
			
			$name = htmlspecialchars($name, ENT_QUOTES);
			$classname = htmlspecialchars($classname, ENT_QUOTES);
			
			// Find all variables in the format string
			if (preg_match_all('/\{(\$[A-Za-z0-9\_\'\"\[\]]+)\}/sU', $format, $formatMatched)) {
				// Prepare for the replacement
				foreach($formatMatched[0] AS $key => $value) {
					$formatVariables['Search'][] = urlencode($value);
					$formatVariables['Replace'][] = '\' . ' . $formatMatched[1][$key] . ' . \'';
				}
			}
			
			// Urlencode the format but replace some string back for url params 
			$format = str_replace(array('%3A', '%2F', '%3F', '%3D', '%26', '%25PAGE%25'), array(':', '/', '?', '=', '&', '%PAGE%'), urlencode($format));
			
			// Replace variables string to variables
			$format = str_replace($formatVariables['Search'], $formatVariables['Replace'], $format);
			
			$phpcode = '
			<?php 
				if (' . $totalpage . ' > 1) { 
					echo(\'<ul id="' . $name . '" class="' . $classname . '">\'); 
					
					if (' . $totalpage . ' > 0 && ' . $currentpage . ' <= ' . $totalpage . ') { 
						if (' . $currentpage . ' > 1) 
							echo(\'<li><a href="' . str_replace('%PAGE%', '1', $format) . '">&laquo;</a></li><li><a href="\' . str_replace(\'%PAGE%\', (' . $currentpage . ' - 1), \'' . $format . '\') . \'">&lsaquo;</a></li>\'); 
						
						$loop = intval(' . $maxdisplay . ' / 2); 
						
						if (' . $currentpage . ' - $loop > 0) { 
							for ($i = ' . $currentpage . ' - $loop; $i <= ' . $totalpage . ' && $i <= ' . $currentpage . ' + $loop; $i++) { 
								if ($i == ' . $currentpage . ') { 
									echo(\'<li class="this"><a href="\' . str_replace(\'%PAGE%\', $i, \'' . $format . '\'). \'">\' . $i . \'</a></li>\'); 
								} else { 
									echo(\'<li><a href="\' . str_replace(\'%PAGE%\', $i, \'' . $format . '\') . \'">\' . $i . \'</a></li>\'); 
								}
							}
						} else { 
							for ($i = 1; $i <= ' . $totalpage . ' && $i <= ' . $maxdisplay . '; $i++) { 
								if ($i == ' . $currentpage . ') { 
									echo(\'<li class="this"><a href="\' . str_replace(\'%PAGE%\', $i, \'' . $format . '\'). \'">\' . $i . \'</a></li>\'); 
								} else { 
									echo(\'<li><a href="\' . str_replace(\'%PAGE%\', $i, \'' . $format . '\') . \'">\' . $i . \'</a></li>\'); 
								}
							}
						} 
						
						unset($loop); 
						
						if (' . $totalpage . ' > ' . $currentpage . ') 
							echo(\'<li><a href="\' . str_replace(\'%PAGE%\', (' . $currentpage . ' + 1), \'' . $format . '\') . \'">&rsaquo;</a></li><li><a href="\' . str_replace(\'%PAGE%\', (' . $totalpage . '), \'' . $format . '\') . \'">&raquo;</a></li>\');
					} 
					
					echo(\'</ul>\');
				} 
			?>';
			
			$phpcode = str_replace(array("\r", "\n", "\t",'  '), '', $phpcode);
			
			return $phpcode;
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_PAGER_FORMAT_INVALID|' . $format, 'template', true);
		}
		
		return false;
	}
	
	private function doLoop($format, $pos) {
		$params = explode(' ', $format);
		$matched = array();
		$phpcode = $unclosed = '';
		
		switch($params[0]) {
			case 'EMPTY':
				if (isset($params[1]) && preg_match('/^([A-Za-z]+)$/', $params[1], $matched)) {
				
					// Check if we already opened the tag
					if (!isset($this->tagPositionMaps['Loop:' . $params[1]]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_NOT_OPEN|' . $params[1], 'template', true);
						return false;
					}
					
					// Check if we already closed this loop
					if (isset($this->tagPositionMaps['Loop:' . $params[1]]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_ALREADY_CLOSED|' . $params[1], 'template', true);
						return false;
					}
					
					// Check if we already emptied this foreach
					if (isset($this->tagPositionMaps['Loop:' . $params[1]]['Emptied']) && $this->tagPositionMaps['Loop:' . $params[1]]['Emptied']) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_ALREADY_EMPTY|' . $params[1], 'template', true);
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
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
				
			case 'EOF':
				if (isset($params[1]) && preg_match('/^([A-Za-z]+)$/', $params[1], $matched)) {
					if (!isset($this->tagPositionMaps['Loop:' . $params[1]]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_NOT_OPEN|' . $params[1], 'template', true);
						return false;
					}
					
					if (isset($this->tagPositionMaps['Loop:' . $params[1]]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_ALREADY_CLOSED|' . $params[1], 'template', true);
						return false;
					}
					
					if (isset($this->tagPositionMaps['Loop:' . $params[1]]['Emptied']) && $this->tagPositionMaps['Loop:' . $params[1]]['Emptied']) {
						// If we have empty section in this loop
						
						$phpcode .= '<?php } ?>'; // We just need to close empty one (The first if)
					} else {
						$phpcode .= '<?php }} ?>'; // We need to both two, the first if, and foreach;
					}
					
					// Tag this loop to emptied
					$this->tagPositionMaps['Loop:' . $params[1]]['End'] = $pos + strlen($phpcode);
					
					return $phpcode;
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
				
			default:
				if (preg_match('/^([A-Za-z]+) (\$[A-Za-z0-9\_\'\"\[\]]+)$/', $format, $matched)) {
					list($org, $name, $valuename) = $matched;
					
					if (!isset($this->tagPositionMaps['Loop:' . $name])) {
						$phpcode .= '<?php if (isset(' . $valuename . ') && is_array(' . $valuename . ') && !empty(' . $valuename . ')) { ';
						$phpcode .= 'foreach (' . $valuename . ' AS $no => $' . $name . ') { ?>';
						
						$this->tagPositionMaps['Loop:' . $name] = array(
							'Start' => $pos,
							'Name' => $name,
						);
					
						return $phpcode;
					} else {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_FORMAT_EXISTED|' . $name, 'template', true);
					}
					
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
		}
	
		return false;
	}
	
	private function doLogic($format, $pos) {
		$params = explode(' ', $format, 2);
		$matched = array();
		$phpcode = $unclosed = '';
		
		switch($params[0]) {
			case 'ELSEIF':
				if (isset($params[1]) && preg_match('/^([A-Za-z]+) (.*)$/', $params[1], $matched)) {
					list($org, $name, $condition) = $matched;
					
					if (!isset($this->tagPositionMaps['Logic:' . $name]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_NOT_OPEN|' . $name, 'template', true);
						return false;
					}
					
					if (isset($this->tagPositionMaps['Logic:' . $name]['Elsed']) && $this->tagPositionMaps['Logic:' . $name]['Elsed']) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_ELSED|' . $name, 'template', true);
						return false;
					}
					
					if (isset($this->tagPositionMaps['Logic:' . $name]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_CLOSED|' . $name, 'template', true);
						return false;
					}
					
					$phpcode .= '<?php } elseif (' . $condition . ') { ?>';
					
					$this->tagPositionMaps['Logic:' . $name]['Middle'][] = $pos + strlen($phpcode);
					
					return $phpcode;
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
				
			case 'ELSE':
				if (isset($params[1]) && preg_match('/^([A-Za-z]+)$/', $params[1], $matched)) {
					list($org, $name) = $matched;
					
					if (!isset($this->tagPositionMaps['Logic:' . $name]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_NOT_OPEN|' . $name, 'template', true);
						return false;
					}
					
					if (isset($this->tagPositionMaps['Logic:' . $name]['Elsed']) && $this->tagPositionMaps['Logic:' . $name]['Elsed']) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_ELSED|' . $name, 'template', true);
						return false;
					}
					
					if (isset($this->tagPositionMaps['Logic:' . $name]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_CLOSED|' . $name, 'template', true);
						return false;
					}
					
					$phpcode .= '<?php } else { ?>';
					
					$this->tagPositionMaps['Logic:' . $name]['Elsed'] = true;
					$this->tagPositionMaps['Logic:' . $name]['Middle'][] = $pos + strlen($phpcode);
					
					return $phpcode;
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
			
			case 'EOF':
				if (isset($params[1]) && preg_match('/^([A-Za-z]+)$/', $params[1], $matched)) {
					list($org, $name) = $matched;
					
					if (!isset($this->tagPositionMaps['Logic:' . $name]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_NOT_OPEN|' . $name, 'template', true);
						return false;
					}
					
					if (isset($this->tagPositionMaps['Logic:' . $name]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_CLOSED|' . $name, 'template', true);
						return false;
					}
					
					$phpcode .= '<?php } ?>';
					
					$this->tagPositionMaps['Logic:' . $name]['End'] = $pos + strlen($phpcode);
					
					return $phpcode;
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
				
			default:
				if (preg_match('/^([A-Za-z]+) (.*)$/', $format, $matched)) {
					list($org, $name, $condition) = $matched;
					
					if (!isset($this->tagPositionMaps['Logic:' . $name])) {
						$phpcode .= '<?php if (' . $condition . ') { ?>';
						
						$this->tagPositionMaps['Logic:' . $name] = array(
							'Start' => $pos,
							'Name' => $name,
						);
					
						return $phpcode;
					} else {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_FORMAT_EXISTED|' . $name, 'template', true);
					}
					
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
		}
		
		return false;
	}
	
	private function doCase($format, $pos) {
		$params = explode(' ', $format, 2);
		$matched = array();
		$phpcode = $unclosed = '';
		
		switch($params[0]) {
			case 'CASE':
				if (isset($params[1]) && preg_match('/^([A-Za-z]+) (.*)$/', $params[1], $matched)) {
					list($org, $name, $value) = $matched;
					
					if (!isset($this->tagPositionMaps['Case:' . $name]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_NOT_OPEN|' . $name, 'template', true);
						return false;
					}
					
					if (isset($this->tagPositionMaps['Case:' . $name]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_ALREADY_CLOSED|' . $name, 'template', true);
						return false;
					}
					
					$phpcode .= '<?php break; case \'' . addslashes($value) . '\': ?>';
					
					$this->tagPositionMaps['Case:' . $name]['Middle'][] = $pos + strlen($phpcode);
					
					return $phpcode;
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
				
			case 'EOF':
				if (isset($params[1]) && preg_match('/^([A-Za-z]+)$/', $params[1], $matched)) {
					list($org, $name) = $matched;
					
					if (!isset($this->tagPositionMaps['Case:' . $name]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_NOT_OPEN|' . $name, 'template', true);
						return false;
					}
					
					if (isset($this->tagPositionMaps['Case:' . $name]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_ALREADY_CLOSED|' . $name, 'template', true);
						return false;
					}
					
					$phpcode .= '<?php break; }} ?>';
					
					$this->tagPositionMaps['Case:' . $name]['End'] = $pos + strlen($phpcode);
					
					return $phpcode;
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
				
			default:
				if (preg_match('/^([A-Za-z]+) (\$[A-Za-z0-9\_\'\"\[\]]+)$/', $format, $matched)) {
					list($org, $name, $variable) = $matched;
					
					if (!isset($this->tagPositionMaps['Case:' . $name])) {
						$phpcode .= '<?php if (isset(' . $variable . ')) { switch(' . $variable . ') { default: ?>';
						
						$this->tagPositionMaps['Case:' . $name] = array(
							'Start' => $pos,
							'Name' => $name,
						);
					
						return $phpcode;
					} else {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_FORMAT_EXISTED|' . $name, 'template', true);
					}
					
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
		}
		
		return false;
	}
}

?>