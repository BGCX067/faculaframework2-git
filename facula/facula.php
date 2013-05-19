<?php

if (!defined('IN_FACULA')) {
	exit('Access Denied');
}

define('__FACULAVERSION__', '2 Prototype 0.0');

define('FACULA_ROOT', dirname(__FILE__));
define('PROJECT_ROOT', realpath('.'));

define('FACULA_TIME', isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time()); // Unified and unchanged timestamp for this thread. Use this is recommended.

class facula {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static private $instance = null;
	
	static private $config = array(
		'CacheSafeCode' => '<?php /* Facula System Cache File */ exit(); ?>',
		'CacheSectionBreaks' => '"""""""" + Break + """"""""',
		'SystemCacheFileName' => 'coreCache.php',
	);
	
	static private $profile = array(
		'TimeStart' => 0,
		'TimeEnd' => 0,
		'MemoryUsage' => 0,
		'MemoryPeak' => 0,
	);
	
	private $setting = array();
	
	private $coreInstances = array();
	
	private $components = array();
	
	static public function init(&$cfg) {
		if (!self::$instance) {
			if (isset($cfg['core']['SystemCacheRoot'][1]) && (!self::$instance = self::loadObjectFromCache($cfg['core']['SystemCacheRoot']))) {
				self::$instance = new self($cfg);
				
				self::saveObjectToCache($cfg['core']['SystemCacheRoot']);
			}
			
			self::$instance->_inited();
		}
		
		$cfg = null; // Clear config, because we already save them inside the core
		
		unset($cfg);
		
		return self::$instance;
	}
	
	static public function run($appname, $cache = false) {
		if (self::$instance) {
			return self::$instance->_run($appname, $cache);
		} else {
			throw new Exception('Facula must be initialized before running any application.');
		}
		
		return false;
	}
	
	static public function getInstance() {
		return self::$instance;
	}
	
	static public function core($coreName) {
		if (isset(self::$instance->coreInstances[$coreName])) {
			return self::$instance->coreInstances[$coreName];
		} else {
			throw new Exception('Cannot obtain core ' . $coreName . '. The core may not exists or loadable.');
		}
		
		return false;
	}
	
	static private function saveObjectToCache($cacheDir) {
		// Format: Safecode + serialized $this + middlecode + serialize file dirs
		$content = self::$config['CacheSafeCode'] . serialize(self::$instance) . self::$config['CacheSectionBreaks'] . serialize(self::$config['AutoIncludes']);
		
		if (is_dir($cacheDir)) {
			return file_put_contents($cacheDir . DIRECTORY_SEPARATOR . self::$config['SystemCacheFileName'], $content);
		}
		
		return false;
	}
	
	static private function loadObjectFromCache($cacheDir) {
		$file = $cacheDir . DIRECTORY_SEPARATOR . self::$config['SystemCacheFileName'];
		$cacheContents = $tempContents = array();
		$cacheContent = '';
		
		if (is_readable($file)) {
			$cacheContent = str_replace(self::$config['CacheSafeCode'], '', file_get_contents($file));
			
			$cacheContents = explode(self::$config['CacheSectionBreaks'], $cacheContent, 2);
			
			// If we got serialized file dirs
			if (isset($cacheContents[1])) {
				$tempContents = unserialize($cacheContents[1]);
				
				foreach($tempContents AS $path) {
					require($path);
				}
			}
			
			return unserialize($cacheContents[0]); // unserialize the object
		}
		
		return false;
	}
	
	private function __construct(&$cfg) {
		if (version_compare(PHP_VERSION, '5.3.0', '<')) {
			throw new Exception('Facula Framework desired to be running with PHP 5.4+');
		}
		
		return $this->_init($cfg);
	}
	
	private function _run(&$appname, $cache = false) {
		if (isset($this->coreInstances['object'])) {
			return $this->coreInstances['object']->runHandler($appname, $cache);
		}
		
		return false;
	}
	
	private function _init(&$cfg) {
		if ($this->importSetting($cfg)) {
			// Scan all component file and add them to $this->setting['core']['Components']
			$this->scanComponents();
			
			// Initialize needed component
			
			// First, save Autoloads pool to object setting for future use
			
			// include core (with init) and routine files
			if (isset($cfg['core']['Enables'])) {
				$cfg['core']['Enables'] = array_merge(array('debug', 'object', 'request', 'response'), $cfg['core']['Enables']);
			} else {
				$cfg['core']['Enables'] = array('debug', 'object', 'request', 'response');
			}
			
			foreach(self::$config['ComponentInfo'] AS $keyn => $component) {
				switch($component['Type']) {
					case 'core':
						if (!isset($cfg['core']['Enables']) || in_array($component['Name'], $cfg['core']['Enables'])) {
							require($component['Path']);
							
							self::$config['AutoCores'][] = $component;
							self::$config['AutoIncludes'][] = $component['Path']; // Add path to auto include, so facula will auto include those file in every load circle
						}
						
						break;
						
					case 'include':
						require($component['Path']);
						
						self::$config['AutoIncludes'][] = $component['Path'];
						break;
						
					default:
						// If not two core type, save it to object manager's patch so it will deal with this later
						$this->setting['object']['Paths'][$keyn] = $component;
						break;
				}
			}
			
			if (isset(self::$config['AutoCores'])) {
				foreach(self::$config['AutoCores'] AS $component) {
					$this->getCore($component['Name']);
				}
			}
			
			return true;
		}
		
		return false;
	}
	
	private function importSetting(&$cfg) {
		if (is_array($cfg)) {
			foreach($cfg AS $key => $val) {
				$this->setting[$key] = $val;
			}
		}
		
		unset($cfg);
		
		return true;
	}
	
	private function scanComponents() {
		$files = $tempFiles = array();
		
		$directories = array(
			'includes' => FACULA_ROOT . DIRECTORY_SEPARATOR . 'includes',
			'core' => FACULA_ROOT . DIRECTORY_SEPARATOR . 'core',
			'libraries' => FACULA_ROOT . DIRECTORY_SEPARATOR . 'libraries',
		);
		
		if (isset($this->setting['core']['Paths']) && is_array($this->setting['core']['Paths'])) {
			$directories = $directories + $this->setting['core']['Paths'];
		}
		
		foreach($directories AS $type => $dir) {
			if ($dir && ($tempFiles = $this->getModulesFromPath($dir))) {
			
				foreach($tempFiles AS $file) {
					if ($file['Ext'] == 'php') {
						// Add to autoload only when no any conflict or it's a alternative model file
						if (!isset(self::$config['ComponentInfo'][$file['Prefix'].'.'.$file['Name']])) {
							self::$config['ComponentInfo'][$file['Prefix'].'.'.$file['Name']] = array(
								'Path' => $file['Path'],
								'Name' => $file['Name'],
								'Type' => isset($file['Prefix']) ? $file['Prefix'] : null
							);
						} else {
							throw new Exception('File ' . $file['Path'] . ' conflict with ' . self::$config['ComponentInfo'][$file['Prefix'].'.'.$file['Name']]['Path']);
						}
					}
				}
			}
		}
	}
	
	private function _inited() {
		foreach($this->coreInstances AS $core) {
			if (method_exists($core, '_inited')) {
				$core->_inited();
			}
		}
	}
	
	private function getCore($coreName) {
		$objectName = 'facula' . ucfirst($coreName);
		$objectKey = 'core' . '.' . $coreName;
		$componentObj = NULL;
		
		if (isset($this->coreInstances[$coreName])) {
			return $this->coreInstances[$coreName];
		}
		
		if (isset(self::$config['ComponentInfo'][$objectKey])) {
			if (class_exists($objectName)) {
				if (method_exists($objectName, 'getInstance')) {
					if ($componentObj = $objectName::getInstance($this->getSetting($coreName), $this->getSetting('Common'), $this)) {
						$this->coreInstances[$coreName] = $componentObj;
						
						return $componentObj;
					}
				} else {
					throw new Exception('Static method getInstance not found in component ' . $objectName);
				}
			} else {
				throw new Exception('Component '.$coreName.' cannot be load. Please make sure object file already included.');
			}
		} else {
			throw new Exception('Object ' . $objectName . ' not defined. Clear cache before retry.'); 
		}
		
		return false;
	}
	
	public function callCore($coreName) {
		if (isset($this->coreInstances[$coreName])) {
			return $this->coreInstances[$coreName];
		} else {
			throw new Exception('Core module not found: ' . $coreName); 
		}
		
		return false;
	}
	
	private function getModulesFromPath($directory) {
		$modules = array();
		$moduleFilenames = $tempModuleFilenames = array();
		
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($directory), FilesystemIterator::SKIP_DOTS));
		
		foreach($iterator AS $file) {
			if ($file->isFile()) {
				$moduleFilenames = explode('.', $file->getFilename());
				
				switch(count($moduleFilenames)) {
					case 1:
						$modules[] = array(
							'Prefix' => null,
							'Name' => $moduleFilenames[0],
							'Ext' => null,
							'Path' => $file->getPathname()
						);
						break;
						
					case 2:
						$modules[] = array(
							'Prefix' => null,
							'Name' => $moduleFilenames[0],
							'Ext' => $moduleFilenames[1],
							'Path' => $file->getPathname()
						);
						break;
						
					case 3:
						$modules[] = array(
							'Prefix' => $moduleFilenames[0],
							'Name' => $moduleFilenames[1],
							'Ext' => $moduleFilenames[2],
							'Path' => $file->getPathname()
						);
						break;
						
					default:
						$tempModuleFilenames = array(
							'Prefix' => array_shift($moduleFilenames),
							'Ext' => array_pop($moduleFilenames),
						);
						
						$modules[] = array(
							'Prefix' => $tempModuleFilenames['Prefix'],
							'Name' => implode('.', $moduleFilenames),
							'Ext' => $tempModuleFilenames['Ext'],
							'Path' => $file->getPathname()
						);
						break;
				}
			}
		}
		
		if (isset($modules[0])) {
			return $modules;
		}
		
		return false;
	}
	
	private function &getSetting($key) {
		if (!isset($this->setting[$key])) {
			$this->setting[$key] = array();  // If this array not set yet, make a new one and return it
		}
		
		return $this->setting[$key];
	}
}

?>