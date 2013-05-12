<?php

interface coreInterface {
	static public function getInstance(&$cfg, $commonCfg, facula $parent);
	static public function checkInstance($instance);
}

// Yes, now i just need extends below core factory and everything will be just fine. 
// And you can still replace faculaCore with your own core
// I even starting wondering why i have so many cores in different 'core' that only for extends this class for running like a factory
abstract class faculaCoreFactory implements coreInterface { 
	static private $instances = array();
	
	static public final function getInstance(&$cfg, $commonCfg, facula $parent) {
		$caller = get_called_class();
		// If $cfg['Core'] has beed set, means user wants to use their own core instead of default one
		$class = $caller . (isset($cfg['Core'][0]) ? $cfg['Core'] : 'Default');
		
		if (!isset(self::$instances[$class])) {
			if (!class_exists($class)) {
				throw new Exception('Facula core ' . $class . ' is not loadable. Please make sure object file has been included before preform this task.');
			}
			
			// Create and check new instance
			if ($caller::checkInstance(self::$instances[$class] = new $class($cfg, $commonCfg, $parent))) {
				return self::$instances[$class];
			} else {
				throw new Exception('An error happened when facula creating core ' . $class . '.');
			}
		}
		
		return self::$instances[$class];
	}
}

?>