<?php

interface faculaObjectInterface {
	public function _inited();
	public function get($type, $name, $new = false, $justinclude = false);
	public function getFile($type, $name);
	public function runHandler(&$app);
}

class faculaObject extends faculaCoreFactory {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static public function checkInstance($instance) {
		if ($instance instanceof faculaObjectInterface) {
			return true;
		} else {
			throw new Exception('Facula core ' . get_class($instance) . ' needs to implements interface \'faculaObjectInterface\'');
		}
		
		return  false;
	}
}

class faculaObjectDefault implements faculaObjectInterface {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);

	static private $config = array(
		'CacheSafeCode' => '<?php /* Facula Object Cache */ exit(); ?>',
	);
	
	private $configs = array();
	
	private $instances = array();
	
	public function __construct(&$cfg, &$common, $facula) {
		$this->configs = array(
			'Paths' => $cfg['Paths'],
			'ObjectCacheRoot' => isset($cfg['ObjectCacheRoot']) ? $cfg['ObjectCacheRoot'] : '',
			'LibRoot' => isset($cfg['LibRoot']) && is_dir($cfg['LibRoot']) ? $cfg['LibRoot'] : '',
		);
		
		$cfg = null;
		unset($cfg);
		
		return true;
	}
	
	public function _inited() {
		spl_autoload_register(array(&$this, 'getAutoInclude'));
		
		return true;
	}
	
	private function getAutoInclude($classfile) {
		$classfileLower = strtolower($classfile);
		
		if (isset($this->configs['Paths']['base.'.$classfileLower])) { // Use path scope to locate file first
			return require_once($this->configs['Paths']['base.'.$classfileLower]['Path']);
		} elseif ($this->configs['LibRoot'] && strpos('\\', $classfile) != -1) { // If above not work, use namespace to locate file
			return require_once($this->configs['LibRoot'] . DIRECTORY_SEPARATOR . str_replace(array('\\', '/', '_'), DIRECTORY_SEPARATOR, ltrim($classfile, '\\')) . '.php');
		}
		
		return false;
	}
	
	private function &getObjectSetting($objName) {
		if (!isset($this->configs['Components'][$objName])) {
			$this->configs['Components'][$objName] = array(); // You'll get a array anyway
		}
		
		return $this->configs['Components'][$objName];
	}
	
	public function get($type, $name, $new = false, $justinclude = false) {
		$keyName = $type.'.'.strtolower($name);
		$className = $type.$name;
		$newobject = null;
		
		if (isset($this->configs['Paths'][$keyName])) { // isset obviously faster than $this->getFile call.
			if (!isset($this->instances[$keyName]['Loaded'])) { // If this is the first time we load this module
				// First of all, include file, If not success, return false
				
				if (include($this->configs['Paths'][$keyName]['Path'])) {
					$this->instances[$keyName]['Loaded'] = true;
					
					if ($justinclude) {
						return true;
					}
				}
				
				$new = true;
			}
			
			if ($new || !isset($this->instances[$keyName][0])) { // If need to create a new instance OR this is the first time this one been create
				if (class_exists($className)) {
					$newobject = new $className($this->getObjectSetting($keyName));
					
					$this->instances[$keyName][] = $newobject;
					
					return $newobject;
				} else {
					throw new Exception('Object ' . $className . ' cannot be found in file' . $this->configs['Paths'][$keyName]['Path']);
				}
			} else {
				return $this->instances[$keyName][count($this->instances[$keyName]) - 1];
			}
		}
		
		return false;
	}
	
	public function getFile($type, $name) {
		$keyName = $type.'.'.strtolower($name);
		
		if (isset($this->configs['Paths'][$keyName])) {
			return $this->configs['Paths'][$keyName];
		}
		
		return false;
	}
	
	// Start a handler
	public function runHandler(&$app) {
		$handler = null;
		
		/* 
			Handler is the primary entrance of website functions. 
			So like core, the instance of a handler shall be cached for further use.
			
			The rules is, all init operations like loading config or init auto class must be finished with in
			init so it can be cached.
			
			But if data related to dynamic request like $_REQUEST or user session data, must not be deal in init.
			For give a chance to load dynamic request, after handler has been success loaded, we will attempt call _inited(); 
			method in handler's instance.
		*/
		
		if (!$handler = $this->loadHandlerFromCache($app)) {
			if ($handler = $this->get('handler', $app)) {
				$this->saveHandlerToCache($app, $handler);
			} else {
				throw new Exception('Cannot initialize handler ' . $app);
			}
		}
		
		// After saving cache, call the _inited function, tell others we done unserializing / init
		if (method_exists($handler, '_inited')) {
			$handler->_inited();
		}
		
		// When inited has been ran, call run to get module a chance to select operate method.
		if (method_exists($handler, '_run')) {
			$handler->_run();
		}
		
		return $handler;
	}
	
	private function loadHandlerFromCache($handlerName) {
		$file = $this->configs['ObjectCacheRoot'] . DIRECTORY_SEPARATOR . 'cachedHandler.' . $handlerName . '.php';
		
		if ($this->configs['ObjectCacheRoot'] && is_readable($file)) {
			$this->get('handler', $handlerName, false, true); // Will just include file
			return unserialize(str_replace(self::$config['CacheSafeCode'], '', file_get_contents($file)));
		}
		
		return false;
	}
	
	private function saveHandlerToCache($handlerName, &$handlerInstance) {
		$file = $this->configs['ObjectCacheRoot'] . DIRECTORY_SEPARATOR . 'cachedHandler.' . $handlerName . '.php';
		
		if ($this->configs['ObjectCacheRoot'] && is_dir($this->configs['ObjectCacheRoot'])) {
			return file_put_contents($file, self::$config['CacheSafeCode'] . serialize($handlerInstance));
		}
		
		return false;
	}
}

?>