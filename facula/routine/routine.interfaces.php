<?php

abstract class coreTemplate {
	static private $instances = array();
	
	static public final function getInstance(&$cfg, facula $parent) {
		$class = get_called_class();
		
		if (!isset(self::$instances[$class])) {
			return self::$instances[$class] = new $class($cfg, $parent);
		}
		
		return self::$instances[$class];
	}
}

interface Core {
	static public function getInstance(&$cfg, facula $parent);
}

interface Handler {
	public function get(faculaRequest &$request);
	public function post(faculaRequest &$request);
}

?>