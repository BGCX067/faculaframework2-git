<?php

interface faculaResponseInterface {
	public function _inited();
	public function header($header);
	public function send();
	public function setCookie($key, $val = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false);
}

class faculaResponse extends faculaCores implements Core, faculaResponseInterface {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static private $headers = array();
	
	public $config = array(
		'CookiePrefix' => ''
	);
	
	public function __construct(&$cfg, &$common, facula $facula) {
		$this->config = array(
			'CookiePrefix' => isset($common['CookiePrefix'][0]) ? $common['CookiePrefix'] : '',
		);
		
		return true;
	}
	
	public function _inited() {
		self::$headers[] = 'Server: Facula Framework';
		self::$headers[] = 'X-Powered-By: Facula Framework';
		self::$headers[] = 'Content-Type: text/html; charset=utf-8';
	}
	
	public function header($header) {
		self::$headers[] = $header;
		
		return true;
	}
	
	public function send() {
		foreach(self::$headers AS $header) {
			header($header);
		}
		
		return true;
	}
	
	public function setCookie($key, $val = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false) {
		self::$headers[] = 'Set-Cookie: ';
		
		return true;
	}
}

?>