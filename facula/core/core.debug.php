<?php

class faculaDebug extends coreTemplate implements Core {
	static private $facula = null;
	
	private $configs = array();
	
	protected function __construct($cfg, $facula) {
		self::$facula = $facula;
		$this->configs = $cfg;
		
		return true;
	}
	
	public function _inited() {
		set_error_handler(array(&$this, 'error'));
	}
	
	public function error($errno, $errstr, $errfile, $errline, $errcontext) {
		throw new Exception(sprintf('Facula Error: %s (%s) in file %s line %s', $errno, $errstr, $errfile, $errline));
	}
}

?>