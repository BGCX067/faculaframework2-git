<?php
/**
 * Facula Framework Main File
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
        
        // The char to split namespaces
        'NSSpliter' => DIRECTORY_SEPARATOR,

        // Framework namespace
        'FWNS' => array(
            '\Facula\Cores' => 'Cores',
            '\Facula\Bases' => 'Bases',
            '\Facula\Units' => 'Units',
        ),

        // Cores that needed for every boot time
        'RequiredCores' => array(
            'Debug', 'Object', 'Request', 'Response'
        ),
    );

    /** Config container */
    static protected $nsMap = array();

    /** Data container for Facula framework instance */
    protected $setting = array(

    );

    protected $component = array();

    /**
     * Initialization exposure
     *
     * @param array $cfg Configuration array
     *
     * @return mixed Return a singleton Facula object when successed, false otherwise.
     */
    public static function init(array &$cfg)
    {
        spl_autoload_register(function ($class) {
            return static::loadClass($class);
        });

        if (static::$instance) {
            return static::$instance;
        }

        return (static::$instance = new static($cfg));
    }

    public static function loadClass($className)
    {
        // Check if this is a namespace calling
        if (strpos($className, static::$cfg['NSSpliter'])) {
            return static::loadNamespace($className);
        } elseif (false) {
            // Nothing here
        }

        return false;
    }

    public static function loadNamespace($namespace)
    {
        $splitedNamespace = self::splitNamespace($namespace, true);

        // Pop the last element as it will be the class name it self
        $className = array_pop($splitedNamespace);

        $map = self::locateNamespace($splitedNamespace, true);
        print_r($namespace);
        print_r($map);
        if ($map['Ref']['P'] && is_readable($map['Ref']['P'] . DIRECTORY_SEPARATOR . $map['Ref']['Remain'] . DIRECTORY_SEPARATOR . $className . '.php')) {
            require($map['Ref']['P'] . DIRECTORY_SEPARATOR . $map['Ref']['Remain'] . DIRECTORY_SEPARATOR . $className . '.php');
        } else {
            throw new \Exception('Class ' . $namespace . ' not found.');
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
                $map['Ref']['P'] = $path;

                return true;
            } else {
                throw new \Exception('Trying register a namespace(' . $nsPrefix . ') while it already registered');
            }

        } else {
            throw new \Exception('Path ' . $path . ' for namespace ' . $nsPrefix . ' not exist');
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
                throw new \Exception('Trying unregister a namespace while it not existed');
                
                return false;
            }

            return true;
        } else {
            throw new \Exception('Namespace ' . $nsPrefix . ' not registered');
        }

        return false;
    }

    /**
     * Split namespace in to array
     *
     * @param string $namespace The namespace
     *
     * @return array
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
     * @param string $namespace The namespace
     * @param bool $create true will create the array when it's not exist, false will return current matched
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

        }
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
        foreach (static::$cfg['FWNS'] as $namespace => $relativePath) {
            static::registerNamespace($namespace, FACULA_ROOT . DIRECTORY_SEPARATOR . $relativePath);
        }

        if (isset($this->setting['Namespaces']) && is_array($this->setting['Namespaces'])) {
            foreach ($this->setting['Namespaces'] as $namespace => $path) {
                static::registerNamespace($namespace, $path);
            }
        }
        
        return true;
    }

    protected function scanComponents()
    {
        if (isset($this->setting['Paths']) && is_array($this->setting['Paths'])) {
            foreach ($this->setting['Paths'] as $path) {
                # code...
            }

            // Unset the setting and release memory
            unset($this->setting['Paths']);
        }
    }
}
