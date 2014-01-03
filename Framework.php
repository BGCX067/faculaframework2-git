<?php

/**
 * Struct Manage Unit
 *
 * Facula Framework 2013 (C) Rain Lee
 *
 * Facula Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, version 3.
 *
 * Facula Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Facula Framework. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Rain Lee <raincious@gmail.com>
 * @copyright  2013 Rain Lee
 * @package    Facula
 * @version    2.2 prototype
 * @see        https://github.com/raincious/facula FYI
 */

namespace Facula;

/** Version of current Facula version */
define('__FACULAVERSION__', '2 Prototype 0.2');

/** Root of Facula Framework */
define('FACULA_ROOT', dirname(__FILE__));

/** Root of project directory */
define('PROJECT_ROOT', realpath('.'));

/** Separator for namespace */
define('NAMESPACE_SEPARATER', '\\');

/** Inform modules the Facula Framework is declared */
define('IN_FACULA', true);

/** Stable current time for this runtime */
define('FACULA_TIME', isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time());

/**
 * Struct Manage Unit
 *
 * Core of Facula Framework
 */
class Framework
{
    /** Singleton instance container */
    protected static $instance = null;

    /** Performance and running counter */
    public static $profile = array(
        'StartTime' => 0,
        'OutputTime' => 0,
        'ProductionTime' => 0,
        'MemoryUsage' => 0,
        'MemoryPeak' => 0,
    );

    /** Declare maintainer information */
    public static $plate = array(
        'Author' => 'Rain Lee',
        'Reviser' => '',
        'Updated' => '2013',
        'Contact' => 'raincious@gmail.com',
        'Version' => __FACULAVERSION__,
    );

    /** Config container */
    protected static $cfg = array(
        // The start and end tag for framework cache
        'CacheTags' => array(
            '<?php if (!defined(\'IN_FACULA\')) {exit(\'Access Denied\');} ',
            '',
        ),

        // File extension for PHP script files
        'PHPExt' => 'php',

        // The char to split namespaces
        'NSSplitter' => NAMESPACE_SEPARATER,

        // Framework namespace
        'FWNS' => array(
            '\Facula' => FACULA_ROOT,
        ),

        // Cores that needed for every boot time
        'RequiredCores' => array(
            'debug' => '\Facula\Core\Debug',
            'object' => '\Facula\Core\Object',
            'request' => '\Facula\Core\Request',
            'response' => '\Facula\Core\Response'
        ),
    );

    /** Mirror of Components information container */
    protected static $components = array();

    /** Auto includes files */
    protected static $includes = array();

    /** Data container for Facula framework instance */
    protected $setting = array( );

    /** Core container */
    protected $cores = array();

    /**
     * Initialization exposure
     *
     * @param array $cfg Configuration array
     *
     * @return mixed Return a singleton Facula object when successed, false otherwise.
     */
    public static function run(array &$cfg)
    {
        if (!static::$instance) {
            spl_autoload_register(array(__CLASS__, 'loadClass'));

            static::$profile['StartTime'] = microtime(true);

            if (isset($cfg['StateCache'][0])) {
                if (!static::$instance = static::initFromStateCache($cfg['StateCache'])) {
                    static::$instance = new static($cfg);

                    static::saveStateCache($cfg['StateCache']);
                }
            } else {
                static::$instance = new static($cfg);
            }

            static::$instance->ready();
        }

        $cfg = null;

        return static::$instance;
    }

    /**
     * Initialize framework from cache state
     *
     * @param string $stateFile Path to the state cache file
     *
     * @return mixed Will return facula object when succeeded, false otherwise
     */
    protected static function initFromStateCache($stateFile)
    {
        $cache = array();

        if (is_readable($stateFile)) {
            require($stateFile);

            if (!empty($cache)) {
                if (isset($cache['Cmp'])) {
                    static::$components = $cache['Cmp'];
                }

                // Require all include files
                if (isset($cache['Inc'])) {
                    foreach ($cache['Inc'] as $files) {
                        require($files);
                    }
                }

                return unserialize($cache['Facula']); // unserialize the object
            }
        }

        return false;
    }

    /**
     * Save framework state to state file
     *
     * @param string $stateFile Path to the state cache file
     *
     * @return bool Will return true succeeded, false otherwise
     */
    protected static function saveStateCache($stateFile)
    {
        $cache = array(
            'Facula' => serialize(static::$instance),
            'Inc' => static::$includes,
            'Cmp' => static::$components
        );

        $content = self::$cfg['CacheTags'][0]
                    . '$cache = '
                    . var_export($cache, true)
                    . ';'
                    . self::$cfg['CacheTags'][1];

        if (file_put_contents($stateFile, $content)) {
            return true;
        }

        return false;
    }

    /**
     * Clear framework state cache
     *
     * @return bool Return true when success, false otherwise.
     */
    public static function clearState()
    {
        if (static::$instance && isset(static::$instance->setting['StateCache'])) {
            return unlink(static::$instance->setting['StateCache']);
        }

        return false;
    }

    /**
     * Load a class from register record (Namespace and scope)
     *
     * @param string $className Fully qualified class name
     *
     * @return bool Return true when success, false otherwise.
     */
    public static function loadClass($className)
    {
        // Check if this is a namespace calling
        if (strpos($className, static::$cfg['NSSplitter'])) {
            return static::loadNamespace($className);
        } else {
            return static::loadScope($className);
        }

        return false;
    }

    /**
     * Load a class with Scope
     *
     * @param string $class Fully qualified class name
     *
     * @return bool Return true when success, false otherwise.
     */
    protected static function loadScope($class)
    {
        $className = strtolower($class);

        if (isset(static::$components['Scope'][$className])) {
            require(static::$components['Scope'][$className]);

            return true;
        }

        return false;
    }

    /**
     * Register a class into Scope
     *
     * @param string $class Class name
     * @param string $file File path to that file
     *
     * @return bool Return true when success, false otherwise.
     */
    public static function registerScope($class, $file)
    {
        $className = strtolower($class);

        if (isset(static::$components['Scope'][$className])) {
            throw new \Exception(
                'Trying register a class('
                . $class
                . ') to scope while it already registered.'
            );

            return false;
        }

        if (!is_readable($file)) {
            throw new \Exception(
                'Trying register a class('
                . $class
                . '), but the class file('
                . $file
                . ') is not readable.'
            );

            return false;
        }

        return static::$components['Scope'][$className] = str_replace(
            array('\\', '/', DIRECTORY_SEPARATOR),
            DIRECTORY_SEPARATOR,
            $file
        );
    }

    /**
     * Unregister a class from Scope
     *
     * @param string $class Class name
     *
     * @return bool Return true when success, false otherwise. A error will show up when the class not existe.
     */
    public static function unregisterScope($class)
    {
        $className = strtolower($class);

        if (!isset(static::$components['Scope'][$className])) {
            throw new \Exception(
                'Trying unregister a class('
                . $class
                . '), but the class was not found.'
            );

            return false;
        }

        unset(static::$components['Scope'][$className]);

        return true;
    }

    /**
     * Load a class with namespace
     *
     * @param string $class Fully qualified class name
     *
     * @return bool Return true when success, false otherwise.
     */
    protected static function loadNamespace($class)
    {
        $splitedNamespace = self::splitNamespace($class, true);

        // Pop the last element as it will be the class name it self
        $className = array_pop($splitedNamespace);

        $map = self::locateNamespace($splitedNamespace, false);

        if (isset($map['Ref']['P'])) {
            $fullPath = $map['Ref']['P']
                        . DIRECTORY_SEPARATOR
                        . ($map['Remain'] ? $map['Remain'] . DIRECTORY_SEPARATOR : '')
                        . $className
                        . '.' . static::$cfg['PHPExt'];

            if ($map['Ref']['P'] && is_readable($fullPath)) {
                require($fullPath);

                return true;
            }
        }

        return false;
    }

    /**
     * Register a namespace prefix
     *
     * @param string $nsPrefix The prefix
     * @param string $path Path of this namespace perfix
     *
     * @return bool Return true when success, false otherwise.
     */
    public static function registerNamespace($nsPrefix, $path)
    {
        if (file_exists($path)) {
            $map = self::locateNamespace(self::splitNamespace($nsPrefix), true);

            if (!isset($map['Ref']['P']) || !$map['Ref']['P']) {
                $map['Ref']['P'] = rtrim(
                    str_replace(
                        array(
                            '\\',
                            '/',
                            DIRECTORY_SEPARATOR
                        ),
                        DIRECTORY_SEPARATOR,
                        $path
                    ),
                    DIRECTORY_SEPARATOR
                );

                return true;
            } else {
                throw new \Exception(
                    'Trying register a namespace('
                    . $nsPrefix
                    . ') while it already registered.'
                );
            }

        } else {
            throw new \Exception(
                'Path '
                . $path
                . ' for namespace '
                . $nsPrefix
                . ' not exist.'
            );
        }

        return false;
    }

    /**
     * Unregister a namespace prefix
     *
     * @param string $nsPrefix The prefix
     *
     * @return bool Return true when success, false otherwise.
     */
    public static function unregisterNamespace($nsPrefix)
    {
        $map = self::locateNamespace(self::splitNamespace($nsPrefix), false);

        if (!$map['Remain']) {
            if ($map['Ref']) {
                $map['Ref'] = null;
            } else {
                throw new \Exception('Trying unregister a namespace while it not existed.');

                return false;
            }

            return true;
        } else {
            throw new \Exception('Namespace ' . $nsPrefix . ' not registered.');
        }

        return false;
    }

    /**
     * Split namespace in to array
     *
     * @param string $namespace The namespace
     *
     * @return array Array of splited namespace name
     */
    protected static function splitNamespace($namespace)
    {
        return explode(
            static::$cfg['NSSplitter'],
            trim(
                str_replace(
                    array(
                        '/',
                        '\\',
                        DIRECTORY_SEPARATOR
                    ),
                    static::$cfg['NSSplitter'],
                    $namespace
                ),
                static::$cfg['NSSplitter']
            )
        );
    }

    /**
     * Get reference of the namespace in inner Namespacing map
     *
     * @param array $splitedNamespace The namespace
     * @param bool $create Set to true to create the array when it's not exist, false to return last matched
     *
     * @return &array array('Ref' => reference, 'Parent' => reference, 'Remain' => string)
     */
    protected static function locateNamespace(array $splitedNamespace, $create = false)
    {
        $splitedNS = $splitedRemainNS = $splitedNamespace;

        // Set a reference that point to the root Mapping container
        $mapParentRef = $mapCurrentRef = $mapActiveRef = & static::$components['NSMap'];

        foreach ($splitedNS as $nsk => $ns) {
            $mapParentRef = & $mapActiveRef;

            // Repoint the Namespace reference if namespace already existed
            // Or init a new Namespace
            if (isset($mapActiveRef[$ns])) {
                $mapCurrentRef = & $mapActiveRef[$ns];
                $mapActiveRef = & $mapActiveRef[$ns]['S'];
            } elseif ($create) {
                $mapActiveRef[$ns] = array(
                                    'P' => '',
                                    'S' => array()
                                    );

                $mapCurrentRef = & $mapActiveRef[$ns];
                $mapActiveRef = & $mapActiveRef[$ns]['S'];
            } else {
                break;
            }

            unset($splitedRemainNS[$nsk]);
        }

        return array(
            'Ref' => & $mapCurrentRef,
            'Parent' => & $mapParentRef,
            'Remain' => implode(DIRECTORY_SEPARATOR, $splitedRemainNS),
        );
    }

    /**
     * Register hooks from plugin
     *
     * @param array $pluginName Plugin name
     * @param bool $mainFile File that contains the plugin class
     *
     * @return bool ture when succeeded, false otherwise
     */
    public static function registerPlugin($pluginName, $mainFile)
    {
        $pluginClassname = $pluginName . 'Plugin';
        $invokeResult = null;

        if (!static::registerScope($pluginClassname, $mainFile)) {
            throw new \Exception(
                'Cannot register class scope for plugin class '
                . $pluginClassname .'.'
            );

            return false;
        }

        $plugRef = new \ReflectionClass($pluginClassname);

        if (!$plugRef->implementsInterface('\Facula\Base\Implement\Plugin')) {
            throw new \Exception(
                'A facula plugin have to implement interface: '
                . '\\Facula\\Base\\Implement\\Plugin'
            );

            return false;
        }

        if (!is_array($invokeResult = $plugRef->getMethod('register')->invoke(null))) {
            throw new \Exception(
                'Registering plugin '
                . $pluginClassname
                . ', but registrant returns invalid result.'
            );

            return false;
        }

        foreach ($invokeResult as $hookName => $binded) {
            static::registerHook($hookName, $pluginClassname, $binded);
        }

        return true;
    }

    /**
     * Unregister hooks from plugin
     *
     * @param array $pluginName Plugin name
     *
     * @return bool ture when successed, false otherwise
     */
    public static function unregisterPlugin($pluginName)
    {
        $pickedUp = false;
        $pluginClassname = $pluginName . 'Plugin';

        // We can use static::unregisterHook to do same thing, but below code is faster
        foreach (static::$components['Hooks'] as $hook => $hookName) {
            if ($hookName == $pluginClassname) {
                $pickedUp = true;

                unset(static::$components['Hooks'][$hook][$hookName]);
            }
        }

        if ($pickup) {
            return static::unregisterScope($pluginClassname);
        } else {
            throw new \Exception(
                'Unregistering plugin '
                . $pluginClassname
                . '. But it seems not registered.'
            );
        }

        return false;
    }

    /**
     * Register a hook
     *
     * @param string $hook Hook name
     * @param string $processorName Processor Name
     * @param string $callback Hook processor
     *
     * @return bool true when registered, false when fail.
     */
    public static function registerHook($hook, $processorName, $callback)
    {
        if (isset(static::$components['Hooks'][$hook][$processorName])) {
            throw new \Exception(
                'Registering hook '
                . $hook
                . ' for '
                . $processorName
                . ', But seems it already registered'
            );

            return false;
        }

        if (is_callable($callback)) {
            if (!static::$instance && is_object($callback)) {
                throw new \Exception(
                    'Registering hook '
                    . $hook
                    . ' for '
                    . $processorName
                    . '. But framework not ready.'
                );

                return false;
            }

            static::$components['Hooks'][$hook][$processorName] = $callback;
        } else {
            throw new \Exception(
                'Registering hook '
                . $hook
                . ' for '
                . $processorName
                . '. But seems the callback function is not callable.'
            );

            return false;
        }

        return false;
    }

    /**
     * Unregister a hook
     *
     * @param string $hook Hook name
     * @param string $processorName Processor Name
     *
     * @return bool true when unregister success, false when fail.
     */
    public static function unregisterHook($hook, $processorName)
    {
        if (isset(static::$components['Hooks'][$hook][$processorName])) {
            unset(static::$components['Hooks'][$hook][$processorName]);

            return true;
        } else {
            throw new \Exception(
                'Unregistering hook '
                . $hook
                . ' for '
                . $processorName
                . '. But seems it not registered.'
            );
        }

        return false;
    }

    /**
     * Run a hook
     *
     * @param string $hook Hook
     * @param array $params Parameter in array
     * @param array $errors A reference to get error details of plugins
     *
     * @return mixed Result array when success, false when failed.
     */
    public static function summonHook($hook, array $params = array(), array &$errors = array())
    {
        $hookCall = $error = null;
        $results = array();

        if (isset(static::$components['Hooks'][$hook])) {
            foreach (static::$components['Hooks'][$hook] as $hookName => $callback) {
                $error = null;
                $hookCall = $callback;

                $results[$hookName] = $hookCall($params, $error);
                $errors[$hookName] = $error;
            }
        }

        return $results;
    }

    /**
     * Get size of specified hook
     *
     * @param string $hook Hook
     *
     * @return integer Queue size of hook
     */
    public static function getHookSize($hook)
    {
        if (isset(static::$components['Hooks'][$hook])) {
            return count(static::$components['Hooks'][$hook]);
        }

        return 0;
    }

    /**
     * Get application info
     *
     * @return array Of application informations
     */
    public static function getVersion()
    {
        if (isset(static::$instance)) {
            return array(
                'Base' => 'Facula ' . __FACULAVERSION__,

                'App' => isset(static::$instance->setting['AppName'])
                        ? static::$instance->setting['AppName'] : 'Facula App',

                'Ver' => isset(static::$instance->setting['AppVersion'])
                        ? static::$instance->setting['AppVersion'] : '0.0',

                'Boot' => isset(static::$instance->setting['Common']['BootVersion'])
                        ? static::$instance->setting['Common']['BootVersion'] : '0',
            );
        }

        return array();
    }

    /**
     * Get all loaded cores
     *
     * @param string $coreName Key name of the core
     *
     * @return mixed Object when got the core, false when failed.
     */
    public static function core($coreName)
    {
        if (static::$instance) {
            if (isset(static::$instance->cores[$coreName])) {
                return static::$instance->cores[$coreName];
            } else {
                throw new \Exception(
                    'Function core '
                    . $coreName
                    . ' not available. '
                    . 'You can only acquire following cores: '
                    . (implode(', ', array_keys(static::$instance->cores)))
                    . '.'
                );
            }
        } else {
            throw new \Exception(
                'Facula must be initialized to get core '
                . $coreName
                . ' to work.'
            );
        }

        return false;
    }

    /**
     * Get all loaded cores
     *
     * @return mixed array of cores, or false when failed.
     */
    public static function getAllCores()
    {
        if (static::$instance && !empty(static::$instance->cores)) {
            return static::$instance->cores;
        }

        return false;
    }

    /**
     * The entity of initialization
     *
     * @param array $cfg Configuration
     *
     * @return void
     */
    protected function __construct(array &$cfg)
    {
        // Following process will only be call when the framework boot from cold

        // First, import all the settings in to Facula core
        if ($this->importConfigs($cfg)) {
            $this->setting['Common']['AppName'] = isset($cfg['AppName'][0]) ? $cfg['AppName'] : 'Facula App';
            $this->setting['Common']['AppVersion'] = isset($cfg['AppVersion'][0]) ? $cfg['AppVersion'] : '0.0';
            $this->setting['Common']['BootVersion'] = FACULA_TIME;

            // Now we have settings, the first step: register namespace
            $this->registerAllNamespaces();

            // Second, start components pickup
            $this->pickComponents();

            // Now, init up all function cores
            $this->initCores();

            static::summonHook('cold');
        }
    }

    /**
     * Warm up initializer
     *
     * @return void
     */
    protected function ready()
    {
        // Initialize all cores
        foreach ($this->cores as $core) {
            if (!$core->inited()) {
                throw new \Exception(
                    'Warming up core '
                    . get_class($core)
                    . '. But it returns false.'
                );
            }
        }

        // Then, load routines and clear the routines array for release memory
        $this->includeComponents('Routine');
        static::$components['Routine'] = array();

        static::summonHook('ready');

        return true;
    }

    /**
     * Include all %type%.*.php file
     *
     * @param string $type Components file type. Limit to Inc|Rot
     *
     * @return void
     */
    protected function includeComponents($type)
    {
        if (isset(static::$components[$type])) {
            foreach (static::$components[$type] as $file) {
                require($file);
            }
        }
    }

    /**
     * Initialize all needed function cores
     *
     * @return void
     */
    protected function initCores()
    {
        $cores = array();

        $cores += static::$cfg['RequiredCores'];

        if (isset($this->setting['UsingCore'])
            && is_array($this->setting['UsingCore'])) {

            foreach ($this->setting['UsingCore'] as $coreName => $coreClass) {
                if (!isset($cores[$coreName])) {
                    $cores[$coreName] = $coreClass;
                }
            }
        }

        foreach ($cores as $coreName => $coreClass) {
            $this->initCore($coreName, $coreClass);
        }

        // Release the setting for saving memory.
        unset($this->setting['UsingCore']);
    }

    /**
     * Initialize specified core
     *
     * @param string $coreName Key name of the core
     * @param string $coreClass Class name of the core
     *
     * @return void
     */
    protected function initCore($coreName, $coreClass)
    {
        $coreRef = new \ReflectionClass($coreClass);

        if (!$coreRef->implementsInterface('\Facula\Base\Implement\Factory\Core')) {
            throw new \Exception(
                'Initializing core '
                . $coreClass
                . '. But seems it not implement interface \\Facula\\Base\\Implement\\Factory\\Core.'
            );

            return false;
        }

        if (!$this->cores[$coreName] = $coreRef->getMethod('getInstance')->invoke(
            null,
            $this->squeezeCoreSetting($coreName),
            $this->exportCommonSetting(),
            $this
        )) {
            throw new \Exception(
                'Initializing core '
                . $coreClass
                . '. But it returns false.'
            );

            return false;
        }

        return true;
    }

    /**
     * Export a core setting and remove it from setting
     *
     * @param string $coreName Key name of the core
     *
     * @return array
     */
    protected function squeezeCoreSetting($coreName)
    {
        $setting = array();

        if (!isset($this->setting['Core'][$coreName])) {
            $setting = array();
        } else {
            $setting = $this->setting['Core'][$coreName];

            unset($this->setting['Core'][$coreName]);
        }

        return $setting;
    }

    /**
     * Export common setting
     *
     * @return array
     */
    protected function exportCommonSetting()
    {
        return isset($this->setting['Common']) ? $this->setting['Common'] : array();
    }

    /**
     * Import setting form configuration
     *
     * @param array $cfg Configuration array
     *
     * @return bool Always return true
     */
    protected function importConfigs(array &$cfg)
    {
        foreach ($cfg as $key => $value) {
            $this->setting[$key] = $value;
        }

        return true;
    }

    /**
     * Register all built in and configured Namespace
     *
     * @return bool Always return true
     */
    protected function registerAllNamespaces()
    {
        $namespaces = $validNamespaces = array();

        // Convert core namespace's relative path in to actual path
        foreach (static::$cfg['FWNS'] as $namespace => $path) {
            static::registerNamespace($namespace, $path);
        }

        if (isset($this->setting['Namespaces']) && is_array($this->setting['Namespaces'])) {
            foreach ($this->setting['Namespaces'] as $namespace => $path) {
                static::registerNamespace($namespace, $path);
            }
        }

        return true;
    }

    /**
     * Discover components, and import them into Framework
     *
     * @return void
     */
    protected function pickComponents()
    {
        $components = $modules = array();

        if (isset($this->setting['Paths']) && is_array($this->setting['Paths'])) {

            foreach ($this->setting['Paths'] as $path) {
                $scanner = new \Facula\Base\Tool\File\ModuleScanner($path);

                // Must use array_merge. Yes, it's slow but it can auto resolve reindex problem
                $modules = array_merge($modules, $scanner->scan());
            }

            foreach ($modules as $module) {
                switch ($module['Prefix']) {
                    case 'include':
                        static::$includes[] = $module['Path'];

                        // Require the include file for init
                        require($module['Path']);
                        break;

                    case 'routine':
                        static::$components['Routine'][] = $module['Path'];
                        break;

                    case 'plugin':
                        self::registerPlugin($module['Name'], $module['Path']);
                        break;

                    case 'class':
                        self::registerScope(strtolower($module['Name']), $module['Path']);
                        break;

                    default:
                        self::registerScope(strtolower($module['Name']), $module['Path']);
                        break;
                }
            }

            // Unset the setting and release memory
            unset($this->setting['Paths'], $this->setting['Namespaces']);
        }
    }
}
