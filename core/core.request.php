<?php

/*****************************************************************************
	Facula Framework User Request Pre-Processing Unit
	
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

interface faculaRequestInterface {
	public function _inited();
	public function get($type, $key, &$errored = false);
	public function gets($type, $keys, &$errors = array(), $failfalse = false);
	public function getCookie($key);
	public function getPost($key);
	public function getGet($key);
	public function getPosts($keys, &$errors = array());
	public function getGets($keys, &$errors = array());
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

abstract class faculaRequestDefaultBase implements faculaRequestInterface {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static protected $requestMethods = array(
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
	
	protected $configs = array(
		'MaxHeaderSize' => 0,
		'MaxDataSize' => 0,
		'MaxRequestBlocks' => 0,
		'AutoMagicQuotes' => false,
		'CookiePrefix' => 'facula_',
	);
	
	protected $pool = array();
	
	public $requestInfo = array(
			'method' => 'get',
			'gzip' => false,
			'languages' => array(
				'en'
			),
			'language' => 'en',
			'https' => false,
			'auth' => array(
				'Username' => '',
				'Password' => '',
			),
			'ip' => '0.0.0.0',
			'ipArray' => array('0','0','0','0'),
			'forwarded' => false,
	);
	
	public function __construct(&$cfg, &$common, $facula) {
		global $_SERVER;
		
		if (function_exists('get_magic_quotes_gpc')) {
			$this->configs['AutoMagicQuotes'] = get_magic_quotes_gpc();
		}
		
		if (isset($cfg['MaxDataSize'])) { // give memory_limit * 0.8 because our app needs memory to run, so memory cannot be 100%ly use for save request data;
			$this->configs['MaxDataSize'] = min(
													(int)$cfg['MaxDataSize'],
													$this->convertIniUnit(ini_get('post_max_size')), 
													$this->convertIniUnit(ini_get('memory_limit')) * 0.8
													);
		} else {
			$this->configs['MaxDataSize'] = min(
													$this->convertIniUnit(ini_get('post_max_size')), 
													$this->convertIniUnit(ini_get('memory_limit')) * 0.8
													);
		}
		
		// CDN or approved proxy servers
		if (isset($cfg['TrustedProxies']) && is_array($cfg['TrustedProxies'])) {
			$proxyIPRange = $proxyIPTemp = array();
			
			if (defined('AF_INET6')) {
				$this->configs['TPVerifyFlags'] = FILTER_FLAG_IPV4 + FILTER_FLAG_IPV6;
			} else {
				$this->configs['TPVerifyFlags'] = FILTER_FLAG_IPV4;
			}
			
			foreach($cfg['TrustedProxies'] AS $proxy) {
				$proxyIPRange = explode('-', $proxy, 2);
				
				foreach($proxyIPRange AS $proxyIP) {
					if (!filter_var($proxyIP, FILTER_VALIDATE_IP, $this->configs['TPVerifyFlags'])) {
						throw new Exception($proxyIP . ' not a valid IP for proxy server.');
						break; break;
					}
				}
				
				if (isset($proxyIPRange[1])) {
					$proxyIPTemp[0] = (inet_pton($proxyIPRange[0]));
					$proxyIPTemp[1] = (inet_pton($proxyIPRange[1]));
					
					if ($proxyIPTemp[0] < $proxyIPTemp[1]) {
						$this->configs['TrustedProxies'][$proxyIPTemp[0]] = $proxyIPTemp[1];
					} elseif ($proxyIPTemp[0] > $proxyIPTemp[1]) {
						$this->configs['TrustedProxies'][$proxyIPTemp[1]] = $proxyIPTemp[0];
					} else {
						$this->configs['TrustedProxies'][$proxyIPTemp[0]] = false;
					}
				} else {
					$this->configs['TrustedProxies'][(inet_pton($proxyIPRange[0]))] = false;
				}
			}
		} else {
			$this->configs['TrustedProxies'] = array();
		}
		
		$this->configs['MaxRequestBlocks'] = isset($cfg['MaxRequestBlocks']) ? (int)$cfg['MaxRequestBlocks'] : 512; // We can handler up to 512 elements in _GET + _POST + _COOKIE + SERVER array
		$this->configs['MaxHeaderSize'] = isset($cfg['MaxHeaderSize']) ? (int)$cfg['MaxHeaderSize'] : 1024; // How long of the data we can handle.
		
		$this->configs['CookiePrefix'] = isset($common['CookiePrefix'][0]) ? $common['CookiePrefix'] : '';
		
		// Get environment variables
		
		// Get current root
		if (isset($common['SiteRootURL'][0])) {
			$this->requestInfo['rootURL'] = $common['SiteRootURL'];
		} else {
			$this->requestInfo['rootURL'] = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
		}
		
		// Get current absolute root
		if (isset($_SERVER['SERVER_ADDR']) && isset($_SERVER['SERVER_PORT'])) {
			$this->requestInfo['absRootURL'] = ($_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://') . $_SERVER['SERVER_ADDR'] . ($_SERVER['SERVER_PORT'] == 80 ? '' : ':' . $_SERVER['SERVER_PORT'] ) . $this->requestInfo['rootURL'];
		}
		
		$cfg = null;
		unset($cfg);
		
		return true;
	}
	
	public function _inited() {
		global $_GET, $_POST, $_COOKIE, $_SERVER;
		
		// Init all needed array if not set.
		if (!isset($_GET, $_POST, $_COOKIE, $_SERVER)) {
			$_SERVER = $_COOKIE = $_POST = $_GET = array();
		}
		
		if ((count($_GET) + count($_POST) + count($_COOKIE) + count($_SERVER)) > $this->configs['MaxRequestBlocks']) { // Sec check: Request array element cannot exceed this
			facula::core('debug')->exception('ERROR_REQUEST_BLOCKS_OVERLIMIT', 'limit', true);
		} elseif (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > $this->configs['MaxDataSize']) { // Sec check: Request size cannot large than this
			facula::core('debug')->exception('ERROR_REQUEST_SIZE_OVERLIMIT', 'limit', true);
		}
		
		if ($this->configs['AutoMagicQuotes']) { // Impossible by now, remove all slash code back
			foreach($_GET AS $key => $val) {
				$_GET[$key] = is_array($val) ? array_map('stripslashes', $val) : stripslashes($val);
			}
			
			foreach($_POST AS $key => $val) {
				$_POST[$key] = is_array($val) ? array_map('stripslashes', $val) : stripslashes($val);
			}
			
			foreach($_COOKIE AS $key => $val) {
				$_COOKIE[$key] = is_array($val) ? array_map('stripslashes', $val) : stripslashes($val);
			}
		}
		
		// Check the size and by the way, figure out client info
		foreach($_SERVER AS $key => $val) {
			if (!isset($val[$this->configs['MaxHeaderSize']])) {
				switch(strtoupper($key)) {
					case 'REQUEST_METHOD':
						$this->requestInfo['method'] = isset(self::$requestMethods[$val]) ? self::$requestMethods[$val] : 'get'; // Determine the type of request method.
						break;
						
					case 'HTTP_ACCEPT_ENCODING':
						if (strpos($val, 'gzip') !== false) { // Try to found out if our dear client support gzip
							$this->requestInfo['gzip'] = true;
						}
						break;
						
					case 'HTTP_ACCEPT_LANGUAGE':
						$lang = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'], 3); // No need to read all languages that client has
						
						foreach($lang AS $languageOrder => $language) {
							$this->requestInfo['languages'][$languageOrder] = trim(strtolower(explode(';', $language, 2)[0]));
						}
						
						if (isset($this->requestInfo['languages'][0])) {
							$this->requestInfo['language'] = $this->requestInfo['languages'][0];
						}
						break;
						
					case 'SERVER_PORT':
						if ($val == 443) {
							$this->requestInfo['https'] = true; 
						}
						break;
						
					case 'PHP_AUTH_USER':
						$this->requestInfo['auth'] = array(
							'Username' => $val,
							'Password' => isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '',
						);
						break;
						
					default:
						$this->requestInfo['Raw'][$key] = &$_SERVER[$key];
						break;
				}
			} else {
				facula::core('debug')->exception('ERROR_REQUEST_HEADER_SIZE_OVERLIMIT|' . $key, 'limit', true);
				
				return false;
				break;
			}
		}
		
		if ($this->requestInfo['ip'] = $this->getUserIP(null, true)) { // Get client IP
			$this->requestInfo['ipArray'] = $this->splitIP($this->requestInfo['ip']);
			
			if (isset($_SERVER['REMOTE_ADDR']) && $this->requestInfo['ip'] != $_SERVER['REMOTE_ADDR']) {
				$this->requestInfo['forwarded'] = true;
			}
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
	
	public function getPosts($keys, &$errors = array()) {
		return $this->gets('POST', $keys, $errors, false);
	}
	
	public function getGets($keys, &$errors = array()) {
		return $this->gets('GET', $keys, $errors, false);
	}
	
	public function get($type, $key, &$errored = false) {
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
					$result[$key] = null;
					$errors[] = $key;
				}
			}
		}
		
		return !empty($result) ? $result : false;
	}
	
	protected function getUserIP($ipstr = '', $outasstring = false) {
		global $_SERVER;
		$ip = '';
		$ips = array();
		$sForwardName = '';
		$checkProxy = false;
		
		if (!$ipstr) {
			// Check if proxy has been set, make sure 'HTTP_X_FORWARDED_FOR' at the first of list
			foreach(array('HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED') AS $sForwardKeyName) {
				if (isset($_SERVER[$sForwardKeyName])) {
					$sForwardName = $sForwardKeyName;
					$checkProxy = true;
					break;
				}
			}
			
			if ($checkProxy) {
				if (isset($_SERVER['REMOTE_ADDR'])) {
					if (!$this->checkProxyTrusted($_SERVER['REMOTE_ADDR']) || (($ip = $this->getRealIPAddrFromXForward($_SERVER[$sForwardName])) == '0.0.0.0')) {
						// If REMOTE_ADDR (Must be proxy's addr here) not in our trusted list OR No any server we can trust in X Forward, set the address to REMOTE_ADDR
						$ip = $_SERVER['REMOTE_ADDR'];
					}
				} else {
					$ip = '0.0.0.0';
				}
			} else {
				$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
			}
			
			return $outasstring ? $ip : self::splitIP($ip);
		} else {
			return $outasstring ? $ipstr : self::splitIP($ipstr);
		}
		
		return false;
	}
	
	protected function splitIP($ip) {
		return explode(':', str_replace('.', ':', $ip), 8); // Max is 8 for a IP addr
	}
	
	protected function getRealIPAddrFromXForward($x_forwarded_for) {
		$ips = array_reverse(explode(',', str_replace(' ', '', $x_forwarded_for)));
		
		foreach($ips AS $forwarded) {
			if (filter_var($forwarded, FILTER_VALIDATE_IP, $this->configs['TPVerifyFlags'])) {
				if (!$this->checkProxyTrusted($forwarded)) {
					return $forwarded;
					break;
				}
			} else {
				break;
			}
		}
		
		return '0.0.0.0';
	}
	
	protected function checkProxyTrusted($ip) {
		$bIP = inet_pton($ip);
		
		if (isset($this->configs['TrustedProxies'][$bIP])) {
			return true;
		}
		
		foreach($this->configs['TrustedProxies'] AS $start => $end) {
			if ($end && $bIP >= $start && $bIP <= $end) {
				return true;
				break;
			}
		}
		
		return false;
	}
	
	protected function convertIniUnit($str) {
		$strLen = 0;
		$lastChar = '';
		
		if (is_numeric($str)) {
			return (int)$str;
		} else {
			$strLen = strlen($str);
			
			if ($lastChar = $str[$strLen - 1]) {
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
		}
		
		return 0;
	}
}

class faculaRequestDefault extends faculaRequestDefaultBase {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
}

?>