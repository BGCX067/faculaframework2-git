<?php

interface faculaResponseInterface {
	public function _inited();
	public function setHeader($header);
	public function setContent(&$content);
	public function send();
	public function setCookie($key, $val = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false);
}

class faculaResponse extends faculaCoreFactory {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static public function checkInstance($instance) {
		if ($instance instanceof faculaResponseInterface) {
			return true;
		} else {
			throw new Exception('Facula core ' . get_class($instance) . ' needs to implements interface \'faculaResponseInterface\'');
		}
		
		return  false;
	}
}

class faculaResponseDefault implements faculaResponseInterface {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static private $headers = array();
	static private $content = '';
	
	public $configs = array(
		'CookiePrefix' => 'facula_',
		'CookieExpire' => 3600,
		'GZIPEnabled' => false,
		'UseGZIP' => false,
	);
	
	public function __construct(&$cfg, &$common, facula $facula) {
		$this->configs = array(
			'CookiePrefix' => isset($common['CookiePrefix'][0]) ? $common['CookiePrefix'] : '',
			'CookieExpire' => isset($cfg['CookieExpireDefault'][0]) ? $cfg['CookieExpireDefault'] : 3600,
			'GZIPEnabled' => isset($cfg['UseGZIP']) && $cfg['UseGZIP'] && function_exists('gzcompress') ? true : false,
		);
		
		$cfg = null;
		unset($cfg);
		
		return true;
	}
	
	public function _inited() {
		self::$headers[] = 'Server: Facula Framework';
		self::$headers[] = 'X-Powered-By: Facula Framework ' . __FACULAVERSION__;
		self::$headers[] = 'Content-Type: text/html; charset=utf-8';
		
		if (facula::core('request')->getClientInfo('gzip')) {
			$this->configs['UseGZIP'] = true;
		}
		
		return true;
	}
	
	public function send() {
		ob_start();
		
		foreach(self::$headers AS $header) {
			header($header);
		}
		
		echo self::$content;
		
		ob_end_flush();
		
		// Belowing flush both needed.
		ob_flush();
		flush();
		
		return true;
	}
	
	public function setHeader($header) {
		self::$headers[] = $header;
		
		return true;
	}
	
	public function setContent(&$content) {
		if ($this->configs['UseGZIP'] && $this->configs['GZIPEnabled']) {
			self::$content = "\x1f\x8b\x08\x00\x00\x00\x00\x00".substr(gzcompress($content, 2), 0, -4);
			self::$headers[] = 'Content-Encoding: gzip';
		} else {
			self::$content = $content;
		}
		
		self::$headers[] = 'Content-Length: '.strlen(self::$content);
		
		return true;
	}
	
	public function setCookie($key, $val = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false) {
		global $_COOKIE;
		
		$cKey		= $this->configs['CookiePrefix'] . urlencode($key);
		$cVal		= $val ? urlencode($val) : '';
		$cExpire	= gmstrftime('%A, %d-%b-%Y %H:%M:%S GMT', $expire ? FACULA_TIME + intval($expire) : FACULA_TIME + $this->configs['CookieExpire']);
		$cPath		= $path;
		$cDomain	= $domain ? $domain : (strpos($_SERVER['HTTP_HOST'], '.') != -1 ? $_SERVER['HTTP_HOST'] : ''); // The little dot check for IEs
		$cSecure	= $secure ? ' Secure;' : '';
		$cHttponly	= $httponly ? ' HttpOnly;' : '';
		
		self::$headers[] = "Set-Cookie: {$cKey}={$cVal}; Domain={$cDomain}; Path={$cPath}; Expires={$cExpire};{$cSecure}{$cHttponly}";
		
		$_COOKIE[$this->configs['CookiePrefix'] . $key] = $val; // Assume we already successed. The value can be read immediately, no need to reload page.
		
		return true;
	}
	
	public function unsetCookie($key) {
		global $_COOKIE;
		
		$cKey = $this->configs['CookiePrefix'] . urlencode($key);
		$cExpire = gmstrftime('%A, %d-%b-%Y %H:%M:%S GMT', FACULA_TIME - 3600);
	
		self::$headers[] = "Set-Cookie: {$cKey}=NULL; Expires={$cExpire};";
		
		unset($_COOKIE[$this->configs['CookiePrefix'] . $key]); // Assume we already unset it
		
		return true;
	}
}

?>