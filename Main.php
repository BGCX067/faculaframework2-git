<?php

/**
 * Facula Framework Struct Manage Unit
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
 * @package    FaculaFramework
 * @version    2.2 prototype
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula;

define('__FACULAVERSION__', '2 Prototype 0.2');

define('FACULA_ROOT', dirname(__FILE__));
define('PROJECT_ROOT', realpath('.'));

define('IN_FACULA', true);
define('FACULA_TIME', isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time());

/**
 * The core of Facula Framework
 */
class Main
{
    /** Singleton instance container */
    protected static $instance = null;

    /** Config container */
    protected static $cfg = array(
        // File extension for PHP script files
        'PHPExt' => 'php',

        // The char to split namespaces
        'NSSpliter' => DIRECTORY_SEPARATOR,

        // Framework namespace
        'FWNS' => array(
            '\Facula' => FACULA_ROOT,
        ),

        // Cores that needed for every boot time
        'RequiredCores' => array(
            '\Facula\Cores\Debug\Core',
            '\Facula\Cores\Object\Core',
            '\Facula\Cores\Request\Core',
            '\Facula\Cores\Response\Core'
        ),
    );

    /** Config container */
    static protected $nsMap = array();

    /** Class and other files that will be use by Facula */
    static protected $scopeMap = array();

    /** Hooks will be use by Facula */
    static protected $hookMap = array();

    /** Data container for Facula framework instance */
    protected $setting = array( );

    /** Mirror: For static::$nsMap. It will be reference by static::$nsMap after warm boot */
    protected $namespaces = array();

    /** Mirror: Scope information container */
    protected $scope = array();

    /** Mirror: Hook information container */
    protected $hooks = array();

    /** Mirror of Components information container */
    protected $components = array();

    /**
     * Initialization exposure
     *
     * @param array $cfg Configuration array
     *
     * @return mixed Return a singleton Facula object when successed, false otherwise.
     */
    public static function run(array &$cfg)
    {
        spl_autoload_register(function ($class) {
            return static::loadClass($class);
        });

        if (static::$instance) {
            return static::$instance;
        }

        return (static::$instance = new static($cfg));
    }

    /**
     * Global class autoloader
     *
     * @param string $className Fully qualified class name
     *
     * @return bool Return true when success, false otherwise.
     */
    protected static function loadClass($className)
    {
        // Check if this is a namespace calling
        if (strpos($className, static::$cfg['NSSpliter'])) {
            return static::loadNamespace($className);
        } else {
            return static::loadScope($className);
        }

        return false;
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

        $fullPath = $map['Ref']['P']
                    . DIRECTORY_SEPARATOR
                    . ($map['Remain'] ? $map['Remain'] . DIRECTORY_SEPARATOR : '')
                    . $className
                    . '.' . static::$cfg['PHPExt'];

        if ($map['Ref']['P'] && is_readable($fullPath)) {
            require($fullPath);

            return true;
        } else {
            throw new \Exception('Class ' . $class . ' not found.');
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

        if (isset(static::$scopeMap['Class'][$className])) {
            require(static::$scopeMap['Class'][$className]);

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

        if (isset(static::$scopeMap['Class'][$className])) {
            throw new \Exception('Trying register a class(' . $class . ') to scope while it already registered.');

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

        return static::$scopeMap['Class'][$className] = $file;
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

        if (!isset(static::$scopeMap['Class'][$className])) {
            throw new \Exception('Trying unregister a class(' . $class . '), but the class was not found.');

            return false;
        }

        unset(static::$scopeMap['Class'][$className]);

        return true;
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
                $map['Ref']['P'] = $path;

                return true;
            } else {
                throw new \Exception('Trying register a namespace(' . $nsPrefix . ') while it already registered.');
            }

        } else {
            throw new \Exception('Path ' . $path . ' for namespace ' . $nsPrefix . ' not exist.');
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
            static::$cfg['NSSpliter'],
            trim(
                str_replace(
                    array(
                        '/',
                        '\\'
                    ),
                    static::$cfg['NSSpliter'],
                    $namespace
                ),
                static::$cfg['NSSpliter']
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
        $mapParentRef = $mapCurrentRef = $mapActiveRef = & static::$nsMap;

        foreach ($splitedNS as $ns) {
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

            array_shift($splitedRemainNS);
        }

        return array(
            'Ref' => & $mapCurrentRef,
            'Parent' => & $mapParentRef,
            'Remain' => implode(static::$cfg['NSSpliter'], $splitedRemainNS),
        );
    }

    public static function registerPlugin($pluginName, $mainFile)
    {
        $pluginClassname = $pluginName . 'Plugin';
        $invokeResult = null;

        if (!static::registerScope($pluginClassname, $mainFile)) {
            throw new \Exception('Cannot register class scope for plugin class ' . $pluginClassname .'.');

            return false;
        }

        $plugRef = new \ReflectionClass($pluginClassname);

        if (!$plugRef->implementsInterface('\Facula\Base\Implement\Plugin')) {
            throw new \Exception('Plugin have to implement interface \\Facula\\Base\\Implement\\Plugin');

            return false;
        }

        if (!is_array($invokeResult = $plugRef->getMethod('register')->invoke(null))) {
            throw new \Exception('Registering plugin ' . $pluginClassname . ', but registrant returns invalid result.');

            return false;
        }

        foreach ($invokeResult as $hookName => $binded) {
            if (isset(static::$hookMap[$hookName][$pluginClassname])) {
                throw new \Exception(
                    'Registering plugin '
                    . $pluginClassname
                    . ' for '
                    . $hookName
                    . ', But seems it already registered'
                );

                return false;
            }

            if (is_callable($binded)) {
                static::$hookMap[$hookName][$pluginClassname] = $binded;
            } else {
                throw new \Exception(
                    'Registering plugin '
                    . $pluginClassname
                    . ' for hook '
                    . $hookName
                    . '. But seems the callback function is not callable.'
                );

                return false;
            }

        }

        return true;
    }

    /**
     * The entity of initialization
     *
     * @param array $cfg Configuration
     *
     * @return void
     */
    private function __construct(array &$cfg)
    {

        // Following process will only be call when the framework boot from cold

        // First, import all the settings in to Facula core
        if ($this->importConfigs($cfg)) {
            // Now we have settings, the first step: register namespace
            $this->registerAllNamespaces();

            // Second, start components pickup
            $this->pickComponents();

            // After all, send inited signal
            $this->finishingWarmup();
        }
    }

    protected function finishingWarmup()
    {
        // Takeover static::$nsMap, static::$scopeMap and static::$hookMap;
        $this->namespaces = static::$nsMap;
        $this->scope = static::$scopeMap;
        $this->hooks = static::$hookMap;

        // Set all old container to empty
        static::$nsMap = static::$scopeMap = static::$hookMap = null;

        //Mapping the static map to dynamic instance
        static::$nsMap = & $this->namespaces;
        static::$scopeMap = & $this->scope;
        static::$hookMap = & $this->hooks;

        return true;
    }

    protected function importConfigs(array &$cfg)
    {
        foreach ($cfg as $key => $value) {
            $this->setting[$key] = $value;
        }

        return true;
    }

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

    protected function pickComponents()
    {
        $components = $modules = array();

        if (isset($this->setting['Paths']) && is_array($this->setting['Paths'])) {

            foreach ($this->setting['Paths'] as $path) {
                $scanner = new Base\File\ModuleScanner($path);
                $modules = array_merge($modules, $scanner->scan());
            }

            foreach ($modules as $module) {
                switch ($module['Prefix']) {
                    case 'include':
                        $this->components['Inc'][] = $module['Path'];
                        break;

                    case 'routine':
                        $this->components['Rot'][] = $module['Path'];
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
            unset($this->setting['Paths']);
        }
    }
}
