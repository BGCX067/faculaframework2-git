<?php

abstract class faculaCores {
	static private $instances = array();
	
	static public final function getInstance(&$cfg, $commonCfg, facula $parent) {
		if (isset($cfg['Custom'][0])) { // If Custom has been set, will try to load user specified core
			$class = 'core' . ucfirst($cfg['Custom']);
			
			if (!class_exists($class)) {
				throw new Exception('Custom core ' . $class . ' is not loadable. Please make sure object file has been included before preform this task.');
			}
		} else {
			$class = get_called_class();
		}
		
		if (!isset(self::$instances[$class])) {
			return self::$instances[$class] = new $class($cfg, $commonCfg, $parent);
		}
		
		return self::$instances[$class];
	}
}

interface Core {
	static public function getInstance(&$cfg, $commonCfg, facula $parent);
}

interface Handler {
	public function get(faculaRequest &$request);
	public function post(faculaRequest &$request);
}

?>