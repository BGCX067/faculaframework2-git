<?php

interface faculaRequestInterface {
	public function _inited();
	public function get($type, $key, &$errored = false);
	public function gets($type, $keys, &$errors = array(), $failfalse = false);
	public function getCookie($key);
	public function getPost($key);
	public function getGet($key);
	public function getClientInfo($key);
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
		'GET' => 'get',
		'POST' => 'post',
		'PUT' => 'put',
		'HEAD' => 'head',
		'DELETE' => 'delete',
		'TRACE' => 'trace',
		'OPTIONS' => 'options',
		'CONNECT' => 'connect',
		'PATCH' => 'patch'
	);
	
	private $configs = array(
		'MaxRequestSize' => 0,
		'MaxRequestBlocks' => 0,
		'AutoMagicQuotes' => false,
		'CookiePrefix' => 'facula_',
	);
	
	private $pool = array();
	
	public $requestInfo = array();
	
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
		
		if ($this->configs['AutoMagicQuotes']) { // Impossible by now, remove all slash code back
			foreach($_REQUEST AS $key => $val) {
				$_REQUEST[$key] = is_array($val) ? array_map('stripslashes', $val) : stripslashes($val);
			}
		}
		
		if (count($_REQUEST) + count($_COOKIE) > $this->configs['MaxRequestBlocks']) { // Sec check: Request and cookie array element cannot exceed this
			facula::core('debug')->exception('ERROR_REQUEST_BLOCKS_OVERLIMIT', 'limit', true);
		} elseif (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > $this->configs['MaxRequestSize']) { // Sec check: Request size cannot large than this
			facula::core('debug')->exception('ERROR_REQUEST_SIZE_OVERLIMIT', 'limit', true);
		}
		
		$this->requestInfo['method'] = isset(self::$requestMethods[$_SERVER['REQUEST_METHOD']]) ? self::$requestMethods[$_SERVER['REQUEST_METHOD']] : 'get'; // Determine the type of request method.
		
		if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') != -1) { // Try to found out if our dear client support gzip
			$this->requestInfo['gzip'] = true;
		} else {
			$this->requestInfo['gzip'] = false;
		}
		
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) { // Found out which language that client using
			$lang = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'], 3); // No need to read all languages that client has
			
			foreach($lang AS $language) {
				$this->requestInfo['languages'][] = trim(strtolower(explode(';', $language, 2)[0]));
			}
			
			if (isset($this->requestInfo['languages'][0])) {
				$this->requestInfo['language'] = $this->requestInfo['languages'][0];
			}
		}
		
		if ($this->requestInfo['ip'] = tools::getUserIP(null, true)) { // Get client IP
			$this->requestInfo['ipArray'] = tools::splitIP($this->requestInfo['ip']);
		}
		
		if ($_SERVER['SERVER_PORT'] == 443) {
			$this->requestInfo['https'] = true; 
		} else {
			$this->requestInfo['https'] = false; 
		}
		
		$this->pool = array(
			'GET' => &$_GET,
			'POST' => &$_POST,
			'COOKIE' => &$_COOKIE,
		);
		
		return true;
	}
	
	public function getClientInfo($key) {
		if (isset($this->requestInfo[$key])) {
			return $this->requestInfo[$key];
		}
		
		return false;
	}
	
	public function getCookie($key) {
		return $this->get('COOKIE', $this->configs['CookiePrefix'] . $key);
	}
	
	public function getPost($key) {
		return $this->get('POST', $key);
	}
	
	public function getGet($key) {
		return $this->get('GET', $key);
	}
	
	public function get($type, $key, &$errored = false) {
		// Yeah, originally there we have a strtoupper here, But consider i'm not doing nurse, why i do that waste that function call for lazyer?
		if (isset($this->pool[$type][$key])) {
			return $this->pool[$type][$key];
		} else {
			$errored = true;
		}
		
		return false;
	}
	
	public function gets($type, $keys, &$errors = array(), $failfalse = false) {
		$result = array();
		
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