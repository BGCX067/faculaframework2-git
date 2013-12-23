<?php

/**
 * Object Core Prototype
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

namespace Facula\Base\Prototype\Core;

/**
 * Prototype class for Object for make core remaking more easy
 */
abstract class Object extends \Facula\Base\Prototype\Core implements \Facula\Base\Implement\Core\Object
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
                        ? \Facula\Base\Tool\File\PathParser::get($cfg['ObjectCacheRoot']) : '',

            'OCExpire' => isset($cfg['ObjectCacheExpire'])
                        && $cfg['ObjectCacheExpire']
                        ? (int)($cfg['ObjectCacheExpire']) : 604800,

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

        if ($this->configs['OCRoot']) {
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

            if (is_readable($file) && filemtime($file) >= $this->configs['CacheTime']) {
                require($file);

                if ($obj && $instance = unserialize($obj)) {

                    if ($this->configs['OCExpire']
                        && $instance->cachedObjectSaveTime < $this->configs['OCExpire'] - FACULA_TIME) {
                        unlink($file);
                    }

                    return $instance;
                }
            }
        }

        return false;
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
        $objectInfo = array();

        if ($this->configs['OCRoot']) {
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

            return file_put_contents(
                $instance->cachedObjectFilePath,
                static::$config['CacheSafeCode'][0]
                . '$obj = '
                . var_export(serialize($instance), true)
                . ';'
                . static::$config['CacheSafeCode'][1]
            );
        }

        return false;
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

        if (class_exists($object, true)) {
            if ($cache && ($newInstance = $this->loadObjectFromCache($object))) {
                // Call init after instance has been created to pre init it
                if (method_exists($newInstance, 'init')) {
                    if (!$newInstance->init()) {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_OBJECT_NEWINSTNACE_INIT_FAILED|' . $object,
                            'object',
                            true
                        );

                        return false;
                    }
                }

                if (method_exists($newInstance, 'inited')) {
                    $newInstance->inited();
                }

                return $newInstance;
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
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_OBJECT_NEWINSTNACE_MAXPARAMEXCEEDED',
                            'object',
                            true
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
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_OBJECT_NEWINSTNACE_INIT_FAILED|' . $object,
                            'object',
                            true
                        );

                        return false;
                    }
                }

                // Then call inited to notify object we already done init
                if (method_exists($newInstance, 'inited')) {
                    $newInstance->inited();
                }

                return $newInstance;
            }
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_OBJECT_NEWINSTNACE_OBJECTNOTFOUND|' . $object,
                'object',
                true
            );
        }

        return false;
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
                $hookResult = \Facula\Framework::summonHook(
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

                \Facula\Framework::summonHook(
                    'call_' . $appParam[0] . '::' . $appParam[1] . '_after',
                    array(
                        'Call' => $callResult,
                        'Hook' => $hookResult,
                    ),
                    $errors
                );
            } elseif (method_exists($handler, 'run')) {
                $hookResult = \Facula\Framework::summonHook(
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

                \Facula\Framework::summonHook(
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
}
