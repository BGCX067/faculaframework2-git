<?php

interface faculaTemplateInterface {
	public function _inited();
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
		$files = array();
		// General settings
		$this->configs = array(
			'Cache' => isset($cfg['CacheTemplate']) && $cfg['CacheTemplate'] ? true : false,
			'Compress' => isset($cfg['CompressOutput']) && $cfg['CompressOutput'] ? true : false,
			'Renew' => isset($cfg['ForceRenew']) && $cfg['ForceRenew'] ? true : false,
			'AsDbgHandler' => isset($cfg['DisplayDebug']) && $cfg['DisplayDebug'] ? true : false,
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
		
		// Scan for template files
		if ($files = $facula->scanModuleFiles($this->configs['TplPool'])) {
			foreach($files AS $file) {
				switch($file['Prefix']) {
					case 'language':
						$this->pool['File']['Lang'][$file['Name']][] = $file['Path'];
						break;
						
					case 'template':
						if (!isset($this->pool['File']['Tpl'][$file['Name']])) {
							$this->pool['File']['Tpl'][$file['Name']] = $file['Path'];
						} else {
							throw new Exception('Template file ' . $this->pool['File']['Tpl'][$file['Name']] . ' conflicted with ' . $file['Path'] . '.');
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
		// Determine what language can be used for this client
		$siteLanguage = facula::core('request')->getClientInfo('languages');
		
		$clientLanguage = array_keys($this->pool['File']['Lang']);
		
		$selectedLanguage = array_values(array_intersect($siteLanguage, $clientLanguage)); // Use $siteLanguage as the first param so we can follow clients priority
		
		if (isset($selectedLanguage[0][0])) {
			$this->pool['Language'] = $selectedLanguage[0];
		} else {
			$this->pool['Language'] = 'default';
		}
		
		// Set Essential assign value
		$this->assigned['Time'] = FACULA_TIME;
		$this->assigned['RootURL'] = facula::core('request')->getClientInfo('rootURL');
		$this->assigned['AbsRootURL'] = facula::core('request')->getClientInfo('absRootURL');
		
		return true;
	}
	
	public function assign($key, $val) {
		if (!isset($this->assigned[$key])) {
			$this->assigned[$key] = $val;
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_ASSIGN_KEY_EXISTED', 'template', true);
		}
		
		return false;
	}
	
	public function inject($key, $templatecontent) {
		$this->pool['Injected'][$key][] = $templatecontent;
		
		return true;
	}
	
	public function render($templateName, $expire = 0, $expiredCallback = null) {
		$content = '';
		
		if ($content = $this->getPageContent($templateName, $expire, $expiredCallback)) {
			return $content;
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_NOCONTENT|' . $templateName, 'template', true);
		}
		
		return false;
	}
	
	public function importTemplateFile($name, $path) {
		if (!isset($this->pool['File']['Tpl'][$name])) {
			$this->pool['File']['Tpl'][$name] = $path;
			
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
	
	private function getPageContent($templateName, $expire, $expiredCallback = null) {
		$content = '';
		$compiledTpl = $this->configs['Compiled'] . DIRECTORY_SEPARATOR . 'compiledTemplate.' . $templateName . '.' . $this->pool['Language'] . '.php';
		
		if (isset($this->pool['File']['Tpl'][$templateName])) {
			if (!$this->configs['Renew'] && is_readable($compiledTpl) && (!$expire || filemtime($compiledTpl) > FACULA_TIME - $expire)) {
				if ($content = $this->doRender($compiledTpl)) {
					return $content;
				}
			} else {
				if ($expiredCallback && is_callable($expiredCallback)) {
					$expiredCallback();
				}
				
				if ($this->doCompile($this->pool['File']['Tpl'][$templateName], $compiledTpl)) {
					if ($content = $this->doRender($compiledTpl)) {
						return $content;
					}
				}
			}
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_NOTFOUND|' . $templateName, 'template', true);
		}
		
		return false;
	}
	
	/* Load setting for compiling */
	private function doRender(&$compiledTpl) {
		$render = new faculaTemplateDefaultRender($compiledTpl, $this->assigned);
		
		return $render->getResult();
	}
	
	private function doCompile($sourceTpl, $resultTpl) {
		$sourceContent = $renderCachedContent = $compiledContent = $compiledContentForCached = '';
		$splitedCompiledContent = array();
		
		if (!isset($this->pool['LanguageMap'])) {
			$this->loadLangMap();
		}
		
		if ($sourceContent = file_get_contents($sourceTpl)) {
			$compiler = new faculaTemplateDefaultCompiler($this->pool, $sourceContent);
			
			if ($compiledContent = $compiler->compile()) {
				if ($this->configs['Compress']) {
					$content = str_replace(array('  ', "\r", "\n", "\t"), '', $content);
				}
			
				if ($this->configs['Cache']) {
					// Spilt using no cache 
					$splitedCompiledContent = explode('<!-- NOCACHE -->', $compiledContent);
					$splitedCompiledContentIndexLen = count($splitedCompiledContent) - 1;
					
					// Deal with area which need to be cached
					foreach($splitedCompiledContent AS $key => $val) {
						if ($key > 0 && $key < $splitedCompiledContentIndexLen && $key%2) {
							$splitedCompiledContent[$key] = '<?php echo(\'' . addslashes($val) . '\'); ?>';
						}
					}
					
					// Reassembling compiled content;
					$compiledContentForCached = implode('', $splitedCompiledContent);
					
					// Save compiled content to a temp file
					$cachedResultTpl = $resultTpl . '.cached.temp.php';
					
					if (file_put_contents($cachedResultTpl, $compiledContentForCached)) {
						$render = new faculaTemplateDefaultRender($cachedResultTpl, $this->assigned);
						
						// Render nocached compiled content
						if (($renderCachedContent = $render->getResult()) && unlink($cachedResultTpl)) {
							$renderCachedContent = self::$setting['TemplateFileSafeCode'][0] . self::$setting['TemplateFileSafeCode'][1] . $renderCachedContent;
							
							return file_put_contents($resultTpl, $renderCachedContent);
						}
					}
				} else {
					return file_put_contents($resultTpl, $compiledContent);
				}
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
		ob_start();
		
		extract($assigned);
		
		require($targetTpl);
		
		$this->content = ob_get_contents();
		
		ob_end_clean();
		
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
		
		{~ IDName (ClassName) CurrentPage TotalPages (URLFORMAT/%PAGE%/) ~} // Display page switcher
		
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
		
		{^ SwitcherName $Variable ^$}
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
	
	private $tagMaps = array();
	
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
				$format['Preg'] = '/\\' .  self::$setting['Delimiter'][0] . '\\' . $format['Tag'] . ' (.*) \\' . $format['Tag'] . '\\' . self::$setting['Delimiter'][1] . '/sU';
				
				if ($format['IsExternal']) {
					$format['Function'] = $format['Command'];
				} else {
					$format['Function'] = array(&$this, $format['Command']);
				}
				
				while(preg_match($format['Preg'], $content, $matchedTags, PREG_OFFSET_CAPTURE)) {
					// Get Original content info
					$tempResult['OriginalLen']		= strlen($matchedTags[0][0]);
					$tempResult['StartPos']			= $matchedTags[0][1];
					$tempResult['EndPos']			= $matchedTags[0][1] + $tempResult['OriginalLen'];
					
					// Generate replacement
					$tempResult['Result'] = $format['Function']($matchedTags[1][0], $tempResult['StartPos'], $this->tagMaps);
					$tempResult['ResultLen'] = strlen($tempResult['Result']);
					
					// Cuting out the original content
					$contentBefore = substr($content, 0, $tempResult['StartPos']);
					$contentAfter = substr($content, $tempResult['EndPos'], strlen($content));
					
					// Reassembling content with new result
					$content = $contentBefore . $tempResult['Result'] . $contentAfter;
					
					// Renew the position indexs
					foreach($this->tagMaps AS $key => $val) {
						if (isset($this->tagMaps[$key]['Start']) && $tempResult['StartPos'] < $this->tagMaps[$key]['Start']) {
							$this->tagMaps[$key]['Start'] += $tempResult['ResultLen'] - $tempResult['OriginalLen'];;
						}
						
						if (isset($this->tagMaps[$key]['End']) && $tempResult['EndPos'] < $this->tagMaps[$key]['End']) {
							$this->tagMaps[$key]['End'] += $tempResult['ResultLen'] - $tempResult['OriginalLen'];
						}
					}
				}
			}
			
			if (!$unclosedTag = $this->doCheckUnclosedBetweenPositions(0, strlen($content))) {
				return $content;
			} else {
				facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_TAG_UNCLOSE|' . $unclosedTag, 'template', true);
			}
		}
		
		return false;
	}
	
	/* Compile Tools */
	public function doCheckUnclosedBetweenPositions($posFrom, $posTo) {
		if ($posFrom >= $posTo) {
			facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_UNCLOSECHECK_FROM_MUST_SMALLER', 'template', true);
			return false;
		}
		
		foreach($this->tagMaps AS $tagKey => $tagRecord) {
			if ((isset($tagRecord['Start']) && $tagRecord['Start'] > $posFrom)) {
				if (!isset($tagRecord['End'])) {
					return isset($tagRecord['Name'][0]) ? $tagRecord['Name'] : true;
					break;
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
	private function doInclude($format) {
		$param = explode(' ', $format);
		$replaces = $temprepleaces = array();
		
		if (isset($this->pool['File']['Tpl'][$param[0]])) {
			$tplContent = file_get_contents($this->pool['File']['Tpl'][$param[0]]);
			
			if (isset($param[1])) {
				$temprepleaces[0] = explode(';', $param[1]);
				
				foreach($temprepleaces[0] AS $replace) {
					$temprepleaces[1] = explode('=', $replace);
					
					if (isset($temprepleaces[1][0][0]) && isset($temprepleaces[1][1][0])) {
						$replaces['Search'][] = $temprepleaces[1][0];
						$replaces['Replace'][] = $temprepleaces[1][1];
					}
				}
				
				$tplContent = str_replace($replaces['Search'], $replaces['Replace'], $tplContent);
			}
			
			return $tplContent;
		} else {
			facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_INCLUDE_TPL_NOTFOUND|' . $param[0], 'template', true);
		}
		
		return false;
	}
	
	private function doInjectArea($format) {
		$phpcode = '';
		
		if (isset($this->pool['Injected'][$format])) {
			foreach($this->pool['Injected'][$format] AS $code) {
				$phpcode .= $code;
			}
			
			return $phpcode;
		}
		
		return false;
	}
	
	private function doLanguage($format) {
		if (isset($this->pool['LanguageMap'][$format])) {
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
			
			$phpcode = '<?php if (isset(' . $param[0] . ')) { ';
			
			if (!isset($param[1])) {
				$phpcode .= 'echo(' . $param[0] . ');';
			} else {
				switch($param[1]) {
					case 'date':
						if (isset($this->pool['LanguageMap']['FORMAT_DATE_' . $param[2]])) {
							$phpcode .= 'echo(date(\'' . $this->pool['LanguageMap']['FORMAT_DATE_' . $param[2]] . '\', intval(' . $param[0] . ')));';
						} else {
							facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_VARIABLE_DATE_LANG_MISSED', 'template', true);
						}
						break;
				
					case 'friendlytime':
						if (isset($this->pool['LanguageMap']['FORMAT_TIME_SNDBEFORE']) && 
							isset($this->pool['LanguageMap']['FORMAT_TIME_MINBEFORE']) && 
							isset($this->pool['LanguageMap']['FORMAT_TIME_HRBEFORE']) &&
							isset($this->pool['LanguageMap']['FORMAT_TIME_DAYBEFORE']) &&
							isset($this->pool['LanguageMap']['FORMAT_TIME_MOREBEFORE'])) {
							$phpcode .= '$temptime = $Time - (' . $param[0] . '); if ($temptime < 60) { printf(\'' . $this->pool['LanguageMap']['FORMAT_TIME_SNDBEFORE'] . '\', $temptime); } elseif ($temptime < 3600) { printf(\'' . $this->pool['LanguageMap']['FORMAT_TIME_MINBEFORE'] . '\', intval($temptime / 60)); } elseif ($temptime < 86400) { printf(\'' . $this->pool['LanguageMap']['FORMAT_TIME_HRBEFORE'] . '\', intval($temptime / 3600)); } elseif ($temptime < 604800) { printf(\'' . $this->pool['LanguageMap']['FORMAT_TIME_DAYBEFORE'] . '\', intval($temptime / 86400)); } elseif ($temptime) { echo(date(\'' . $this->pool['LanguageMap']['FORMAT_TIME_MOREBEFORE'] . '\', intval(' . $param[0] . '))); } $temptime = 0;';
						} else {
							facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_VARIABLE_BYTE_LANG_MISSED', 'template', true);
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
						}
						break;
						
					case 'json':
						$phpcode .= 'echo(json_encode(' . $param[0] . ', JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG));';
						break;
						
					case 'jsonData':
						$phpcode .= 'echo(htmlspecialchars(json_encode(' . $param[0] . ')));';
						break;
						
					case 'urlchar':
						$phpcode .= 'echo(urlencode(' . $param[0] . '));';
						break;
						
					case 'slashed':
						$phpcode .= 'echo(addslashes(' . $param[0] . '));';
						break;
						
					case 'html':
						$phpcode .= 'echo(htmlspecialchars(' . $param[0] . ', ENT_QUOTES));';
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
		$matched = array();
		
		$phpcode = '<?php ';
		
		if (preg_match('/^([A-Za-z0-9_-]+) \(([A-Za-z0-9_-\s]+)\) (\$[A-Za-z0-9\_\'\"\[\]]+) (\$[A-Za-z0-9\_\'\"\[\]]+) (\$[A-Za-z0-9\_\'\"\[\]]+) \((.*)\)$/', $format, $matched)) {
			list($org, $name, $classname, $currentpage, $totalpage, $maxdisplay, $format) = $matched;
			
			$name = htmlspecialchars($name, ENT_QUOTES);
			$classname = htmlspecialchars($classname, ENT_QUOTES);
			$format = str_replace(array('%3A', '%2F', '%3F', '%3D', '%25PAGE%25'), array(':', '/', '?', '=', '%PAGE%'), urlencode($format));
			
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
			
			return str_replace(array("\r", "\n", "\t",'  '), '', $phpcode);
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
					if (!isset($this->tagMaps['Loop:' . $params[1]]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_NOT_OPEN|' . $params[1], 'template', true);
						return false;
					}
					
					// Check if we already closed this loop
					if (isset($this->tagMaps['Loop:' . $params[1]]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_ALREADY_CLOSED|' . $params[1], 'template', true);
						return false;
					}
					
					// Check if we already emptied this foreach
					if (isset($this->tagMaps['Loop:' . $params[1]]['Emptied']) && $this->tagMaps['Loop:' . $params[1]]['Emptied']) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_ALREADY_EMPTY|' . $params[1], 'template', true);
						return false;
					}
					
					// Well, not yet. So we can empty it right now
					$phpcode .= '<?php } } else { ?>'; // Close foreach and Open else case
					
					// Tag this loop to emptied
					$this->tagMaps['Loop:' . $params[1]]['Emptied'] = true;
					
					return $phpcode;
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
				
			case 'EOF':
				if (isset($params[1]) && preg_match('/^([A-Za-z]+)$/', $params[1], $matched)) {
					if (!isset($this->tagMaps['Loop:' . $params[1]]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_NOT_OPEN|' . $params[1], 'template', true);
						return false;
					}
					
					if ($unclosed = $this->doCheckUnclosedBetweenPositions($this->tagMaps['Loop:' . $params[1]]['Start'], $pos)) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_TAG_UNCLOSE|' . $unclosed, 'template', true);
						return false;
					}
					
					if (isset($this->tagMaps['Loop:' . $params[1]]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_ALREADY_CLOSED|' . $params[1], 'template', true);
						return false;
					}
					
					if (isset($this->tagMaps['Loop:' . $params[1]]['Emptied']) && $this->tagMaps['Loop:' . $params[1]]['Emptied']) {
						// If we have empty section in this loop
						
						$phpcode .= '<?php } ?>'; // We just need to close empty one (The first if)
					} else {
						$phpcode .= '<?php }} ?>'; // We need to both two, the first if, and foreach;
					}
					
					// Tag this loop to emptied
					$this->tagMaps['Loop:' . $params[1]]['End'] = $pos;
					
					return $phpcode;
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOOP_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
				
			default:
				if (preg_match('/^([A-Za-z]+) (\$[A-Za-z0-9\_\'\"\[\]]+)$/', $format, $matched)) {
					list($org, $name, $valuename) = $matched;
					
					if (!isset($this->tagMaps['Loop:' . $name])) {
						$phpcode .= '<?php if (isset(' . $valuename . ') && is_array(' . $valuename . ') && !empty(' . $valuename . ')) { ';
						$phpcode .= 'foreach (' . $valuename . ' AS $no => $' . $name . ') { ?>';
						
						$this->tagMaps['Loop:' . $name] = array(
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
					
					if (!isset($this->tagMaps['Logic:' . $name]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_NOT_OPEN|' . $name, 'template', true);
						return false;
					}
					
					if (isset($this->tagMaps['Logic:' . $name]['Elsed']) && $this->tagMaps['Logic:' . $name]['Elsed']) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_ELSED|' . $name, 'template', true);
						return false;
					}
					
					if (isset($this->tagMaps['Logic:' . $name]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_CLOSED|' . $name, 'template', true);
						return false;
					}
					
					$phpcode .= '<?php } elseif (' . $condition . ') { ?>';
					
					return $phpcode;
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
				
			case 'ELSE':
				if (isset($params[1]) && preg_match('/^([A-Za-z]+)$/', $params[1], $matched)) {
					list($org, $name) = $matched;
					
					if (!isset($this->tagMaps['Logic:' . $name]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_NOT_OPEN|' . $name, 'template', true);
						return false;
					}
					
					if (isset($this->tagMaps['Logic:' . $name]['Elsed']) && $this->tagMaps['Logic:' . $name]['Elsed']) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_ELSED|' . $name, 'template', true);
						return false;
					}
					
					if (isset($this->tagMaps['Logic:' . $name]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_CLOSED|' . $name, 'template', true);
						return false;
					}
					
					$this->tagMaps['Logic:' . $name]['Elsed'] = true;
					
					$phpcode .= '<?php } else { ?>';
					
					return $phpcode;
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
			
			case 'EOF':
				if (isset($params[1]) && preg_match('/^([A-Za-z]+)$/', $params[1], $matched)) {
					list($org, $name) = $matched;
					
					if (!isset($this->tagMaps['Logic:' . $name]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_NOT_OPEN|' . $name, 'template', true);
						return false;
					}
					
					if ($unclosed = $this->doCheckUnclosedBetweenPositions($this->tagMaps['Logic:' . $name]['Start'], $pos)) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_TAG_UNCLOSE|' . $unclosed, 'template', true);
						return false;
					}
					
					if (isset($this->tagMaps['Logic:' . $name]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_ALREADY_CLOSED|' . $name, 'template', true);
						return false;
					}
					
					$phpcode .= '<?php } ?>';
					
					$this->tagMaps['Logic:' . $name]['End'] = $pos;
					
					return $phpcode;
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_LOGIC_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
				
			default:
				if (preg_match('/^([A-Za-z]+) (.*)$/', $format, $matched)) {
					list($org, $name, $condition) = $matched;
					
					if (!isset($this->tagMaps['Logic:' . $name])) {
						$phpcode .= '<?php if (' . $condition . ') { ?>';
						
						$this->tagMaps['Logic:' . $name] = array(
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
					
					if (!isset($this->tagMaps['Case:' . $name]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_NOT_OPEN|' . $name, 'template', true);
						return false;
					}
					
					if (isset($this->tagMaps['Case:' . $name]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_ALREADY_CLOSED|' . $name, 'template', true);
						return false;
					}
					
					$phpcode .= '<?php break; case \'' . addslashes($value) . '\': ?>';
					
					return $phpcode;
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
				
			case 'EOF':
				if (isset($params[1]) && preg_match('/^([A-Za-z]+)$/', $params[1], $matched)) {
					list($org, $name) = $matched;
					
					if (!isset($this->tagMaps['Case:' . $name]['Start'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_NOT_OPEN|' . $name, 'template', true);
						return false;
					}
					
					if ($unclosed = $this->doCheckUnclosedBetweenPositions($this->tagMaps['Case:' . $name]['Start'], $pos)) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_TAG_UNCLOSE|' . $unclosed, 'template', true);
						return false;
					}
					
					if (isset($this->tagMaps['Case:' . $name]['End'])) {
						facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_ALREADY_CLOSED|' . $name, 'template', true);
						return false;
					}
					
					$phpcode .= '<?php break; }} ?>';
					
					$this->tagMaps['Case:' . $name]['End'] = $pos;
					
					return $phpcode;
				} else {
					facula::core('debug')->exception('ERROR_TEMPLATE_COMPILER_CASE_FORMAT_INVALID|' . $format, 'template', true);
				}
				break;
				
			default:
				if (preg_match('/^([A-Za-z]+) (\$[A-Za-z0-9\_\'\"\[\]]+)$/', $format, $matched)) {
					list($org, $name, $variable) = $matched;
					
					if (!isset($this->tagMaps['Case:' . $name])) {
						$phpcode .= '<?php if (isset(' . $variable . ')) { switch(' . $variable . ') { default: ?>';
						
						$this->tagMaps['Case:' . $name] = array(
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