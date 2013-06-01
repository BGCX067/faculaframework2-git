<?php

interface faculaObjectInterface {
	public function _inited();
	public function get($type, $name, $new = false, $justinclude = false, $cache = false);
	public function getFile($type, $name);
	public function getInstance($object, $ags, $cache = false);
	public function run(&$app, $cache = false);
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
		'CacheSafeCode' => array(
			'<?php if (!defined(\'IN_FACULA\')) {exit(\'Access Denied\');} ',
			' ?>',
		),
	);
	
	private $configs = array();
	
	private $instances = array();
	
	public function __construct(&$cfg, &$common, $facula) {
		$this->configs = array(
			'Paths' => $cfg['Paths'],
			'ObjectCacheRoot' => isset($cfg['ObjectCacheRoot']) && is_dir($cfg['ObjectCacheRoot']) ? $cfg['ObjectCacheRoot'] : '',
			'LibRoot' => isset($cfg['LibrariesRoot']) && is_dir($cfg['LibrariesRoot']) ? $cfg['LibrariesRoot'] : '',
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
		
		if (isset($this->configs['Paths']['class.' . $classfileLower])) { // Use path scope to locate file first
			return require_once($this->configs['Paths']['class.' . $classfileLower]['Path']);
		} elseif ($this->configs['LibRoot'] && strpos($classfile, '\\') !== false) { // If above not work, use namespace to locate file
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
	
	private function loadObjectFromCache($objectName, $type = '', $uniqueid = '') {
		$instance = null;
		$cache = '';
		
		if ($this->configs['ObjectCacheRoot']) {
			$file = $this->configs['ObjectCacheRoot'] . DIRECTORY_SEPARATOR . 'cachedObject.' . ($type ? $type : 'general') . '#' . str_replace(array('\\', '/'), '%', $objectName) . '#' . ($uniqueid ? $uniqueid : 'common') . '.php';
			
			if (is_readable($file)) {
				require($file);
				
				if ($instance = unserialize($cache)) {				
					return $instance;
				}
			}
		}
		
		return false;
	}
	
	private function saveObjectToCache($objectName, $instance, $type = '', $uniqueid = '') {
		if ($this->configs['ObjectCacheRoot']) {
			$instance->cachedObjectFilePath = $file = $this->configs['ObjectCacheRoot'] . DIRECTORY_SEPARATOR . 'cachedObject.' . ($type ? $type : 'general') . '#' . str_replace(array('\\', '/'), '%', $objectName) . '#' . ($uniqueid ? $uniqueid : 'common') . '.php';
			
			return file_put_contents($file, self::$config['CacheSafeCode'][0] . '$cache = \'' . serialize($instance) . '\'' . self::$config['CacheSafeCode'][1]);
		}
		
		return false;
	}
	
	public function getInstance($object, $ags, $cache = false) {
		$newinstance = null;
		
		if (class_exists($object)) {
			if ($cache && ($newinstance = $this->loadObjectFromCache($object))) {
				if (method_exists($newinstance, '_inited')) {
					$newinstance->_inited();
				}
				
				return $newinstance;
			} else {
				switch(count($ags)) {
					case 0:
						$newinstance = new $object();
						break;
				
					case 1:
						$newinstance = new $object($ags[0]);
						break;
						
					case 2:
						$newinstance = new $object($ags[0], $ags[1]);
						break;
						
					case 3:
						$newinstance = new $object($ags[0], $ags[1], $ags[2]);
						break;
						
					case 4:
						$newinstance = new $object($ags[0], $ags[1], $ags[2], $ags[3]);
						break;
						
					case 5:
						$newinstance = new $object($ags[0], $ags[1], $ags[2], $ags[3], $ags[4]);
						break;
						
					case 6:
						$newinstance = new $object($ags[0], $ags[1], $ags[2], $ags[3], $ags[4], $ags[5]);
						break;
						
					case 7:
						$newinstance = new $object($ags[0], $ags[1], $ags[2], $ags[3], $ags[4], $ags[5], $ags[6]);
						break;
						
					case 8:
						$newinstance = new $object($ags[0], $ags[1], $ags[2], $ags[3], $ags[4], $ags[5], $ags[6], $ags[7]);
						break;
						
					case 9:
						$newinstance = new $object($ags[0], $ags[1], $ags[2], $ags[3], $ags[4], $ags[5], $ags[6], $ags[7], $ags[8]);
						break;
						
					case 10:
						$newinstance = new $object($ags[0], $ags[1], $ags[2], $ags[3], $ags[4], $ags[5], $ags[6], $ags[7], $ags[8], $ags[9]);
						break;
						
					default:
						facula::core('debug')->exception('ERROR_OBJECT_NEWINSTNACE_MAXPARAMEXCEEDED', 'object', true);
						break;
				}
				
				// Save first
				if ($cache) {
					$this->saveObjectToCache($object, $newinstance);
				}
				
				// Then call inited to notify object we already done init
				if (method_exists($newinstance, '_inited')) {
					$newinstance->_inited();
				}
				
				return $newinstance;
			}
		} else {
			facula::core('debug')->exception('ERROR_OBJECT_NEWINSTNACE_OBJECTNOTFOUND|' . $object, 'object', true);
		}
		
		return false;
	}
	
	public function get($type, $name, $new = false, $justinclude = false, $cache = false) {
		$keyName = $type . '.' . strtolower($name);
		$className = $type . $name;
		$newobject = null;
		
		if (isset($this->configs['Paths'][$keyName])) { // isset obviously faster than $this->getFile call.
			if (!isset($this->instances[$keyName]['Loaded'])) { // If this is the first time we load this module
				// First of all, include file, If not success, return false
				
				if (require($this->configs['Paths'][$keyName]['Path'])) {
					$this->instances[$keyName]['Loaded'] = true;
					
					if ($justinclude) {
						return true;
					}
				}
				
				$new = true;
			}
			
			if ($new) { // If need to create a new instance
				if ($newobject = $this->getInstance($className, array($this->getObjectSetting($className)), $cache)) {
					$this->instances[$keyName][] = $newobject;
					
					return $newobject;
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
	
	// Start a handler or other type of class
	public function run(&$app, $cache = false) {
		$handler = null;
		
		if ($handler = $this->getInstance($app, array(), $cache)) {
			// When inited has been ran, call run to get module a chance to select operate method.
			if (method_exists($handler, '_run')) {
				$handler->_run();
			}
		}
		
		return $handler;
	}
}

?>