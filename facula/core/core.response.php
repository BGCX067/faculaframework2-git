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
		'GZIPEnabled' => false,
		'UseGZIP' => false,
	);
	
	public function __construct(&$cfg, &$common, facula $facula) {
		$this->configs = array(
			'CookiePrefix' => isset($common['CookiePrefix'][0]) ? $common['CookiePrefix'] : '',
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
		self::$headers[] = 'Set-Cookie: ';
		
		return true;
	}
}

?>