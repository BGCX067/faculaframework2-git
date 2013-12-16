<?php

/*****************************************************************************
    Facula Framework Core Unit

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

define('__FACULAVERSION__', '2 Prototype 0.1');

define('FACULA_ROOT', dirname(__FILE__));
define('PROJECT_ROOT', realpath('.'));

define('IN_FACULA', true);
define('FACULA_TIME', isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time()); // Unified and unchanged timestamp for this thread. Use this is recommended.

class Facula
{
    public static $plate = array(
        'Author' => 'Rain Lee',
        'Reviser' => '',
        'Updated' => '2013',
        'Contact' => 'raincious@gmail.com',
        'Version' => __FACULAVERSION__,
    );

    public static $time = 0;

    private static $instance = null;

    public static $profile = array(
        'StartTime' => 0,
        'OutputTime' => 0,
        'ProductionTime' => 0,
        'MemoryUsage' => 0,
        'MemoryPeak' => 0,
    );

    private static $config = array(
        'CacheSafeCode' => array(
            '<?php if (!defined(\'IN_FACULA\')) {exit(\'Access Denied\');} ',
            ' ?>',
        ),
        'SystemCacheFileName' => 'coreCache.php',
        'CoreComponents' => array('debug', 'object', 'request', 'response'),
    );

    private $setting = array();

    private $coreInstances = array();

    public static function init(array &$cfg = array())
    {
        self::$profile['StartTime'] = microtime(true);
        self::$time = time();

        if (!self::$instance) {
            if (isset($cfg['core']['SystemCacheRoot'][1])) {
                if (!self::$instance = self::loadCoreFromCache($cfg['core']['SystemCacheRoot'])) {
                    self::$instance = new self($cfg);

                    self::saveCoreToCache($cfg['core']['SystemCacheRoot']);
                }
            } else {
                self::$instance = new self($cfg);
            }

            self::$instance->_inited();

            if ((isset($cfg['AppName'], self::$instance->setting['AppName'], $cfg['AppVersion'], self::$instance->setting['AppVersion'])) && ((self::$instance->setting['AppName'] != $cfg['AppName']) || (self::$instance->setting['AppVersion'] != $cfg['AppVersion']))) {
                self::clearCoreCache();
            }
        }

        $cfg = null; // Clear config, because we already save them inside the core

        unset($cfg);

        return self::$instance;
    }

    public static function run($appname, array $args = array(), $cache = false)
    {
        if (self::$instance && isset(self::$instance->coreInstances['object'])) {
            return self::$instance->coreInstances['object']->run($appname, $args, $cache);
        } else {
            throw new Exception('Facula must be initialized before running any application.');
        }

        return false;
    }

    public static function getVersion()
    {
        if (isset(self::$instance)) {
            return array(
                'Base' => 'Facula Framework ' . __FACULAVERSION__,
                'App' => isset(self::$instance->setting['AppName']) ? self::$instance->setting['AppName'] : 'Facula App',
                'Ver' => isset(self::$instance->setting['AppVersion']) ? self::$instance->setting['AppVersion'] : '0.0',
                'Boot' => isset(self::$instance->setting['Common']['BootVersion']) ? self::$instance->setting['Common']['BootVersion'] : '0',
            );
        }

        return array();
    }

    public static function getCoreInfo()
    {
        if (isset(self::$instance)) {
            $cores = array();

            foreach (self::$instance->coreInstances as $core) {
                $cores[get_class($core)] = array(
                    'Plate' => isset($core::$plate) ? $core::$plate : null,
                    'Info' => method_exists($core, '_getInfo') ? $core->_getInfo() : null,
                );
            }

            return array(
                'App' => isset(self::$instance->setting['AppName']) ? self::$instance->setting['AppName'] : 'Facula App',
                'Ver' => isset(self::$instance->setting['AppVersion']) ? self::$instance->setting['AppVersion'] : '0.0',
                'Cores' => $cores,
            );
        }

        return array();
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    public static function core($coreName)
    {
        if (isset(self::$instance->coreInstances[$coreName])) {
            return self::$instance->coreInstances[$coreName];
        } else {
            throw new Exception('Cannot obtain core ' . $coreName . '. The core may not exists or loadable.');
        }

        return false;
    }

    public static function isCoreSet($coreName)
    {
        if (isset(self::$instance->coreInstances[$coreName])) {
            return true;
        }

        return false;
    }

    public static function getAllCores()
    {
        if (isset(self::$instance) && !empty(self::$instance->coreInstances)) {
            return self::$instance->coreInstances;
        }

        return false;
    }

    private static function saveCoreToCache($cacheDir)
    {
        $cache = array(
            'Facula' => serialize(self::$instance),
            'Inc' => self::$config['AutoIncludes'],
            'Rot' => isset(self::$config['AutoRoutines']) ? self::$config['AutoRoutines'] : array(),
        );

        $content = self::$config['CacheSafeCode'][0] . '$cache = ' . var_export($cache, true) . ';'. self::$config['CacheSafeCode'][1];

        if (is_dir($cacheDir)) {
            return file_put_contents($cacheDir . DIRECTORY_SEPARATOR . self::$config['SystemCacheFileName'], $content);
        }

        return false;
    }

    private static function loadCoreFromCache($cacheDir)
    {
        $facula = null;
        $file = $cacheDir . DIRECTORY_SEPARATOR . self::$config['SystemCacheFileName'];
        $cache = array();

        if (is_readable($file)) {
            require($file);

            if (!empty($cache)) {
                if (isset($cache['Rot'][0])) {
                    self::$config['AutoRoutines'] = $cache['Rot'];
                }

                if (isset($cache['Inc'][0])) {
                    foreach ($cache['Inc'] as $path) {
                        require($path);
                    }
                }

                return unserialize($cache['Facula']); // unserialize the object
            }
        }

        return false;
    }

    public static function clearCoreCache()
    {
        if (isset(self::$instance) && isset(self::$instance->setting['core']['SystemCacheRoot'])) {
            $file = self::$instance->setting['core']['SystemCacheRoot'] . DIRECTORY_SEPARATOR . self::$config['SystemCacheFileName'];

            if (is_readable($file)) {
                return unlink($file);
            } else {
                throw new Exception('Facula core cache file ' . $file . ' cannot be operated.');
            }
        } else {
            throw new Exception('Facula must be inited to remove core cache.');
        }

        return false;
    }

    private function __construct(array &$cfg)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<=')) {
            throw new Exception('Facula Framework desired to be running with PHP 5.4+');
        }

        return $this->_init($cfg);
    }

    private function _init(array &$cfg)
    {
        $cfg['Common']['AppVersion'] = (isset($cfg['AppName']) ? $cfg['AppName'] : 'Facula App') . ' ' . (isset($cfg['AppVersion']) ? $cfg['AppVersion'] : '0.0');
        $cfg['Common']['BootVersion'] = FACULA_TIME;

        if ($this->importSetting($cfg)) {
            // Scan all component file and add them to $this->setting['core']['Components']
            $this->scanComponents();

            // Initialize needed component

            // First, save Autoloads pool to object setting for future use

            // include core (with init) and routine files
            if (isset($cfg['core']['Enables']) && is_array($cfg['core']['Enables'])) {
                $cfg['core']['Enables'] = array_merge(self::$config['CoreComponents'], array_values($cfg['core']['Enables'])); // Use array_merge to solve numeric key conflicts automatically
            } else {
                $cfg['core']['Enables'] = self::$config['CoreComponents'];
            }

            // Init the AutoCores array
            self::$config['AutoCores'] = array();

            foreach (self::$config['ComponentInfo'] as $keyn => $component) {
                switch ($component['Type']) {
                    case 'core':
                        require($component['Path']);
                        self::$config['AutoCores'][$component['Name']] = $component; // Rest, add to the end
                        self::$config['AutoIncludes'][] = $component['Path']; // Add path to auto include, so facula will auto include those file in every load circle

                        break;

                    case 'include':
                        require($component['Path']);

                        self::$config['AutoIncludes'][] = $component['Path'];
                        break;

                    case 'routine':
                        self::$config['AutoRoutines'][] = $component['Path'];
                        break;

                    default:
                        // If not two core type, save it to object manager's path and let them deal with this later
                        $this->setting['object']['Paths'][$keyn] = $component;
                        break;
                }
            }

            // Init all cores
            foreach ($cfg['core']['Enables'] as $componentKey) {
                if (isset(self::$config['AutoCores'][$componentKey])) {
                    $this->getCore(self::$config['AutoCores'][$componentKey]['Name']);
                } else {
                    throw new Exception('Cannot found the specified facula core: ' . $componentKey . '.');
                }
            }

            return true;
        }

        return false;
    }

    private function importSetting(array &$cfg)
    {
        foreach ($cfg as $key => $val) {
            $this->setting[$key] = $val;
        }

        unset($cfg);

        return true;
    }

    private function scanComponents()
    {
        $files = $tempFiles = array();

        $directories = array(
            FACULA_ROOT . DIRECTORY_SEPARATOR . 'includes',
            FACULA_ROOT . DIRECTORY_SEPARATOR . 'core',
        );

        if (!isset($this->setting['core']['NoBuildinLibs']) || !$this->setting['core']['NoBuildinLibs']) {
            $directories[] = FACULA_ROOT . DIRECTORY_SEPARATOR . 'libraries';
        }

        if (isset($this->setting['core']['Paths']) && is_array($this->setting['core']['Paths'])) {
            $directories = array_merge($directories, array_values($this->setting['core']['Paths'])); // array_merge will automatically solve numeric key conflicts
        }

        foreach ($directories as $type => $dir) {
            if ($dir && ($tempFiles = $this->scanModuleFiles($dir))) {

                foreach ($tempFiles as $file) {
                    if ($file['Ext'] == 'php') {
                        // Add to autoload only when no any conflict
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

    private function _inited()
    {
        // Call sub core to wake up
        foreach ($this->coreInstances as $core) {
            if (method_exists($core, '_inited')) {
                $core->_inited();
            }
        }

        // Include routines to wake up user's sutff
        if (isset(self::$config['AutoRoutines'])) {
            foreach (self::$config['AutoRoutines'] as $path) {
                require($path);
            }
        }

        return true;
    }

    private function getCore($coreName)
    {
        $objectName = 'facula' . ucfirst($coreName);
        $objectKey = 'core' . '.' . $coreName;
        $componentObj = null;

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

    private function &getSetting($key)
    {
        if (!isset($this->setting[$key])) {
            $this->setting[$key] = array();  // If this array not set yet, make a new fake one and return it
        }

        return $this->setting[$key];
    }

    public function scanModuleFiles($directory)
    {
        $modules = array();
        $moduleFilenames = $tempModuleFilenames = array();

        if (is_dir($directory)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($directory), FilesystemIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $moduleFilenames = explode('.', $file->getFilename());

                    switch (count($moduleFilenames)) {
                        case 1:
                            break;

                        case 2:
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
        } else {
            throw new Exception($directory . ' is not a directory.');
        }

        return false;
    }
}
