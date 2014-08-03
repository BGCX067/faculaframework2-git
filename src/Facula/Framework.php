<?php

/**
 * Struct Manage Unit
 *
 * Facula Framework 2014 (C) Rain Lee
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
 * @copyright  2014 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula;

/** Inform modules the Facula Framework is declared */
define('IN_FACULA', true);

/** Version of current Facula version */
define('__FACULAVERSION__', '0.1.0-alpha');

/** Root of Facula Framework */
define('FACULA_ROOT', dirname(__FILE__));

/** Root of project directory */
define('PROJECT_ROOT', realpath('.'));

/** Separator for namespace */
define('NAMESPACE_SEPARATER', '\\');

/** Stable current time for this runtime */
define('FACULA_TIME', isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time());

/**
 * Struct Manage Unit
 *
 * Core of Facula Framework
 */
class Framework
{
    /** Mirror constant for FACULA_ROOT */
    const ROOT = FACULA_ROOT;

    /** Mirror constant for PROJECT_ROOT */
    const PATH = PROJECT_ROOT;

    /** Mirror constant for FACULA_TIME */
    const TIME = FACULA_TIME;

    /** Singleton instance container */
    protected static $instance = null;

    /** Temporary data pool */
    protected static $pool = array();

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
        'Updated' => '2014',
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

        // Framework namespace
        'FWNS' => array(
            'Facula' => FACULA_ROOT,
        ),

        // Max directory seek depth for components
        'CompMaxSeekDepth' => 2,

        // Max directory seek depth for packages
        'PkgMaxSeekDepth' => 1,
    );

    /** Cores that needed for every boot time */
    protected static $requiredCores = array(
        'debug' => 'Facula\Core\Debug',
        'object' => 'Facula\Core\Object',
        'request' => 'Facula\Core\Request',
        'response' => 'Facula\Core\Response'
    );

    /** Mirror of Components information container */
    protected static $components = array();

    /** Auto includes files */
    protected static $includes = array();

    /** Initializer files which only be load when cold up */
    protected static $initializers = array();

    /** Data container for Facula framework instance */
    protected $setting = array();

    /** Core container */
    protected $cores = array();

    /**
     * Initialization exposure
     *
     * @param array $cfg Configuration array
     *
     * @return mixed Return a singleton Facula object when successed, false otherwise.
     */
    public static function run(array &$cfg = array())
    {
        if (!static::$instance) {
            spl_autoload_register(array(__CLASS__, 'loadClass'));

            static::$profile['StartTime'] = microtime(true);

            if (!empty($cfg['StateCache'])) {
                if (!static::$instance = static::initFromStateCache($cfg['StateCache'])) {
                    static::$instance = new static($cfg);

                    static::saveStateCache($cfg['StateCache']);
                }
            } else {
                static::$instance = new static($cfg);
            }

            static::$instance->ready();

            static::$profile['InitedTime'] = microtime(true);
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

        if (file_exists($stateFile)) {
            require($stateFile);

            if (!empty($cache)) {
                if (isset($cache['Cmp'])) {
                    static::$components = $cache['Cmp'];
                }

                // Require all include files
                if (isset($cache['Inc'])) {
                    foreach ($cache['Inc'] as $files) {
                        static::requireFile($files);
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
        if (!static::loadScope($className)) {
            return static::loadNamespace($className);
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
        if (isset(static::$components['Scope'][$class])) {
            static::requireFile(static::$components['Scope'][$class]);

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
        if (isset(static::$components['Scope'][$class])) {
            trigger_error(
                'Trying register a class "'
                . $class
                . '" to scope while it already registered.',
                E_USER_ERROR
            );

            return false;
        }

        if (!is_readable($file)) {
            trigger_error(
                'Trying register a class "'
                . $class
                . '", but the class file "'
                . $file
                . '" is not readable.',
                E_USER_ERROR
            );

            return false;
        }

        return static::$components['Scope'][$class] = str_replace(
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
        if (!isset(static::$components['Scope'][$class])) {
            trigger_error(
                'Trying unregister a class "'
                . $class
                . '", but the class was not found.',
                E_USER_ERROR
            );

            return false;
        }

        unset(static::$components['Scope'][$class]);

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

        if (isset($map['Path']) && $map['Path']) {
            $fullPath = $map['Path']
                        . DIRECTORY_SEPARATOR
                        . ($map['Remain'] ? implode(DIRECTORY_SEPARATOR, $map['Remain']) . DIRECTORY_SEPARATOR : '')
                        . $className
                        . '.' . static::$cfg['PHPExt'];

            if (file_exists($fullPath)) {

                // Load initializers and components when needed
                if ($map['Ref']['I'] && !isset(static::$pool['NSLoaded'][$map['Ref']['I']])) {
                    if (!static::$instance) {
                        trigger_error(
                            'Loading from namespace '
                            . $class
                            . ' with subcomponents, But framework not ready for that.'
                            . ' The framework needs to fully initialized'
                            . ' to load subcomponents from the namespace.',
                            E_USER_ERROR
                        );

                        return false;
                    }

                    // Register classes
                    if (isset($map['Ref']['M']['C'])) {
                        foreach ($map['Ref']['M']['C'] as $className => $classPath) {
                            static::registerScope($className, $classPath);
                        }
                    }

                    // Register plugins
                    if (isset($map['Ref']['M']['P'])) {
                        foreach ($map['Ref']['M']['P'] as $pluginPath) {
                            static::initPlugin($pluginPath);
                        }
                    }

                    // Load routines at last
                    if (isset($map['Ref']['M']['R'])) {
                        foreach ($map['Ref']['M']['R'] as $routinePath) {
                            static::requireFile($routinePath);
                        }
                    }

                    static::$pool['NSLoaded'][$map['Ref']['I']] = true;
                }

                static::requireFile($fullPath);

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
     * @param array $componentPaths Paths of Subcomponents under this namespace.
     *                              Subcomponents under this set will be only load on namespace called
     *                              And only load once according to the last picked namespace
     *
     * @return bool Return true when success, false otherwise.
     */
    public static function registerNamespace($nsPrefix, $path, array $componentPaths = array())
    {
        $currentRoot = null;
        $subModules = array();

        if (is_dir($path)) {
            $map = self::locateNamespace(self::splitNamespace($nsPrefix), true);

            if (isset($map['Ref']['R']) && !$map['Ref']['R']) {
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
                $map['Ref']['R'] = true;

                // Check of sub componentPaths has set, include it if so
                if (!empty($componentPaths)) {
                    $map['Ref']['I'] = crc32($nsPrefix); // Fine with CRC32

                    // If the framework not finish init and primary namespaces not registered
                    // (Notice that, static::$pool['NSInited'] will be not set when boot in warm up)
                    if (!static::$instance && !isset(static::$pool['NSInited'])) {
                        trigger_error(
                            'Registering namespace '
                            . $nsPrefix
                            . ' with subcomponents, But framework not ready for that.'
                            . ' The primary namespaces must be registered before registering'
                            . ' any namespace which contain subcomponents.',
                            E_USER_ERROR
                        );

                        return false;
                    }

                    foreach ($componentPaths as $componentPath) {
                        $scanner = new Base\Tool\File\ModuleScanner(
                            Base\Tool\File\PathParser::get($componentPath),
                            static::$cfg['CompMaxSeekDepth']
                        );

                        foreach ($scanner->scan() as $subModule) {
                            switch ($subModule['Prefix']) {
                                case 'routine':
                                    $map['Ref']['M']['R'][] = $subModule['Path'];
                                    break;

                                case 'plugin':
                                    $map['Ref']['M']['P'][] = $subModule['Path'];
                                    break;

                                case 'class':
                                    $map['Ref']['M']['C'][ucfirst($subModule['Name'])] = $subModule['Path'];
                                    break;

                                default:
                                    break;
                            }
                        }
                    }
                } else {
                    $map['Ref']['I'] = 0;
                }

                return true;
            } else {
                trigger_error(
                    'Trying to register a namespace "'
                    . $nsPrefix
                    . '" while it already registered.',
                    E_USER_ERROR
                );
            }

        } else {
            trigger_error(
                'Path '
                . $path
                . ' for namespace '
                . $nsPrefix
                . ' not exist.',
                E_USER_ERROR
            );
        }

        return false;
    }

    /**
     * Unregister a namespace prefix
     *
     * @param string $nsPrefix The prefix
     * @param bool $vague Search namespace vaguely,
     *              if you register a name say \Vendor\Level1 and unregister \Vendor\Level1\Level2,
     *              Then $vague = true, the \Vendor\Level1 will be unregistered;
     *              Then $vague = false, unregister will return false
     *              because there is no \Vendor\Level1\Level2 registered;
     *
     * @return bool Return true when success, false otherwise.
     */
    public static function unregisterNamespace($nsPrefix, $vague = false)
    {
        $map = self::locateNamespace(self::splitNamespace($nsPrefix), false);

        if (isset($map['Ref']) && (empty($map['Remain']) || !$vague)) {
            if (!empty($map['Ref']['S'])) {
                $map['Ref']['P'] = '';
            } else {
                $map['Ref'] = null;
            }
        } else {
            trigger_error(
                'Trying unregister a namespace while it not existed.',
                E_USER_ERROR
            );

            return false;
        }

        return true;
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
            NAMESPACE_SEPARATER,
            trim(
                str_replace(
                    array(
                        '/',
                        '\\',
                        DIRECTORY_SEPARATOR
                    ),
                    NAMESPACE_SEPARATER,
                    $namespace
                ),
                NAMESPACE_SEPARATER
            )
        );
    }

    /**
     * Get reference of the namespace in inner Namespacing map
     *
     * @param array $splitedNamespace The namespace
     * @param bool $create Set to true to create the array when it's not exist, false to return last matched
     *
     * @return array array('Ref' => reference, 'Parent' => reference, 'Remain' => array)
     */
    protected static function locateNamespace(array $splitedNamespace, $create = false)
    {
        $skiping = false;
        $splitedNS = $splitedRemainNS = $splitedNamespace;
        $currentMapRef = null;
        $currentPath = '';
        $remainPreRemoveNS = array();

        $mapSelectedRef = & static::$components['NSMap'];

        foreach ($splitedNS as $index => $namespace) {
            if (isset($mapSelectedRef[$namespace])) {
                if (!$mapSelectedRef[$namespace]['P']) {
                    $remainPreRemoveNS[] = $index;
                } else {
                    $currentPath = $mapSelectedRef[$namespace]['P'];

                    if (isset($remainPreRemoveNS[0])) {

                        foreach ($remainPreRemoveNS as $removeIndex) {
                            unset($splitedRemainNS[$removeIndex]);
                        }

                        $remainPreRemoveNS = array();
                    }

                    unset($splitedRemainNS[$index]);
                }

                $currentMapRef = & $mapSelectedRef[$namespace];

                $mapSelectedRef = & $mapSelectedRef[$namespace]['S'];
            } elseif ($create) {
                $mapSelectedRef[$namespace] = array(
                    'S' => array(),
                    'P' => '',
                    'R' => false,
                );

                $currentMapRef = & $mapSelectedRef[$namespace];

                $mapSelectedRef = & $mapSelectedRef[$namespace]['S'];

                unset($splitedRemainNS[$index]);
            } else {
                break;
            }
        }

        return array(
            'Path' => $currentPath,
            'Ref' => & $currentMapRef,
            'Remain' => $splitedRemainNS,
        );
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
            trigger_error(
                'Registering hook '
                . $hook
                . ' for '
                . $processorName
                . ', But seems it already registered',
                E_USER_ERROR
            );

            return false;
        }

        if (is_callable($callback)) {

            // Check if framework has finished init
            // If a closure callback registered before framework finish init, it may cause
            // no callable hook problem, because callback is not cachedable
            if (!static::$instance && is_object($callback)) {
                trigger_error(
                    'Registering hook '
                    . $hook
                    . ' for '
                    . $processorName
                    . '. But framework not ready for a closure callback.',
                    E_USER_ERROR
                );

                return false;
            }

            static::$components['Hooks'][$hook][$processorName] = $callback;
        } else {
            trigger_error(
                'Registering hook '
                . $hook
                . ' for '
                . $processorName
                . '. But seems the callback function is not callable.',
                E_USER_ERROR
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
            trigger_error(
                'Unregistering hook '
                . $hook
                . ' for '
                . $processorName
                . '. But seems it not registered.',
                E_USER_ERROR
            );
        }

        return false;
    }

    /**
     * Safe wrap for require the package declaration file
     *
     * @param string $file Path to the declaration file
     *
     * @return void
     */
    protected static function loadPackageDeclarationFile($file)
    {
        $package = array();

        require($file);

        if (is_array($package)) {
            return $package;
        }

        return array();
    }

    /**
     * Get the root directory of the package
     *
     * @param string $pkgName Package name
     *
     * @return mixed Return the path if package has been declared, or false otherwise.
     */
    public static function getPackagePath($pkgName)
    {
        if (isset(static::$components['Packages'][$pkgName])) {
            return static::$components['Packages'][$pkgName];
        }

        return false;
    }

    /**
     * Register a package into Framework
     *
     * @param string $pkgName Name of this package
     * @param string $pkgDir Root directory of the package
     *
     * @return bool Return true when succeed, false otherwise.
     */
    public static function registerPackage($pkgName, $pkgDir)
    {
        $package = $packageLoads = $packagePaths = array();
        $nsFolder = $dclFile = '';
        $pathInfo = pathinfo($dclFile);

        if (!is_dir($pkgDir)) {
            trigger_error(
                '\"'
                . $pkgDir
                . '\" is not a directory for package.',
                E_USER_ERROR
            );

            return false;
        }

        $dclFile = $pkgDir
                . DIRECTORY_SEPARATOR
                . 'package.'
                . $pkgName
                . '.'
                . static::$cfg['PHPExt'];

        if (!is_file($dclFile)) {
            trigger_error(
                'Package declaration file '
                . $dclFile
                . ' not existed.',
                E_USER_ERROR
            );

            return false;
        }

        // Load package info
        $package = static::loadPackageDeclarationFile($dclFile);

        if (isset($package['Requires'])) {
            if (!is_array($package['Requires'])) {
                trigger_error(
                    'Registering package "' . $pkgName . '" from file "'
                    . $dclFile
                    . '", but the "Requires" option is invalid.',
                    E_USER_ERROR
                );

                return false;
            }

            foreach ($package['Requires'] as $required) {
                if (!isset(static::$components['Packages'][$required])
                && !isset(static::$pool['AlPkgs'][$required])) {
                    trigger_error(
                        'Package "' . $pkgName . '" declared in file "'
                        . $dclFile
                        . '" can\'t be enabled. '
                        . 'It requires another package "' . $required . '" to be enabled, '
                        . 'which is not.',
                        E_USER_WARNING
                    );

                    return false;
                }
            }
        }

        // Check if the declaration file is valid
        if (!isset($package['Namespace'][0])) {
            trigger_error(
                'Registering package "' . $pkgName . '" from file "'
                . $dclFile
                . '", but the package namespace name not declared',
                E_USER_ERROR
            );

            return false;
        }

        if (isset($package['Loads'])) {
            if (!is_array($package['Loads'])) {
                trigger_error(
                    'Registering package "' . $pkgName . '" from file "'
                    . $dclFile
                    . '", but the "Loads" option is invalid.',
                    E_USER_ERROR
                );

                return false;
            }

            foreach ($package['Loads'] as $componentPath) {
                $packageLoads[] = Base\Tool\File\PathParser::get(
                    $pkgDir
                    . DIRECTORY_SEPARATOR
                    . $componentPath
                );
            }
        }

        // Get Namespace folder
        if (isset($package['Folder'], $package['Folder'][0])) {
            $nsFolder = $pkgDir . DIRECTORY_SEPARATOR . $package['Folder'];
        } else {
            $nsFolder = $pkgDir;
        }

        // Register the namespace with Loads
        static::registerNamespace($package['Namespace'], $nsFolder, $packageLoads);

        // Paths
        if (isset($package['Paths'])) {
            if (!is_array($package['Paths'])) {
                trigger_error(
                    'Registering package "' . $pkgName . '" from file "'
                    . $dclFile
                    . '", but the "Paths" option is invalid.',
                    E_USER_ERROR
                );

                return false;
            }

            foreach ($package['Paths'] as $packagePath) {
                $packagePaths[] = Base\Tool\File\PathParser::get(
                    $pkgDir
                    . DIRECTORY_SEPARATOR
                    . $packagePath
                );
            }

            static::pickComponents($packagePaths);
        }

        static::$components['Packages'][$pkgName] = $pkgDir;

        return true;
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
                trigger_error(
                    'Function core '
                    . $coreName
                    . ' not available. '
                    . 'You can only acquire following cores: '
                    . (implode(', ', array_keys(static::$instance->cores)))
                    . '.',
                    E_USER_ERROR
                );
            }
        } else {
            trigger_error(
                'Facula must be initialized to get core '
                . $coreName
                . ' to work.',
                E_USER_ERROR
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
     * Safe wrapper to load file
     *
     * @param string $file Path to the PHP file
     *
     * @return void
     */
    protected static function requireFile($file)
    {
        require($file);
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

            // Get some of the info of current PHP installation
            $this->setting['Common']['PHP'] = array(
                'SAPI' => strtolower(php_sapi_name()),
                'UName' => php_uname(),
                'Version' => strtolower(phpversion()),
                'ServerName' => gethostname(),
            );

            // Now we have settings, the first step: register namespace
            $this->registerAllNamespaces();
            static::$pool['NSInited'] = true;

            // Second, start components pickup
            $this->pickAllComponents();
            static::$pool['CompInited'] = true;

            // And then, register all packages
            $this->registerAllPackages();
            static::$pool['PkgInited'] = true;

            // Load all include file for init user per defined object and functions
            foreach (static::$includes as $includeFile) {
                static::requireFile($includeFile);
            }

            // Now, init up all function cores
            $this->initAllCores();
            static::$pool['CoreInited'] = true;

            static::summonHook('cold');

            static::$pool['ColdFinished'] = true;
        }
    }

    /**
     * Magic to get function core directly from framework instance
     *
     * @param string $key Function core key name
     *
     * @return mixed Return the core object when succeed, throw a error otherwise
     */
    public function __get($key)
    {
        if (!isset($this->cores[$key])) {
            trigger_error(
                'Function core '
                . $key
                . ' not available. '
                . 'You can only acquire following cores: '
                . (implode(', ', array_keys($this->cores)))
                . '.',
                E_USER_ERROR
            );
        }

        return $this->cores[$key];
    }

    /**
     * Magic to add a new function core to framework
     *
     * @param string $key Function core key name
     * @param string $key Function core class name
     *
     * @return void
     */
    public function __set($key, $val)
    {
        $this->initCore($key, $val);
    }

    /**
     * Magic to check if function core exist
     *
     * @param string $key Function core key name
     *
     * @return mixed Return true when it's exist, false otherwise
     */
    public function __isset($key)
    {
        return isset($this->cores[$key]) ? true : false;
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
                trigger_error(
                    'Warming up core "'
                    . get_class($core)
                    . '". But it returns false.',
                    E_USER_ERROR
                );
            }
        }

        // Load all initializer file for cold init
        if (isset(static::$pool['ColdFinished'])) {
            foreach (static::$initializers as $initializerFile) {
                static::requireFile($initializerFile);
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
                static::requireFile($file);
            }
        }
    }

    /**
     * Initialize all needed function cores
     *
     * @return void
     */
    protected function initAllCores()
    {
        $cores = array();

        $cores += static::$requiredCores;

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
        if (!class_exists($coreClass)) {
            trigger_error(
                'Initializing core "'
                . $coreClass
                . '". But the core class '
                . $coreClass
                . ' seems not exist.',
                E_USER_ERROR
            );
        }

        $coreRef = new \ReflectionClass($coreClass);

        if (!$coreRef->implementsInterface('Facula\Base\Implement\Factory\Core')) {
            trigger_error(
                'Initializing core "'
                . $coreClass
                . '". But seems it not implement interface '
                . 'Facula\\Base\\Implement\\Factory\\Core.',
                E_USER_ERROR
            );

            return false;
        }

        if (!$this->cores[$coreName] = $coreRef->getMethod('getInstance')->invoke(
            null,
            $this->squeezeCoreSetting($coreName),
            $this->exportCommonSetting(),
            $this
        )) {
            trigger_error(
                'Initializing core "'
                . $coreClass
                . '". But it returns false.',
                E_USER_ERROR
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

            unset($this->setting['Namespaces']);
        }

        return true;
    }

    /**
     * Pickup all declared components and register them in to Framework
     *
     * @return void
     */
    protected function pickAllComponents()
    {
        if (isset($this->setting['Paths']) && is_array($this->setting['Paths'])) {
            static::pickComponents($this->setting['Paths']);

            // Release memory
            unset($this->setting['Paths']);
        }

        return true;
    }

    /**
     * Pickup all declared packages and register them in to Framework
     *
     * @return void
     */
    protected function registerAllPackages()
    {
        $packageRaws = array();

        if (isset($this->setting['Packages']) && is_array($this->setting['Packages'])) {
            foreach ($this->setting['Packages'] as $path) {
                $scanner = new Base\Tool\File\ModuleScanner(
                    Base\Tool\File\PathParser::get($path),
                    static::$cfg['PkgMaxSeekDepth']
                );

                foreach ($scanner->scan() as $modules) {
                    switch ($modules['Prefix']) {
                        case 'package':
                            if (!isset($packageRaws[$modules['Name']])) {
                                    $packageRaws[$modules['Name']] =
                                        $modules['Dir'];

                                    static::$pool['AlPkgs'][$modules['Name']] = true;
                            } else {
                                trigger_error(
                                    'Registering package from file "'
                                    . $modules['Path']
                                    . '", but it seems conflicted with another package "'
                                    . $packageRaws[$modules['Name']]
                                    . '".',
                                    E_USER_ERROR
                                );
                            }
                            break;

                        default:
                            break;
                    }
                }
            }

            foreach ($packageRaws as $packageName => $packageDir) {
                static::registerPackage($packageName, $packageDir);
            }

            // Release memory
            unset($this->setting['Packages']);
        }

        return false;
    }

    /**
     * Register hooks from plugin classes
     *
     * @param bool $mainFile File that contains the plugin class
     *
     * @return bool ture when succeeded, false otherwise
     */
    protected static function initPlugin($mainFile)
    {
        $invokeResult = null;
        $declaredClasses = get_declared_classes();

        if (!is_readable($mainFile)) {
            trigger_error(
                'Plugin file '
                . $mainFile
                . ' is not readable.',
                E_USER_ERROR
            );

            return false;
        } else {
            static::requireFile($mainFile);
        }

        foreach (array_diff(get_declared_classes(), $declaredClasses) as $key => $pluginClassname) {
            if (!static::registerScope($pluginClassname, $mainFile)) {
                trigger_error(
                    'Cannot register class scope for plugin class "'
                    . $pluginClassname
                    . '".',
                    E_USER_ERROR
                );

                return false;
            }

            $plugRef = new \ReflectionClass($pluginClassname);

            if (!$plugRef->implementsInterface('Facula\Base\Implement\Plugin')) {
                trigger_error(
                    'A facula plugin have to implement interface: '
                    . 'Facula\\Base\\Implement\\Plugin',
                    E_USER_ERROR
                );

                return false;
            }

            if (!is_array($invokeResult = $plugRef->getMethod('register')->invoke(null))) {
                trigger_error(
                    'Registering plugin "'
                    . $pluginClassname
                    . '", but registrant returns invalid result.',
                    E_USER_ERROR
                );

                return false;
            }

            foreach ($invokeResult as $hookName => $binded) {
                static::registerHook($hookName, $pluginClassname, $binded);
            }
        }

        return true;
    }

    /**
     * Discover components, and import them into Framework
     *
     * @return void
     */
    protected static function pickComponents(array $paths)
    {
        $modules = array();

        foreach ($paths as $path) {
            $scanner = new Base\Tool\File\ModuleScanner(
                Base\Tool\File\PathParser::get($path),
                static::$cfg['CompMaxSeekDepth']
            );

            foreach ($scanner->scan() as $module) {
                switch ($module['Prefix']) {
                    case 'include':
                        static::$includes[] = $module['Path'];
                        break;

                    case 'initialize':
                        static::$initializers[] = $module['Path'];
                        break;

                    case 'routine':
                        static::$components['Routine'][] = $module['Path'];
                        break;

                    case 'plugin':
                        static::initPlugin($module['Path']);
                        break;

                    case 'class':
                        static::registerScope(ucfirst($module['Name']), $module['Path']);
                        break;

                    default:
                        static::registerScope($module['Name'], $module['Path']);
                        break;
                }
            }
        }
    }
}
