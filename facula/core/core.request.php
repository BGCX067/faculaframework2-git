<?php

interface faculaRequestInterface {
	public function _inited();
	public function get($type, $key, &$errored = false);
	public function gets($type, $keys, &$errors = array(), $failfalse = false);
	public function getCookie($key, $val);
	public function getPost($key, $val);
	public function getGet($key, $val);
}

class faculaRequest extends faculaCoreFactory {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static public function checkInstance($instance) {
		if ($instance instanceof faculaRequestInterface) {
			return true;
		} else {
			throw new Exception('Facula core ' . get_class($instance) . ' needs to implements interface \'faculaRequestInterface\'');
		}
		
		return  false;
	}
}

class faculaRequestDefault implements faculaRequestInterface {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static private $requestMethods = array(
		'' => 'GET', // empty as default huh
		'GET' => 'GET',
		'POST' => 'POST',
		'PUT' => 'PUT',
		'HEAD' => 'HEAD'
	);
	
	private $configs = array(
		'MaxRequestSize' => 0,
		'MaxRequestBlocks' => 0,
		'AutoMagicQuotes' => false,
		'CookiePrefix' => 'facula_',
	);
	
	private $pool = array();
	
	public $method = 'GET';
	public $gzip = false;
	public $language = 'en';
	public $languages = array();
	
	public function __construct(&$cfg, &$common, $facula) {
		if (function_exists('get_magic_quotes_gpc')) {
			$this->configs['AutoMagicQuotes'] = get_magic_quotes_gpc();
		}
		
		if (isset($cfg['MaxRequestSize'])) { // give memory_limit * 0.8 because our app needs memory to run, so memory cannot be 100%ly use for save request data;
			$this->configs['MaxRequestSize'] = min(
													(int)$cfg['MaxRequestSize'],
													tools::convertIniUnit(ini_get('post_max_size')), 
													tools::convertIniUnit(ini_get('memory_limit')) * 0.8
													);
		} else {
			$this->configs['MaxRequestSize'] = min(
													tools::convertIniUnit(ini_get('post_max_size')), 
													tools::convertIniUnit(ini_get('memory_limit')) * 0.8
													);
		}
		
		$this->configs['MaxRequestBlocks'] = isset($cfg['MaxRequestBlocks']) ? (int)$cfg['MaxRequestBlocks'] : 512; // We can handler up to 512 elements in _REQUEST array
		
		$this->configs['CookiePrefix'] = isset($common['CookiePrefix'][0]) ? $common['CookiePrefix'] : '';
		
		$cfg = null;
		unset($cfg);
		
		return true;
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
		
		$this->method = self::$requestMethods[$_SERVER['REQUEST_METHOD']]; // Determine the type of request method.
		
		if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') != -1) { // Try to found out if our dear client support gzip
			$this->gzip = true;
		}
		
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$lang = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'], 3); // No need to read all language that client has
			
			foreach($lang AS $language) {
				$this->languages[] = trim(strtolower(explode(';', $language, 2)[0]));
			}
			
			if (isset($this->languages[0])) {
				$this->language = $this->languages[0];
			}
		}
		
		$this->pool = array(
			'GET' => &$_GET,
			'POST' => &$_POST,
			'COOKIE' => &$_COOKIE,
		);
		
		return true;
	}
	
	public function getCookie($key, $val) {
		return $this->get('COOKIE', $this->configs['CookiePrefix'] . $key, $val);
	}
	
	public function getPost($key, $val) {
		return $this->get('POST', $key, $val);
	}
	
	public function getGet($key, $val) {
		return $this->get('GET', $key, $val);
	}
	
	public function get($type, $key, &$errored = false) {
		$type = strtoupper($type);
		
		if (isset($this->pool[$type][$key])) {
			return $this->pool[$type][$key];
		} else {
			$errored = true;
		}
		
		return false;
	}
	
	public function gets($type, $keys, &$errors = array(), $failfalse = false) {
		$result = array();
		$type = strtoupper($type);
		
		if (is_array($keys)) {
			foreach($keys AS $key) {
				if (isset($this->pool[$type][$key])) {
					$result[$key] = $this->pool[$type][$key] ? $this->pool[$type][$key] : null;
				} elseif ($failfalse) {
					return false;
				} else {
					$errors[] = $key;
				}
			}
		}
		
		return isset($result[0]) ? $result : false;
	}
}

?>