<?php

/*****************************************************************************
	Facula Framework HTTP Responser

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

interface faculaResponseInterface {
	public function _inited();
	public function setHeader($header);
	public function setContent($content);
	public function send();
	public function setCookie($key, $val = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false);
	public function unsetCookie($key);
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
	
	public $configs = array();
	
	public function __construct(&$cfg, &$common, facula $facula) {
		$this->configs = array(
			'CookiePrefix' => isset($common['CookiePrefix'][0]) ? $common['CookiePrefix'] : 'facula_',
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
		} else {
			$this->configs['UseGZIP'] = false;
		}
		
		return true;
	}
	
	public function send() {
		$file = $line = '';
		
		if (!headers_sent($file, $line)) {
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
		} else {
			facula::core('debug')->exception('ERROR_RESPONSE_ALREADY_RESPONSED| File: ' . $file . ' Line: ' . $line, 'data');
		}
		
		return false;
	}
	
	public function setHeader($header) {
		self::$headers[] = $header;
		
		return true;
	}
	
	public function setContent($content) {
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