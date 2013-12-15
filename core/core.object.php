<?php

/*****************************************************************************
	Facula Framework Object Manager
	
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

interface faculaObjectInterface {
	public function _inited();
	public function getFile($type, $name);
	public function getInstance($object, $args, $cache = false);
	public function getFileByNamespace($namespace);
	public function callFunction($function, $args = array());
	public function run($app, $args = array(), $cache = false);
	public function hookSize($hookName);
	public function runHook($hookName, $hookArgs, &$error);
	public function addHook($hookName, $processorName, $processor);
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

abstract class faculaObjectDefaultBase implements faculaObjectInterface {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);

	static protected $config = array(
		'CacheSafeCode' => array(
			'<?php if (!defined(\'IN_FACULA\')) {exit(\'Access Denied\');} ',
			' ?>',
		),
	);
	
	protected $configs = array();
	
	protected $hooks = array();
	
	protected $instances = array();
	
	public function __construct(&$cfg, &$common, $facula) {
		$paths = array();
		
		$this->configs = array(
			'OCRoot' => isset($cfg['ObjectCacheRoot']) && is_dir($cfg['ObjectCacheRoot']) ? $cfg['ObjectCacheRoot'] : '',
			'OCExpire' => isset($cfg['ObjectCacheExpire']) && $cfg['ObjectCacheExpire'] ? (int)($cfg['ObjectCacheExpire']) : 604800,
			'CacheTime' => $common['BootVersion'],
			'AutoPaths' => array(
				'CmpRoot' => isset($cfg['ComponentRoot']) && is_dir($cfg['ComponentRoot']) ? $cfg['ComponentRoot'] : '',
			)
		);
		
		// Convert plugin type file to misc type file to it will be able to use autoload
		foreach($cfg['Paths'] AS $key => $path) {
			switch($path['Type']) {
				case 'class':
					$paths['Paths'][$key] = $path;
					break;
					
				case 'plugin':
					$paths['Paths'][$path['Name'] . 'Plugin'] = $path;
					$paths['Plugins'][$key] = $path;
					break;
					
				default:
					$paths['Paths'][$path['Name'] . ucfirst($path['Type'])] = $path;
					break;
			}
		}
		
		if (isset($cfg['Libraries']) && is_array($cfg['Libraries'])) {
			foreach($cfg['Libraries'] AS $libRoot) {
				$this->configs['AutoPaths'][] = $libRoot;
			}
			
			$this->configs['AutoPaths'] = array_unique(array_merge($this->configs['AutoPaths'], explode(PATH_SEPARATOR, get_include_path())));
		}
		
		// Save path to paths
		$this->configs['Paths'] = $paths['Paths'];
		
		// Now, init all plugins
		if (isset($paths['Plugins'])) {
			$tempPluginName = '';
			
			foreach($paths['Plugins'] AS $plugin) {
				$tempPluginName = $plugin['Name'] . 'Plugin';
				
				// Load plugin file manually
				require($plugin['Path']);
				
				// Is that plugin file contains we wanted plugin?
				if (class_exists($tempPluginName, false)) {
					if (method_exists($tempPluginName, 'register')) {
						foreach($tempPluginName::register() AS $hook => $action) {
							if (!isset($this->hooks[$hook][$tempPluginName])) {
								if (is_callable(array($tempPluginName, $action))) {
									$this->hooks[$hook][$tempPluginName] = array($tempPluginName, $action);
								}
							} else {
								throw new Exception('Hook ' . $hook . ' already have a processor ' . $tempPluginName . '.');
							}
						}
					} else {
						throw new Exception('Static method ' . $tempPluginName . '::register() must be declared.');
					}
				} else {
					throw new Exception('File ' . $plugin['Path'] . ' must contain class ' . $tempPluginName . '.');
				}
			}
		}
		
		$cfg = null;
		unset($cfg);
		
		return true;
	}
	
	public function _getInfo() {
		return array(
			'Files' => $this->configs['Paths'],
			'Hooks' => $this->hooks,
		);
	}
	
	public function _inited() {
		spl_autoload_register(array(&$this, 'getAutoInclude'));
		
		register_shutdown_function(function() {
			$this->shutdownHook();
		});
		
		return true;
	}
	
	protected function getAutoInclude($classfile) {
		$classfileLower = strtolower($classfile);
		
		if (isset($this->configs['Paths']['class.' . $classfileLower])) { // Use path scope to locate file first
			return require_once($this->configs['Paths']['class.' . $classfileLower]['Path']);
		} elseif (isset($this->configs['Paths'][$classfile])) { // If this is not a class file, but other module we dont know
			return require_once($this->configs['Paths'][$classfile]['Path']);
		} elseif ($this->getWithNamespace($classfile)) {
			return true;
		}
		
		return false;
	}
	
	protected function getWithNamespace($namespace) {
		$modPath = str_replace(array('\\', '/', '_'), DIRECTORY_SEPARATOR, $namespace) . '.php';
		
		foreach($this->configs['AutoPaths'] AS $path) {
			if (file_exists($path . DIRECTORY_SEPARATOR . $modPath)) {
				return require_once($path . DIRECTORY_SEPARATOR . $modPath);
			}
		}
		
		return false;
	}
	
	protected function &getObjectSetting($objName) {
		if (!isset($this->configs['Components'][$objName])) {
			$this->configs['Components'][$objName] = array(); // You'll get a array anyway
		}
		
		return $this->configs['Components'][$objName];
	}
	
	protected function loadObjectFromCache($objectName, $type = '', $uniqueid = '') {
		$instance = null;
		$obj = array();
		
		if ($this->configs['OCRoot']) {
			$file = $this->configs['OCRoot'] . DIRECTORY_SEPARATOR . 'cachedObject.' . ($type ? $type : 'general') . '#' . str_replace(array('\\', '/'), '%', $objectName) . '#' . ($uniqueid ? $uniqueid : 'common') . '.php';
			
			if (is_readable($file) && filemtime($file) >= $this->configs['CacheTime']) {
				require($file);
				
				if ($obj && $instance = unserialize($obj)) {
					
					if ($this->configs['OCExpire'] && $instance->cachedObjectSaveTime < $this->configs['OCExpire'] - FACULA_TIME) {
						unlink($file);
					}
					
					return $instance;
				}
			}
		}
		
		return false;
	}
	
	protected function saveObjectToCache($objectName, $instance, $type = '', $uniqueid = '') {
		$objectInfo = array();
		
		if ($this->configs['OCRoot']) {
			$instance->cachedObjectFilePath = $this->configs['OCRoot'] . DIRECTORY_SEPARATOR . 'cachedObject.' . ($type ? $type : 'general') . '#' . str_replace(array('\\', '/'), '%', $objectName) . '#' . ($uniqueid ? $uniqueid : 'common') . '.php';
			$instance->cachedObjectSaveTime = FACULA_TIME;
			
			return file_put_contents($instance->cachedObjectFilePath, self::$config['CacheSafeCode'][0] . '$obj = ' . var_export(serialize($instance), true) . ';' . self::$config['CacheSafeCode'][1]);
		}
		
		return false;
	}
	
	public function getInstance($object, $args, $cache = false) {
		$newinstance = null;
		
		if (class_exists($object, true)) {
			if ($cache && ($newinstance = $this->loadObjectFromCache($object))) {
				// Call _init after instance has been created to pre init it
				if (method_exists($newinstance, '_init')) {
					if (!$newinstance->_init()) {
						facula::core('debug')->exception('ERROR_OBJECT_NEWINSTNACE_INIT_FAILED|' . $object, 'object', true);
						
						return false;
					}
				}
			
				if (method_exists($newinstance, '_inited')) {
					$newinstance->_inited();
				}
				
				return $newinstance;
			} else {
				switch(count($args)) {
					case 0:
						$newinstance = new $object();
						break;
				
					case 1:
						$newinstance = new $object($args[0]);
						break;
						
					case 2:
						$newinstance = new $object($args[0], $args[1]);
						break;
						
					case 3:
						$newinstance = new $object($args[0], $args[1], $args[2]);
						break;
						
					case 4:
						$newinstance = new $object($args[0], $args[1], $args[2], $args[3]);
						break;
						
					case 5:
						$newinstance = new $object($args[0], $args[1], $args[2], $args[3], $args[4]);
						break;
						
					case 6:
						$newinstance = new $object($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
						break;
						
					case 7:
						$newinstance = new $object($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
						break;
						
					case 8:
						$newinstance = new $object($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
						break;
						
					case 9:
						$newinstance = new $object($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8]);
						break;
						
					case 10:
						$newinstance = new $object($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9]);
						break;
						
					default:
						facula::core('debug')->exception('ERROR_OBJECT_NEWINSTNACE_MAXPARAMEXCEEDED', 'object', true);
						break;
				}
				
				// Save first
				if ($cache) {
					$this->saveObjectToCache($object, $newinstance);
				}
				
				// Call _init after instance has been created to pre init it
				if (method_exists($newinstance, '_init')) {
					if (!$newinstance->_init()) {
						facula::core('debug')->exception('ERROR_OBJECT_NEWINSTNACE_INIT_FAILED|' . $object, 'object', true);
						
						return false;
					}
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
	
	public function callFunction($function, $args = array()) {
		if (is_callable($function)) {
			switch(count($args)) {
				case 0:
					return $function();
					break;
			
				case 1:
					return $function($args[0]);
					break;
					
				case 2:
					return $function($args[0], $args[1]);
					break;
					
				case 3:
					return $function($args[0], $args[1], $args[2]);
					break;
					
				case 4:
					return $function($args[0], $args[1], $args[2], $args[3]);
					break;
					
				case 5:
					return $function($args[0], $args[1], $args[2], $args[3], $args[4]);
					break;
					
				case 6:
					return $function($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
					break;
					
				case 7:
					return $function($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
					break;
					
				case 8:
					return $function($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
					break;
					
				case 9:
					return $function($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8]);
					break;
					
				case 10:
					return $function($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9]);
					break;
					
				default:
					return call_user_func_array($function, $args);
					break;
			}
		}
		
		return false;
	}
	
	public function getFile($type, $name) {
		$keyName = $type . '.' . $name;
		
		if (isset($this->configs['Paths'][$keyName])) {
			return $this->configs['Paths'][$keyName];
		}
		
		return false;
	}
	
	public function getFileByNamespace($namespace) {
		if ($this->configs['CmpRoot'] && strpos($namespace, '\\') !== false) {
			return $this->configs['CmpRoot'] . DIRECTORY_SEPARATOR . str_replace(array('\\', '/', '_'), DIRECTORY_SEPARATOR, ltrim($namespace, '\\')) . '.php';
		}
		
		return false;
	}
	
	// Start a handler or other type of class
	public function run($app, $args = array(), $cache = false) {
		$handler = $hookResult = $callResult = $error = null;
		$appParam = explode('::', str_replace(array('::', '->'), '::', $app), 2);
		
		if ($handler = $this->getInstance($appParam[0], $args, $cache)) {
			if (isset($appParam[1]) && method_exists($handler, $appParam[1])) {
				$hookResult = $this->runHook('call_' . $appParam[0] . '::' . $appParam[1] . '_before', $args, $error);
				
				$callResult = $this->callFunction(array($handler, $appParam[1]), $args);
				
				$this->runHook('call_' . $appParam[0] . '::' . $appParam[1] . '_after', array(
					'Call' => $callResult,
					'Hook' => $hookResult,
				), $error);
			} elseif (method_exists($handler, '_run')) {
				$hookResult = $this->runHook('call_' . $appParam[0] . '_before', $args, $error);
				
				$callResult = $this->callFunction(array($handler, '_run'), $args);
				
				$this->runHook('call_' . $appParam[0] . '_after', array(
					'Call' => $callResult,
					'Hook' => $hookResult,
				), $error);
			}
		}
		
		return $handler;
	}
	
	// Hooks
	public function hookSize($hookName) {
		if (isset($this->hooks[$hookName])) {
			return count($this->hooks[$hookName]);
		}
		
		return 0;
	}
	
	public function runHook($hookName, $hookArgs, &$error) {
		$returns = array();
		
		if (isset($this->hooks[$hookName])) {
			foreach($this->hooks[$hookName] AS $processorName => $hook) {
				if (!($returns[$processorName] = $hook($hookArgs, $error))) {
					return false;
					
					break;
				}
			}
			
			return $returns;
		}
		
		return true; // If there is no plugin to run, return true to assume successed
	}
	
	public function addHook($hookName, $processorName, $processor) {
		if (!isset($this->hooks[$hookName][$processorName])) {
			if (is_callable($processor)) {
				$this->hooks[$hookName][$processorName] = $processor;
				
				return true;
			}
		} else {
			facula::core('debug')->exception('ERROR_OBJECT_HOOK_PROCESSOR_ALREADY_EXISTED|' . $processorName, 'object', true);
		}
		
		return false;
	}
	
	protected function shutdownHook() {
		$error = array();
		
		return $this->runHook('shutingdown', array(), $error);
	}
}

class faculaObjectDefault extends faculaObjectDefaultBase {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
}

?>