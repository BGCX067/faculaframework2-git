<?php

/*****************************************************************************
	Facula Framework Debug Manager
	
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

interface faculaDebugInterface {
	public function _inited();
	public function registerHandler($handler);
	public function exception($info, $type = '', $exit = false, Exception $e = null);
	public function criticalSection($enter, $fullEnter = false);
	public function addLog($type, $errorcode, $content, &$backtraces = array());
	
	public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext);
	public function exceptionHandler($exception);
	public function fatalHandler();
}

class faculaDebug extends faculaCoreFactory {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static public function checkInstance($instance) {
		if ($instance instanceof faculaDebugInterface) {
			return true;
		} else {
			throw new Exception('Facula core ' . get_class($instance) . ' needs to implements interface \'faculaDebugInterface\'');
		}
		
		return  false;
	}
}

class faculaDebugDefault implements faculaDebugInterface {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	private $errorRecords = array();
	
	private $tempDisabled = false;
	private $tempFullDisabled = false;
	
	private $configs = array();
	
	private $customHandler = null;
	
	public function __construct(&$cfg, &$common) {
		$this->configs = array(
			'ExitOnAnyError' => isset($cfg['ExitOnAnyError']) ? $cfg['ExitOnAnyError'] : false,
			'LogRoot' => isset($cfg['LogRoot']) && is_dir($cfg['LogRoot']) ? $cfg['LogRoot'] : '',
			'LogServer' => array(
				'Addr' => isset($cfg['LogServerInterface'][0]) ? $cfg['LogServerInterface'] : '',
				'Key' => isset($cfg['LogServerKey'][0]) ? $cfg['LogServerKey'] : '',
			),
			'Debug' => isset($cfg['Debug']) && $cfg['Debug'] ? true : false,
		);
		
		$cfg = null;
		unset($cfg);
		
		return true;
	}
	
	public function _inited() {
		set_error_handler(array(&$this, 'errorHandler'), E_ALL); // Use our own error reporter, just like PHP's E_ALL
		set_exception_handler(array(&$this, 'exceptionHandler')); // Use our own exception reporter
		register_shutdown_function(array(&$this, 'fatalHandler')); // Experimentally use our own fatal reporter
		
		if (isset($this->configs['LogServer']['Addr'][0])) {
			register_shutdown_function(array(&$this, 'reportError')); // Hook up the reportError so we can post error to log server
			$this->configs['LogServer']['Enabled'] = true;
		} else {
			$this->configs['LogServer']['Enabled'] = false;
		}
		
		// error_reporting(E_ALL &~ (E_NOTICE | E_WARNING | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_USER_DEPRECATED)); // Mute php error reporter from most errors
		error_reporting(E_ALL &~ (E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_PARSE | E_ERROR | E_WARNING | E_DEPRECATED | E_NOTICE | E_USER_ERROR | E_USER_WARNING | E_USER_DEPRECATED | E_USER_NOTICE));
		
		return true;
	}
	
	public function addLog($type, $errorcode, $content = '', &$backtraces = array()) {
		global $_SERVER;
		
		list($time, $micro) = explode('.', microtime(true) . '.' . 0, 3); // Anit error when int returns instead of float
		$date = date('l dS \of F Y h:i:s A', $time);
		
		if ($this->configs['LogRoot']) {
			$datefileName = date('Y-m-d H', $time);
			$errorType = '[' . strtoupper($type) . ']' . ($errorcode ? ':' . $errorcode : '');
			
			$filename = 'log.' . $datefileName . '.php';
			$format = "<?php exit(); ?> {$errorType} {$_SERVER['REMOTE_ADDR']} ({$date}.{$micro}): {$content}";
			
			return file_put_contents($this->configs['LogRoot'] . DIRECTORY_SEPARATOR . $filename, $format . "\r\n", FILE_APPEND);
		}
		
		$this->errorRecords[] = array(
			'Time' => $date,
			'Type' => $type,
			'ErrorNo' => $errorcode,
			'Content' => $content,
			'Backtraces' => $backtraces,
			'IP' => $_SERVER['REMOTE_ADDR'],
		);
		
		return false;
	}
	
	public function reportError() {
		if (!empty($this->errorRecords) && $this->configs['LogServer']['Enabled']) {
			$app = facula::getCoreInfo();
			
			$data = array(
				'KEY' => $this->configs['LogServer']['Key'],
				'APP' => $app['App'],
				'VER' => $app['Ver'],
				'ERRNO' => isset($this->errorRecords[0]['ErrorNo']) ? $this->errorRecords[0]['ErrorNo'] : 'Default Error No',
				'DATA' => json_encode($this->errorRecords),
			);
			
			$http = array(
				'http' => array(
					'method' => 'POST',
					'header' => "Content-type: application/x-www-form-urlencoded\r\n".
								"User-Agent: Facula Framework Debug Reporter\r\n",
					'timeout'=> 5,
					'content' => http_build_query($data, '', '&'), 
				),
			);
			
			$this->criticalSection(true);
			$result = file_get_contents($this->configs['LogServer']['Addr'], false, stream_context_create($http));
			$this->criticalSection(false);
			
			if ($result) {
				return true;
			} else {
				return false;
			}
		}
		
		return false;
	}
	
	public function registerHandler($handler) {
		if (!$this->customHandler) {
			if (is_callable($handler)) {
				$this->customHandler = $handler;
			}
		} else {
			$this->exception('ERROR_HANDLER_ALREADY_REGISTERED', 'conflict', true);
		}
	}
	
	public function criticalSection($enter, $fullEnter = false) {
		if ($enter) {
			$this->tempDisabled = true;
			
			if ($fullEnter) { // Disable all error message and logging
				$this->tempFullDisabled = true;
			}
		} else {
			$this->tempFullDisabled = $this->tempDisabled = false;
		}
		
		return true;
	}
	
	public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		$exit = false;
		
		switch($errno) {
			case E_ERROR:
				$exit = true;
				break;
				
			case E_PARSE:
				$exit = true;
				break;
				
			case E_CORE_ERROR:
				$exit = true;
				break;
				
			case E_CORE_WARNING:
				$exit = true;
				break;
				
			case E_COMPILE_ERROR:
				$exit = true;
				break;
				
			case E_COMPILE_WARNING:
				$exit = true;
				break;
				
			case E_USER_ERROR:
				$exit = true;
				break;
		}
		
		$this->exception(sprintf('Error code %s (%s) in file %s line %s', 'PHP('.$errno.')', $errstr, $errfile, $errline), 'PHP|PHP:' . $errno, !$exit ? $this->configs['ExitOnAnyError'] : true);
	}
	
	public function exceptionHandler($exception) {
		$this->exception('Exception: ' . $exception->getMessage(), 'Exception', true, $exception);
	}
	
	public function fatalHandler() { // http://stackoverflow.com/questions/277224/how-do-i-catch-a-php-fatal-error/277230#277230
		$errfile = 'Unknown file';
		$errstr  = '';
		$errno   = E_CORE_ERROR;
		$errline = 0;
		
		if($error = error_get_last()) {
			$errno   = $error['type'];
			$errfile = $error['file'];
			$errline = $error['line'];
			$errstr  = $error['message'];
			
			return $this->errorHandler($errno, $errstr, $errfile, $errline, null);
		}
		
		return false;
	}
	
	public function exception($info, $type = '', $exit = false, Exception $e = null) {
		if (!$this->tempFullDisabled) {
			$backtraces = array_reverse($this->backtrace($e));
			
			$types = explode('|', $type, 2);
			
			$this->addLog($types[0] ? $types[0] : 'Exception', isset($types[1][0]) ? $types[1] : '', $info, $backtraces);
			
			if (!$this->tempDisabled) {
				if ($this->customHandler) {
					$customHandler = $this->customHandler;
					$customHandler($info, $type, $backtraces, $exit, $this->configs['Debug']);
				} else {
					if ($e) {
						$this->displayErrorBanner($e->getMessage(), $backtraces, false, 0);
					} else {
						$this->displayErrorBanner($info, $backtraces, false, 2);
					}
				}
			}
		}
		
		if ($exit) {
			exit();
		}
		
		return true;
	}
	
	private function getArgsType($split, $array = array()) {
		$tmpstr = '';
		
		if (is_array($array)) {
			foreach($array AS $key => $val) {
				if ($tmpstr) {
					$tmpstr .= $split;
				}
				
				switch(gettype($val)) {
					case 'boolean':
						$tmpstr .= $val ? 'true' : 'false';
						break;
						
					case 'integer':
						$tmpstr .= 'integer ' . $val;
						break;
						
					case 'double':
						$tmpstr .= 'double ' . $val;
						break;
						
					case 'string':
						$tmpstr .= '\'' . $val . '\'';
						break;
						
					case 'array':
						$tmpstr .= 'array';
						break;
						
					case 'object':
						$tmpstr .= 'object ' . get_class($val);
						break;
						
					case 'resource':
						$tmpstr .= 'resource ' . get_resource_type($val);
						break;
					
					default:
						$tmpstr .= 'Unknown / Null';
						break;
				}
			}

			return $tmpstr;
		}
		
		return false;
	}
	
	private function backtrace($e = null) {
		$result = array();
		
		if ($e) {
			$trace = $e->getTrace();
		} else {
			$trace = debug_backtrace();
			array_shift($trace);
		}
		
		foreach ($trace as $key => $val) {
			$result[] = array(
				'caller' => (isset($val['class']) ? $val['class'] . (isset($val['type']) ? $val['type'] : '::') : '') . (isset($val['function']) ? $val['function'] . '(' : 'main (') . (isset($val['args']) ? $this->getArgsType(', ', $val['args']) : '') . ')',
				'file' => isset($val['file']) ? $val['file'] : null,
				'line' => isset($val['line']) ? $val['line'] : null,
				'nameplate' => isset($val['class']) && isset($val['class']::$plate) ? array(
					'author' => isset($val['class']::$plate['Author'][0]) ? $val['class']::$plate['Author'] : 'Undeclared',
					'reviser' => isset($val['class']::$plate['Reviser'][0]) ? $val['class']::$plate['Reviser'] : 'Undeclared',
					'contact' => isset($val['class']::$plate['Contact'][0]) ? $val['class']::$plate['Contact'] : '',
					'updated' => isset($val['class']::$plate['Updated'][0]) ? $val['class']::$plate['Updated'] : 'Undeclared',
					'version' => isset($val['class']::$plate['Version'][0]) ? $val['class']::$plate['Version'] : 'Undeclared',
				) : array(
					'author' => 'Nobody',
					'reviser' => 'Nobody',
					'contact' => '',
					'updated' => 'Undeclared',
					'version' => 'Undeclared',
				)
			);
		}
		
		return $result;
	}
	
	private function displayErrorBanner($message, $backtraces, $returncode = false, $callerOffset = 0) {
		$code = '';
		
		if (!headers_sent()) {
			if ($this->configs['Debug']) {
				$code = '<div class="facula-error" style="clear:both;"><span class="title" style="clear:both;font-size:150%;">Facula Error: <strong>' . str_replace(array(FACULA_ROOT, PROJECT_ROOT), array('[Facula Dir]', '[Project Dir]'), $message) . '</strong></span><ul>';
				
				if ($traceSize = count($backtraces)) {
					$traceCallerOffset = $traceSize - ($callerOffset < $traceSize ? $callerOffset : 0);
					$tracesLoop = 0;
					
					foreach($backtraces as $key => $val) {
						$tracesLoop++;
						$code .= '<li' . ($tracesLoop >= $traceCallerOffset ? ' class="current" style="margin:10px;padding:10px;background-color:#fcc;border-radius:5px;color:#a33;"' : ' style="padding:10px;"') . '><span style="line-height:1.5;"><span class="trace" style="display:block;font-size:120%;">' . str_replace(array(FACULA_ROOT, PROJECT_ROOT), array('[Facula Dir]', '[Project Dir]'), $val['caller']) . '</span><span class="notice" style="display:block;margin-bottom:3px;font-size:60%;">Author: <u>' . $val['nameplate']['author'] . '</u> Reviser: <u>' . $val['nameplate']['reviser'] . '</u> ' . ' Version: <u>' . $val['nameplate']['version'] . '</u> Updated in: <u>' . $val['nameplate']['updated'] . '</u> Contact: <u>' . ($val['nameplate']['contact'] ? $val['nameplate']['contact'] : 'Nobody') . '</u></span><span class="notice" style="display:block;font-size:60%;font-weight:bold;">Triggered in file: ' . str_replace(array(FACULA_ROOT, PROJECT_ROOT), array('[Facula Dir]', '[Project Dir]'), $val['file']) . ' (line ' . $val['line'] . ')' . '</span></span></li>'; 
					}
				}
				
				$code .= '</ul></div>';
			} else {
				$code = '<div class="facula-error-min" style="text-align:center;clear:both;">Sorry, we got a problem while cooking the page for you.</div>';
			}
			
			if ($returncode) {
				return $returncode;
			} else {
				echo($code);
			}
		}
		
		return true;
	}
}

?>