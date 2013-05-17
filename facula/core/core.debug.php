<?php

interface faculaDebugInterface {
	public function _inited();
	public function registerHandler($handler);
	public function error($errno, $errstr, $errfile, $errline, $errcontext);
	public function exception($info, $type = '', $exit = false);
	public function criticalSection($enter);
	public function addLog($type, $content);
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
	
	private $tempDisabled = false;
	
	private $configs = array();
	
	private $errorHandler = null;
	
	public function __construct(&$cfg, &$common) {
		$this->configs = array(
			'ExitOnAnyError' => isset($cfg['ExitOnAnyError']) ? $cfg['ExitOnAnyError'] : false,
			'LogRoot' => isset($cfg['LogRoot']) && is_dir($cfg['LogRoot']) ? $cfg['LogRoot'] : '',
			'Debug' => isset($cfg['Debug']) && $cfg['Debug'] ? true : false,
		);
		
		$cfg = null;
		unset($cfg);
		
		return true;
	}
	
	public function _inited() {
		error_reporting(E_ALL ^ E_NOTICE); // Mute php error reporter, yes, E_ALL ^ E_NOTICE is good enough.
		set_error_handler(array(&$this, 'error'), E_ALL); // Use our own error reporter, just like PHP's E_ALL
		
		return true;
	}
	
	public function addLog($type, $content) {
		global $_SERVER;
		
		if ($this->configs['LogRoot']) {
			list($time, $micro) = explode('.', microtime(true) . '.' . 0, 3); // Anit error when int returns instead of float
			
			$date = date('Y-m-d H', $time);
			
			$errorType = '['. strtoupper($type) . ']';
			
			$filename = 'log.' . $date . '.php';
			$format = "<?php exit(); ?> {$errorType} {$_SERVER['REMOTE_ADDR']} {$date}: {$content}";
		
			return file_put_contents($this->configs['LogRoot'] . DIRECTORY_SEPARATOR . $filename, $format . "\r\n", FILE_APPEND);
		}
		
		return false;
	}
	
	public function registerHandler($handler) {
		if (!$this->errorHandler) {
			if (is_callable($handler)) {
				$this->errorHandler = $handler;
			}
		} else {
			$this->exception('ERROR_HANDLER_ALREADY_REGISTERED', 'conflict');
		}
	}
	
	public function criticalSection($enter) {
		if ($enter) {
			$this->tempDisabled = true;
		} else {
			$this->tempDisabled = false;
		}
		
		return true;
	}
	
	public function error($errno, $errstr, $errfile, $errline, $errcontext) {
		$this->exception(sprintf('Error code %s (%s) in file %s line %s', 'PHP('.$errno.')', $errstr, $errfile, $errline), 'PHP', $this->configs['ExitOnAnyError']);
	}
	
	public function exception($info, $type = '', $exit = false) {
		$this->addLog($type ? $type : 'Exception', $info);
		
		if (!$this->tempDisabled) {
			if ($this->errorHandler) {
				$this->errorHandler($info, $this->configs['Debug'], $this->backtrace(), $exit);
			} else {
				$this->displayErrorBanner(new Exception($info), false, 0);
			}
		}
		
		if ($exit) { // You can exit anyway no matter status of $this->tempDisabled
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
						$tmpstr .= $val;
						break;
						
					case 'double':
						$tmpstr .= $val;
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
						$tmpstr .= 'Unknown';
						break;
				}
			}

			return $tmpstr;
		}
		
		return false;
	}
	
	private function backtrace($e = null) {
		$result = array();
		$trace = $e ? $e->getTrace() : debug_backtrace();
		
		array_shift($trace);
		
		foreach ($trace as $key => $val) {
			$result[] = array(
				'caller' => (isset($val['class']) ? $val['class'] . (isset($val['type']) ? $val['type'] : '::') : '') . (isset($val['function']) ? $val['function'] . '(' : 'main (') . (isset($val['args']) ? $this->getArgsType(', ', $val['args']) : '') . ')',
				'file' => $val['file'],
				'line' => $val['line'],
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
	
	private function displayErrorBanner(Exception $e, $returncode = false, $callerOffset = 0) {
		$code = '';
		
		if ($this->configs['Debug']) {
			$backtraces = array_reverse($this->backtrace($e));
			$traceSize = count($backtraces);
			$traceCallerOffset = $traceSize - ($callerOffset < $traceSize ? $callerOffset : 0);
			$tracesLoop = 0;
			
			$code = '<div class="facula-error" style="clear:both;"><span class="title" style="clear:both;font-size:150%;">Facula Error: <strong>' . str_replace(array(FACULA_ROOT, PROJECT_ROOT), array('[Facula Dir]', '[Project Dir]'), $e->getMessage()) . '</strong></span><ul>';
			
			foreach($backtraces as $key => $val) {
				$tracesLoop++;
				$code .= '<li' . ($tracesLoop == $traceCallerOffset ? ' class="current" style="padding:10px;background-color:#fcc;border-radius:5px;color:#a33;"' : ' style="padding:10px;"') . '><span style="line-height:1.5;"><span class="trace" style="display:block;font-size:120%;">' . str_replace(array(FACULA_ROOT, PROJECT_ROOT), array('[Facula Dir]', '[Project Dir]'), $val['caller']) . '</span><span class="notice" style="display:block;margin-bottom:3px;font-size:60%;">Author: <u>' . $val['nameplate']['author'] . '</u> Reviser: <u>' . $val['nameplate']['reviser'] . '</u> ' . ' Version: <u>' . $val['nameplate']['version'] . '</u> Updated in: <u>' . $val['nameplate']['updated'] . '</u> Contact: <u>' . ($val['nameplate']['contact'] ? $val['nameplate']['contact'] : 'Nobody') . '</u></span><span class="notice" style="display:block;font-size:60%;font-weight:bold;">Triggered in file: ' . str_replace(array(FACULA_ROOT, PROJECT_ROOT), array('[Facula Dir]', '[Project Dir]'), $val['file']) . ' (line ' . $val['line'] . ')' . '</span></span></li>'; 
			}
			
			$code .= '</ul></div>';
		} else {
			$code = '<div class="facula-error-min" style="text-align:center;clear:both;">Sorry, we got a problem when trying to cooking a page for you.</div>';
		}
		
		if ($returncode) {
			return $returncode;
		} else {
			echo($code);
		}
		
		return true;
	}
}

?>