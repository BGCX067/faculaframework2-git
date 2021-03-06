<?php

/**
 * Object Core Prototype
 *
 * Facula Framework 2015 (C) Rain Lee
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
 * @copyright  2015 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Base\Prototype\Core;

use Facula\Base\Error\Core\Object as Error;
use Facula\Base\Prototype\Core as Factory;
use Facula\Base\Implement\Core\Object as Implement;
use Facula\Base\Tool\File\PathParser;
use Facula\Framework;

/**
 * Prototype class for Object for make core remaking more easy
 */
abstract class Object extends Factory implements Implement
{
    /** Declare maintainer information */
    public static $plate = array(
        'Author' => 'Rain Lee',
        'Reviser' => '',
        'Updated' => '2013',
        'Contact' => 'raincious@gmail.com',
        'Version' => __FACULAVERSION__,
    );

    /** Default configuration */
    protected static $config = array(
        'CacheSafeCode' => array(
            '<?php if (!defined(\'IN_FACULA\')) {exit(\'Access Denied\');} ',
            ' ?>',
        ),
    );

    /** Instance configuration for caching */
    protected $configs = array();

    /** A tag to not allow re-warming */
    protected $rewarmingMutex = false;

    /**
     * Constructor
     *
     * @param array $cfg Array of core configuration
     * @param array $common Array of common configuration
     * @param \Facula\Framework $facula The framework itself
     *
     * @return void
     */
    public function __construct(&$cfg, $common)
    {
        $paths = array();

        $this->configs = array(
            'OCRoot' => isset($cfg['ObjectCacheRoot'])
                        && is_dir($cfg['ObjectCacheRoot'])
                        ? PathParser::get($cfg['ObjectCacheRoot']) : '',

            'OCExpire' => isset($cfg['ObjectCacheExpire'])
                        && $cfg['ObjectCacheExpire']
                        ? (int)($cfg['ObjectCacheExpire']) : 0,

            'CacheTime' => $common['BootVersion']
        );
    }

    /**
     * Warm up initializer
     *
     * @return bool Return true when initialization complete, false otherwise
     */
    public function inited()
    {
        if ($this->rewarmingMutex) {
            new Error('REWARMING_NOTALLOWED');
        }

        $this->rewarmingMutex = true;

        return true;
    }

    /**
     * Load a serialized object from file
     *
     * @param string $objectName Class name
     * @param string $type Type of the instance
     * @param string $uniqueID Unique ID to make sure the class is unique
     *
     * @return mixed Return the instance if got the object, or false when fail
     */
    protected function loadObjectFromCache($objectName, $type = '', $uniqueID = '')
    {
        $instance = null;
        $obj = array();
        $expire = 0;

        if (!$this->configs['OCRoot']) {
            return false;
        }

        if ($this->configs['OCExpire']) {
            $expire = FACULA_TIME - $this->configs['OCExpire'];
        }

        if ($this->configs['CacheTime'] > $expire) {
            $expire = $this->configs['CacheTime'];
        }

        $file = $this->configs['OCRoot']
                . DIRECTORY_SEPARATOR
                . 'cachedObject.'
                . ($type ? $type : 'general')
                . '#'
                . str_replace(
                    NAMESPACE_SEPARATER,
                    '%',
                    $objectName
                )
                . '#'
                . ($uniqueID ? $uniqueID : 'common')
                . '.php';

        return $this->loadObjectCache($file, $expire);
    }

    /**
     * Serialize a instance and save it into a file
     *
     * @param string $objectName Class name
     * @param object $instance Object instance
     * @param string $type Type of the instance
     * @param string $uniqueID Unique ID to make sure the class is unique
     *
     * @return mixed Return true when object saved, false otherwise
     */
    protected function saveObjectToCache($objectName, $instance, $type = '', $uniqueID = '')
    {
        if (!$this->configs['OCRoot']) {
            return false;
        }

        $instance->cachedObjectFilePath = $this->configs['OCRoot']
                                        . DIRECTORY_SEPARATOR
                                        . 'cachedObject.'
                                        . ($type ? $type : 'general')
                                        . '#'
                                        . str_replace(
                                            array('\\', '/'),
                                            '%',
                                            $objectName
                                        )
                                        . '#'
                                        . ($uniqueID ? $uniqueID : 'common')
                                        . '.php';

        $instance->cachedObjectSaveTime = FACULA_TIME;

        return $this->saveObjectCache($instance);
    }

    /**
     * Automatically get a instance from cache, then initialize and cache it
     *
     * @param object $object Object instance
     * @param array $args Arguments of calling
     * @param bool $cache Load the cached instance, and cache it after initialize.
     *
     * @return mixed Return true when object saved, false otherwise
     */
    public function getInstance($object, array $args = array(), $cache = false)
    {
        $newInstance = null;

        if (!class_exists($object, true)) {
            new Error(
                'OBJECT_NOTFOUND',
                array(
                    $object
                ),
                'ERROR'
            );

            return false;
        }

        if ($cache && ($newInstance = $this->loadObjectFromCache($object))) {
            // Call init after instance has been created to pre init it
            if (method_exists($newInstance, 'init')) {
                if (!$newInstance->init()) {
                    new Error(
                        'OBJECT_CREATE_FAILED',
                        array(
                            $object
                        ),
                        'ERROR'
                    );

                    return false;
                }
            }

            if (method_exists($newInstance, 'inited')) {
                $newInstance->inited();
            }
        } else {
            switch (count($args)) {
                case 0:
                    $newInstance = new $object();
                    break;

                case 1:
                    $newInstance = new $object(
                        $args[0]
                    );
                    break;

                case 2:
                    $newInstance = new $object(
                        $args[0],
                        $args[1]
                    );
                    break;

                case 3:
                    $newInstance = new $object(
                        $args[0],
                        $args[1],
                        $args[2]
                    );
                    break;

                case 4:
                    $newInstance = new $object(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3]
                    );
                    break;

                case 5:
                    $newInstance = new $object(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3],
                        $args[4]
                    );
                    break;

                case 6:
                    $newInstance = new $object(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3],
                        $args[4],
                        $args[5]
                    );
                    break;

                case 7:
                    $newInstance = new $object(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3],
                        $args[4],
                        $args[5],
                        $args[6]
                    );
                    break;

                case 8:
                    $newInstance = new $object(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3],
                        $args[4],
                        $args[5],
                        $args[6],
                        $args[7]
                    );
                    break;

                case 9:
                    $newInstance = new $object(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3],
                        $args[4],
                        $args[5],
                        $args[6],
                        $args[7],
                        $args[8]
                    );
                    break;

                case 10:
                    $newInstance = new $object(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3],
                        $args[4],
                        $args[5],
                        $args[6],
                        $args[7],
                        $args[8],
                        $args[9]
                    );
                    break;

                default:
                    new Error(
                        'OBJECT_MAXPARAM_EXCEEDED',
                        array(
                            $object
                        ),
                        'ERROR'
                    );
                    break;
            }

            // Save first
            if ($cache) {
                $this->saveObjectToCache(
                    $object,
                    $newInstance
                );
            }

            // Call init after instance has been created to pre init it
            if (method_exists($newInstance, 'init')) {
                if (!$newInstance->init()) {
                    new Error(
                        'OBJECT_INIT_FAILED',
                        array(
                            $object
                        ),
                        'ERROR'
                    );

                    return false;
                }
            }

            // Then call inited to notify object we already done init
            if (method_exists($newInstance, 'inited')) {
                $newInstance->inited();
            }
        }

        return $newInstance;
    }

    /**
     * Dynamically call a function
     *
     * @param object $function The function
     * @param array $args Arguments of calling
     *
     * @return mixed Return the result of called class when succeed, false otherwise
     */
    public function callFunction($function, array $args = array())
    {
        if (is_callable($function)) {
            switch (count($args)) {
                case 0:
                    return $function();
                    break;

                case 1:
                    return $function(
                        $args[0]
                    );
                    break;

                case 2:
                    return $function(
                        $args[0],
                        $args[1]
                    );
                    break;

                case 3:
                    return $function(
                        $args[0],
                        $args[1],
                        $args[2]
                    );
                    break;

                case 4:
                    return $function(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3]
                    );
                    break;

                case 5:
                    return $function(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3],
                        $args[4]
                    );
                    break;

                case 6:
                    return $function(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3],
                        $args[4],
                        $args[5]
                    );
                    break;

                case 7:
                    return $function(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3],
                        $args[4],
                        $args[5],
                        $args[6]
                    );
                    break;

                case 8:
                    return $function(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3],
                        $args[4],
                        $args[5],
                        $args[6],
                        $args[7]
                    );
                    break;

                case 9:
                    return $function(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3],
                        $args[4],
                        $args[5],
                        $args[6],
                        $args[7],
                        $args[8]
                    );
                    break;

                case 10:
                    return $function(
                        $args[0],
                        $args[1],
                        $args[2],
                        $args[3],
                        $args[4],
                        $args[5],
                        $args[6],
                        $args[7],
                        $args[8],
                        $args[9]
                    );
                    break;

                default:
                    return call_user_func_array(
                        $function,
                        $args
                    );
                    break;
            }
        }

        return false;
    }

    /**
     * Automatically initialize a class and execute a method of it
     *
     * @param object $app Calling string with ClassName::MethodName format
     * @param array $args Arguments of calling
     * @param bool $cache Load the cached instance, and cache it after initialize.
     *
     * @return mixed Return the result of called method when success, false otherwise
     */
    public function run($app, array $args = array(), $cache = false)
    {
        $handler = $hookResult = $callResult = null;
        $errors = array();
        $appParam = explode('::', str_replace(array('::', '->'), '::', $app), 2);

        if ($handler = $this->getInstance($appParam[0], $args, $cache)) {
            if (isset($appParam[1])
                && method_exists($handler, $appParam[1])) {
                $hookResult = Framework::summonHook(
                    'call_' . $appParam[0] . '::' . $appParam[1] . '_before',
                    $args,
                    $errors
                );

                $callResult = $this->callFunction(
                    array(
                        $handler,
                        $appParam[1]
                    ),
                    $args
                );

                Framework::summonHook(
                    'call_' . $appParam[0] . '::' . $appParam[1] . '_after',
                    array(
                        'Call' => $callResult,
                        'Hook' => $hookResult,
                    ),
                    $errors
                );
            } elseif (method_exists($handler, 'run')) {
                $hookResult = Framework::summonHook(
                    'call_' . $appParam[0] . '_before',
                    $args,
                    $errors
                );

                $callResult = $this->callFunction(
                    array(
                        $handler,
                        'run'
                    ),
                    $args
                );

                Framework::summonHook(
                    'call_' . $appParam[0] . '_after',
                    array(
                        'Call' => $callResult,
                        'Hook' => $hookResult,
                    ),
                    $errors
                );
            }
        }

        return $handler;
    }

    /**
     * Load object instance from cache
     *
     * @param string $file Path to the cache file
     * @param integer $expire The time to expire
     *
     * @return mixed Return object instance when succeed, false otherwise
     */
    protected function loadObjectCache($file, $expire = 0)
    {
        if (!$instance = static::loadCacheFile($file)) {
            return false;
        }

        if ($expire > 0 && $instance->cachedObjectSaveTime <= $expire) {
            return false;
        }

        return $instance;
    }

    /**
     * Save object instance to cache
     *
     * @param instance $instance The object instance
     *
     * @return bool Return true when succeed, false otherwise
     */
    protected function saveObjectCache($instance)
    {
        return static::saveCacheFile($instance);
    }

    /**
     * Static Wrapper: Load object instance from cache file
     *
     * @param string $file The path to cache file
     *
     * @return mixed Return the instance when succeed, false otherwise
     */
    protected static function loadCacheFile($file)
    {
        $obj = null;

        if (!is_readable($file)) {
            return false;
        }

        require($file);

        if (is_null($obj)) {
            return false;
        }

        return unserialize($obj);
    }

    /**
     * Static Wrapper: Save object instance to cache file
     *
     * @param instance $instance The instance to save
     *
     * @return bool Return true when succeed, false otherwise
     */
    protected static function saveCacheFile($instance)
    {
        if (file_exists($instance->cachedObjectFilePath)) {
            unlink($instance->cachedObjectFilePath);
        }

        return file_put_contents(
            $instance->cachedObjectFilePath,
            static::$config['CacheSafeCode'][0]
            . '$obj = '
            . var_export(serialize($instance), true)
            . ';'
            . static::$config['CacheSafeCode'][1]
        );
    }
}
