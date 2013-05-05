<?php

class faculaRequest extends coreTemplate implements Core {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static private $facula = null;
	
	private $configs = array(
		'MaxRequestSize' => 0,
		'MaxRequestBlocks' => 0,
		'AutoMagicQuotes' => false
	);
	
	private $pool = array();
	
	public $method = 'GET';
	
	protected function __construct(&$cfg, $facula) {
		if (function_exists('get_magic_quotes_gpc')) {
			$this->configs['AutoMagicQuotes'] = get_magic_quotes_gpc();
		}
		
		if (isset($cfg['MaxRequestSize'])) { // give memory_limit * 0.8 because our app needs memory to run, so memory cannot be 100%ly use for save request data;
			$this->configs['MaxRequestSize'] = min(
													(int)$cfg['MaxRequestSize'],
													$this->convertIniUnit(ini_get('post_max_size')), 
													$this->convertIniUnit(ini_get('memory_limit')) * 0.8
													);
		} else {
			$this->configs['MaxRequestSize'] = min(
													$this->convertIniUnit(ini_get('post_max_size')), 
													$this->convertIniUnit(ini_get('memory_limit')) * 0.8
													);
		}
		
		$this->configs['MaxRequestBlocks'] = isset($cfg['MaxRequestBlocks']) ? (int)$cfg['MaxRequestBlocks'] : 512; // We can handler up to 512 elements in _REQUEST array
		
		$cfg = null;
		unset($cfg);
		
		return true;
	}
	
	private function convertIniUnit($str) {
		$strLen = 0;
		$lastChar = '';
		
		if (is_numeric($lastChar)) {
			return (int)$str;
		} else {
			$strLen = strlen($str);
			$lastChar = $str[$strLen - 1];
			$strSelected = substr($str, 0, $strLen - 1);
			
			switch(strtolower($lastChar)) {
				case 'k':
					return (int)$strSelected * 1024;
					break;
					
				case 'm':
					return (int)$strSelected * 1048576;
					break;
					
				case 'g':
					return (int)$strSelected * 1073741824;
					break;
			}
		}
		
		return 0;
	}
	
	public function _inited() {
		global $_REQUEST, $_SERVER;
		$totalRequestSize = 0;
		
		if ($this->configs['AutoMagicQuotes']) { // Impossible by now
			foreach($_REQUEST AS $key => $val) {
				$_REQUEST[$key] = is_array($val) ? array_map('stripslashes', $val) : stripslashes($val);
			}
		}
		
		if (count($_REQUEST) > $this->configs['MaxRequestBlocks']) {
			facula::core('debug')->exception('ERROR_REQUEST_BLOCKS_OVERLIMIT', 'limit', true);
		} elseif (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > $this->configs['MaxRequestSize']) {
			facula::core('debug')->exception('ERROR_REQUEST_SIZE_OVERLIMIT', 'limit', true);
		}
		
		$this->method = $_SERVER['REQUEST_METHOD'];
		
		$this->pool = array(
			'REQUEST' => &$_REQUEST,
			'GET' => &$_GET,
			'POST' => &$_POST,
			'COOKIE' => &$_COOKIE,
		);
		
		return true;
	}
	
	public function get($type, $key) {
		$type = strtoupper($type);
		
		if (isset($this->pool[$type][$key])) {
			return $this->pool[$type][$key];
		}
		
		return false;
	}
	
	public function gets($type, $keys, $failfalse = false) {
		$result = array();
		
		if (is_array($keys)) {
			foreach($keys AS $key => $val) {
				if (isset($this->pool[$type][$key])) {
					$result[$key] = $this->pool[$type][$key];
				} elseif ($failfalse) {
					return false;
				}
			}
		}
		
		return isset($result[0]) ? $result : false;
	}
}





?>