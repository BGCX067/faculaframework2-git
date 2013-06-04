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
	public function getInstance($object, $ags, $cache = false);
	public function getFileByNamespace($namespace);
	public function run(&$app, $cache = false);
	public function runHook($hookName, $hookArgs, &$error);
	public function addHook($hookName, $processor);
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
	
	private $plugins = array();
	
	private $instances = array();
	
	public function __construct(&$cfg, &$common, $facula) {
		$paths = array();
		
		$this->configs = array(
			'ObjectCacheRoot' => isset($cfg['ObjectCacheRoot']) && is_dir($cfg['ObjectCacheRoot']) ? $cfg['ObjectCacheRoot'] : '',
			'LibRoot' => isset($cfg['LibrariesRoot']) && is_dir($cfg['LibrariesRoot']) ? $cfg['LibrariesRoot'] : '',
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
				if (class_exists($tempPluginName)) {
					if (method_exists($tempPluginName, 'register')) {
						foreach($tempPluginName::register() AS $hook => $action) {
							if (is_callable(array($tempPluginName, $action))) {
								$this->hooks[$hook][] = array($tempPluginName, $action);
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
		
		return true;
	}
	
	private function getAutoInclude($classfile) {
		$classfileLower = strtolower($classfile);
		
		if (isset($this->configs['Paths']['class.' . $classfileLower])) { // Use path scope to locate file first
			return require_once($this->configs['Paths']['class.' . $classfileLower]['Path']);
		} elseif (isset($this->configs['Paths'][$classfile])) { // If this is not a class file, but other module we dont know
			return require_once($this->configs['Paths'][$classfile]['Path']);
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
			$instance->cachedObjectSaveTime = FACULA_TIME;
			
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
	
	public function getFile($type, $name) {
		$keyName = $type . '.' . $name;
		
		if (isset($this->configs['Paths'][$keyName])) {
			return $this->configs['Paths'][$keyName];
		}
		
		return false;
	}
	
	public function getFileByNamespace($namespace) {
		if ($this->configs['LibRoot'] && strpos($namespace, '\\') !== false) {
			return $this->configs['LibRoot'] . DIRECTORY_SEPARATOR . str_replace(array('\\', '/', '_'), DIRECTORY_SEPARATOR, ltrim($namespace, '\\')) . '.php';
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
	
	// Hooks
	public function runHook($hookName, $hookArgs, &$error) {
		$returns = array();
		
		if (isset($this->hooks[$hookName])) {
			foreach($this->hooks[$hookName] AS $hook) {
				if (!$returns[] = $hook($hookArgs, $error)) {
					return false;
					
					break;
				}
			}
			
			return $returns;
		}
		
		return true; // If there is no plugin to run, return true to assume successed
	}
	
	public function addHook($hookName, $processor) {
		if (is_callable($processor)) {
			$this->hooks[] = $processor;
			
			return true;
		}
		
		return false;
	}
}

?>