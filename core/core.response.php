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
	
	static private $http_content_type = array(
		'evy' => 'application/envoy',
		'fif' => 'application/fractals',
		'spl' => 'application/futuresplash',
		'hta' => 'application/hta',
		'acx' => 'application/internet-property-stream',
		'hqx' => 'application/mac-binhex40',
		'doc' => 'application/msword',
		'dot' => 'application/msword',
		'*' => 'application/octet-stream',
		'bin' => 'application/octet-stream',
		'class' => 'application/octet-stream',
		'dms' => 'application/octet-stream',
		'exe' => 'application/octet-stream',
		'lha' => 'application/octet-stream',
		'lzh' => 'application/octet-stream',
		'oda' => 'application/oda',
		'axs' => 'application/olescript',
		'pdf' => 'application/pdf',
		'prf' => 'application/pics-rules',
		'p10' => 'application/pkcs10',
		'crl' => 'application/pkix-crl',
		'ai' => 'application/postscript',
		'eps' => 'application/postscript',
		'ps' => 'application/postscript',
		'rtf' => 'application/rtf',
		'setpay' => 'application/set-payment-initiation',
		'setreg' => 'application/set-registration-initiation',
		'xla' => 'application/vnd.ms-excel',
		'xlc' => 'application/vnd.ms-excel',
		'xlm' => 'application/vnd.ms-excel',
		'xls' => 'application/vnd.ms-excel',
		'xlt' => 'application/vnd.ms-excel',
		'xlw' => 'application/vnd.ms-excel',
		'msg' => 'application/vnd.ms-outlook',
		'sst' => 'application/vnd.ms-pkicertstore',
		'cat' => 'application/vnd.ms-pkiseccat',
		'stl' => 'application/vnd.ms-pkistl',
		'pot' => 'application/vnd.ms-powerpoint',
		'pps' => 'application/vnd.ms-powerpoint',
		'ppt' => 'application/vnd.ms-powerpoint',
		'mpp' => 'application/vnd.ms-project',
		'wcm' => 'application/vnd.ms-works',
		'wdb' => 'application/vnd.ms-works',
		'wks' => 'application/vnd.ms-works',
		'wps' => 'application/vnd.ms-works',
		'hlp' => 'application/winhlp',
		'bcpio' => 'application/x-bcpio',
		'cdf' => 'application/x-cdf',
		'z' => 'application/x-compress',
		'tgz' => 'application/x-compressed',
		'cpio' => 'application/x-cpio',
		'csh' => 'application/x-csh',
		'dcr' => 'application/x-director',
		'dir' => 'application/x-director',
		'dxr' => 'application/x-director',
		'dvi' => 'application/x-dvi',
		'gtar' => 'application/x-gtar',
		'gz' => 'application/x-gzip',
		'hdf' => 'application/x-hdf',
		'ins' => 'application/x-internet-signup',
		'isp' => 'application/x-internet-signup',
		'iii' => 'application/x-iphone',
		'js' => 'application/x-javascript',
		'latex' => 'application/x-latex',
		'mdb' => 'application/x-msaccess',
		'crd' => 'application/x-mscardfile',
		'clp' => 'application/x-msclip',
		'dll' => 'application/x-msdownload',
		'm13' => 'application/x-msmediaview',
		'm14' => 'application/x-msmediaview',
		'mvb' => 'application/x-msmediaview',
		'wmf' => 'application/x-msmetafile',
		'mny' => 'application/x-msmoney',
		'pub' => 'application/x-mspublisher',
		'scd' => 'application/x-msschedule',
		'trm' => 'application/x-msterminal',
		'wri' => 'application/x-mswrite',
		'cdf' => 'application/x-netcdf',
		'nc' => 'application/x-netcdf',
		'pma' => 'application/x-perfmon',
		'pmc' => 'application/x-perfmon',
		'pml' => 'application/x-perfmon',
		'pmr' => 'application/x-perfmon',
		'pmw' => 'application/x-perfmon',
		'p12' => 'application/x-pkcs12',
		'pfx' => 'application/x-pkcs12',
		'p7b' => 'application/x-pkcs7-certificates',
		'spc' => 'application/x-pkcs7-certificates',
		'p7r' => 'application/x-pkcs7-certreqresp',
		'p7c' => 'application/x-pkcs7-mime',
		'p7m' => 'application/x-pkcs7-mime',
		'p7s' => 'application/x-pkcs7-signature',
		'sh' => 'application/x-sh',
		'shar' => 'application/x-shar',
		'swf' => 'application/x-shockwave-flash',
		'sit' => 'application/x-stuffit',
		'sv4cpio' => 'application/x-sv4cpio',
		'sv4crc' => 'application/x-sv4crc',
		'tar' => 'application/x-tar',
		'tcl' => 'application/x-tcl',
		'tex' => 'application/x-tex',
		'texi' => 'application/x-texinfo',
		'texinfo' => 'application/x-texinfo',
		'roff' => 'application/x-troff',
		't' => 'application/x-troff',
		'tr' => 'application/x-troff',
		'man' => 'application/x-troff-man',
		'me' => 'application/x-troff-me',
		'ms' => 'application/x-troff-ms',
		'ustar' => 'application/x-ustar',
		'src' => 'application/x-wais-source',
		'cer' => 'application/x-x509-ca-cert',
		'crt' => 'application/x-x509-ca-cert',
		'der' => 'application/x-x509-ca-cert',
		'pko' => 'application/ynd.ms-pkipko',
		'zip' => 'application/zip',
		'au' => 'audio/basic',
		'snd' => 'audio/basic',
		'mid' => 'audio/mid',
		'rmi' => 'audio/mid',
		'mp3' => 'audio/mpeg',
		'aif' => 'audio/x-aiff',
		'aifc' => 'audio/x-aiff',
		'aiff' => 'audio/x-aiff',
		'm3u' => 'audio/x-mpegurl',
		'ra' => 'audio/x-pn-realaudio',
		'ram' => 'audio/x-pn-realaudio',
		'wav' => 'audio/x-wav',
		'bmp' => 'image/bmp',
		'cod' => 'image/cis-cod',
		'gif' => 'image/gif',
		'ief' => 'image/ief',
		'jpe' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'jfif' => 'image/pipeg',
		'svg' => 'image/svg+xml',
		'tif' => 'image/tiff',
		'tiff' => 'image/tiff',
		'ras' => 'image/x-cmu-raster',
		'cmx' => 'image/x-cmx',
		'ico' => 'image/x-icon',
		'pnm' => 'image/x-portable-anymap',
		'pbm' => 'image/x-portable-bitmap',
		'pgm' => 'image/x-portable-graymap',
		'ppm' => 'image/x-portable-pixmap',
		'rgb' => 'image/x-rgb',
		'xbm' => 'image/x-xbitmap',
		'xpm' => 'image/x-xpixmap',
		'xwd' => 'image/x-xwindowdump',
		'mht' => 'message/rfc822',
		'mhtml' => 'message/rfc822',
		'nws' => 'message/rfc822',
		'css' => 'text/css',
		'323' => 'text/h323',
		'htm' => 'text/html',
		'html' => 'text/html',
		'stm' => 'text/html',
		'uls' => 'text/iuls',
		'bas' => 'text/plain',
		'c' => 'text/plain',
		'h' => 'text/plain',
		'txt' => 'text/plain',
		'rtx' => 'text/richtext',
		'sct' => 'text/scriptlet',
		'tsv' => 'text/tab-separated-values',
		'htt' => 'text/webviewhtml',
		'htc' => 'text/x-component',
		'etx' => 'text/x-setext',
		'vcf' => 'text/x-vcard',
		'mp2' => 'video/mpeg',
		'mpa' => 'video/mpeg',
		'mpe' => 'video/mpeg',
		'mpeg' => 'video/mpeg',
		'mpg' => 'video/mpeg',
		'mpv2' => 'video/mpeg',
		'mov' => 'video/quicktime',
		'qt' => 'video/quicktime',
		'lsf' => 'video/x-la-asf',
		'lsx' => 'video/x-la-asf',
		'asf' => 'video/x-ms-asf',
		'asr' => 'video/x-ms-asf',
		'asx' => 'video/x-ms-asf',
		'avi' => 'video/x-msvideo',
		'movie' => 'video/x-sgi-movie',
		'flr' => 'x-world/x-vrml',
		'vrml' => 'x-world/x-vrml',
		'wrl' => 'x-world/x-vrml',
		'wrz' => 'x-world/x-vrml',
		'xaf' => 'x-world/x-vrml',
		'xof' => 'x-world/x-vrml',
	);
	
	public $configs = array();
	
	public function __construct(&$cfg, &$common, facula $facula) {
		$setting = array();
		
		$this->configs = array(
			'CookiePrefix' => isset($common['CookiePrefix'][0]) ? $common['CookiePrefix'] : 'facula_',
			'GZIPEnabled' => isset($cfg['UseGZIP']) && $cfg['UseGZIP'] && function_exists('gzcompress') ? true : false,
			'PSignal' => isset($cfg['PostProfileSignal']) && $cfg['PostProfileSignal'] ? true : false,
			'UseFFR' => function_exists('fastcgi_finish_request') ? true : false
		);
		
		$cfg = null;
		unset($cfg);
		
		return true;
	}
	
	public function _inited() {
		self::$headers[] = 'Server: Facula Framework';
		self::$headers[] = 'X-Powered-By: Facula Framework ' . __FACULAVERSION__;
		
		if (facula::core('request')->getClientInfo('gzip')) {
			$this->configs['UseGZIP'] = true;
		} else {
			$this->configs['UseGZIP'] = false;
		}
		
		return true;
	}
	
	public function send($type = '', $persistConn = false) {
		$file = $line = '';
		
		if (!headers_sent($file, $line)) {
			// Assume we will finish this application after output, calc belowing profile data
			facula::$profile['MemoryUsage'] = memory_get_usage(true);
			facula::$profile['MemoryPeak'] = memory_get_peak_usage(true);
			
			facula::$profile['OutputTime'] = microtime(true);
			facula::$profile['ProductionTime'] = facula::$profile['OutputTime'] - facula::$profile['StartTime'];
			
			// Start buffer to output
			ob_start();
			
			if (isset(self::$http_content_type[$type])) {
				header('Content-Type: ' . self::$http_content_type[$type] . '; charset=utf-8');
			} else {
				header('Content-Type: ' . self::$http_content_type['html'] . '; charset=utf-8');
			}
			
			if ($this->configs['PSignal']) {
				header('X-Runtime: ' . (facula::$profile['ProductionTime']  * 1000) . 'ms');
				header('X-Memory: ' . (facula::$profile['MemoryUsage'] / 1024) . 'kb / ' . (facula::$profile['MemoryPeak'] / 1024) . 'kb');
			}
			
			foreach(self::$headers AS $header) {
				header($header);
			}
			
			if ($persistConn) {
				header('Connection: Keep-Alive');
			} else {
				header('Connection: Close');
			}
			
			echo self::$content;
			
			ob_end_flush();
			
			if ($this->configs['UseFFR']) {
				fastcgi_finish_request();
			}
			
			flush();
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_RESPONSE_ALREADY_RESPONSED|File: ' . $file . ' Line: ' . $line, 'data');
		}
		
		return false;
	}
	
	public function setHeader($header) {
		self::$headers[] = $header;
		
		return true;
	}
	
	public function setContent($content) {
		$orgSize = $gzSize = 0;
		$gzContent = '';
		
		$orgSize = strlen($content);
		
		if ($this->configs['UseGZIP'] && $this->configs['GZIPEnabled'] && $orgSize >= 2048) {
			$gzContent = gzcompress($content, 2);
			$gzSize = strlen($gzContent);
			
			self::$content = "\x1f\x8b\x08\x00\x00\x00\x00\x00" . substr($gzContent, 0, $gzSize - 4);
			
			self::$headers[] = 'Vary: Accept-Encoding';
			self::$headers[] = 'Content-Encoding: gzip';
			self::$headers[] = 'X-Length: ' . $gzSize . ' bytes / ' . $orgSize . ' bytes';
		} else {
			self::$content = $content;
			
			self::$headers[] = 'X-Length: ' . $orgSize . ' bytes';
		}
		
		self::$headers[] = 'Content-Length: ' . strlen(self::$content);
		
		return true;
	}
	
	public function setCookie($key, $val = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false) {
		global $_COOKIE;
		
		$cKey		= $this->configs['CookiePrefix'] . urlencode($key);
		$cVal		= $val ? urlencode($val) : 'false';
		$cExpire	= $expire ? gmstrftime('%A, %d-%b-%Y %H:%M:%S GMT', FACULA_TIME + intval($expire)) : 'false';
		$cPath		= $path;
		$cDomain	= $domain ? $domain : (strpos($_SERVER['HTTP_HOST'], '.') != -1 ? $_SERVER['HTTP_HOST'] : ''); // The little dot check for IEs
		$cSecure	= $secure ? ' Secure;' : '';
		$cHttponly	= $httponly ? ' HttpOnly;' : '';
		
		// Add cookie to the top of http header
		array_unshift(self::$headers, "Set-Cookie: {$cKey}={$cVal}; Domain={$cDomain}; Path={$cPath}; Expires={$cExpire};{$cSecure}{$cHttponly}");
		
		$_COOKIE[$this->configs['CookiePrefix'] . $key] = $val; // Assume we already successed. The value can be read immediately, no need to reload page.
		
		return true;
	}
	
	public function unsetCookie($key) {
		$this->setCookie($key, null, -FACULA_TIME);
		
		unset($_COOKIE[$this->configs['CookiePrefix'] . $key]); // Assume we already unset it
		
		return true;
	}
}

?>