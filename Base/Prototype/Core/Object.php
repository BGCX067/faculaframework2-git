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

namespace Facula\Base\Prototype\Core;

abstract class Object extends \Facula\Base\Prototype\Core implements \Facula\Base\Implement\Core\Object
{
    public static $plate = array(
        'Author' => 'Rain Lee',
        'Reviser' => '',
        'Updated' => '2013',
        'Contact' => 'raincious@gmail.com',
        'Version' => __FACULAVERSION__,
    );

    protected static $config = array(
        'CacheSafeCode' => array(
            '<?php if (!defined(\'IN_FACULA\')) {exit(\'Access Denied\');} ',
            ' ?>',
        ),
    );

    protected $configs = array();

    protected $hooks = array();

    protected $instances = array();

    public function __construct(&$cfg, &$common, $facula)
    {
        $paths = array();

        $this->configs = array(
            'OCRoot' => isset($cfg['ObjectCacheRoot']) && is_dir($cfg['ObjectCacheRoot']) ? $cfg['ObjectCacheRoot'] : '',
            'OCExpire' => isset($cfg['ObjectCacheExpire']) && $cfg['ObjectCacheExpire'] ? (int)($cfg['ObjectCacheExpire']) : 604800,
            'CacheTime' => $common['BootVersion']
        );

        return true;
    }

    public function getInfo()
    {
        return array(
            'Files' => $this->configs['Paths'],
            'Hooks' => $this->hooks,
        );
    }

    public function inited()
    {
        return true;
    }

    protected function loadObjectFromCache($objectName, $type = '', $uniqueid = '')
    {
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

    protected function saveObjectToCache($objectName, $instance, $type = '', $uniqueid = '')
    {
        $objectInfo = array();

        if ($this->configs['OCRoot']) {
            $instance->cachedObjectFilePath = $this->configs['OCRoot'] . DIRECTORY_SEPARATOR . 'cachedObject.' . ($type ? $type : 'general') . '#' . str_replace(array('\\', '/'), '%', $objectName) . '#' . ($uniqueid ? $uniqueid : 'common') . '.php';
            $instance->cachedObjectSaveTime = FACULA_TIME;

            return file_put_contents($instance->cachedObjectFilePath, self::$config['CacheSafeCode'][0] . '$obj = ' . var_export(serialize($instance), true) . ';' . self::$config['CacheSafeCode'][1]);
        }

        return false;
    }

    public function getInstance($object, $args, $cache = false)
    {
        $newinstance = null;

        if (class_exists($object, true)) {
            if ($cache && ($newinstance = $this->loadObjectFromCache($object))) {
                // Call init after instance has been created to pre init it
                if (method_exists($newinstance, 'init')) {
                    if (!$newinstance->init()) {
                        \Facula\Framework::core('debug')->exception('ERROR_OBJECT_NEWINSTNACE_INIT_FAILED|' . $object, 'object', true);

                        return false;
                    }
                }

                if (method_exists($newinstance, 'inited')) {
                    $newinstance->inited();
                }

                return $newinstance;
            } else {
                switch (count($args)) {
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
                        \Facula\Framework::core('debug')->exception('ERROR_OBJECT_NEWINSTNACE_MAXPARAMEXCEEDED', 'object', true);
                        break;
                }

                // Save first
                if ($cache) {
                    $this->saveObjectToCache($object, $newinstance);
                }

                // Call init after instance has been created to pre init it
                if (method_exists($newinstance, 'init')) {
                    if (!$newinstance->init()) {
                        \Facula\Framework::core('debug')->exception('ERROR_OBJECT_NEWINSTNACE_INIT_FAILED|' . $object, 'object', true);

                        return false;
                    }
                }

                // Then call inited to notify object we already done init
                if (method_exists($newinstance, 'inited')) {
                    $newinstance->inited();
                }

                return $newinstance;
            }
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_OBJECT_NEWINSTNACE_OBJECTNOTFOUND|' . $object, 'object', true);
        }

        return false;
    }

    public function callFunction($function, $args = array())
    {
        if (is_callable($function)) {
            switch (count($args)) {
                case 0:
                    return $function ();
                    break;

                case 1:
                    return $function ($args[0]);
                    break;

                case 2:
                    return $function ($args[0], $args[1]);
                    break;

                case 3:
                    return $function ($args[0], $args[1], $args[2]);
                    break;

                case 4:
                    return $function ($args[0], $args[1], $args[2], $args[3]);
                    break;

                case 5:
                    return $function ($args[0], $args[1], $args[2], $args[3], $args[4]);
                    break;

                case 6:
                    return $function ($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
                    break;

                case 7:
                    return $function ($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
                    break;

                case 8:
                    return $function ($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
                    break;

                case 9:
                    return $function ($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8]);
                    break;

                case 10:
                    return $function ($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9]);
                    break;

                default:
                    return call_user_func_array($function, $args);
                    break;
            }
        }

        return false;
    }
    
    public function run($app, $args = array(), $cache = false)
    {
        $handler = $hookResult = $callResult = null;
        $errors = array();
        $appParam = explode('::', str_replace(array('::', '->'), '::', $app), 2);

        if ($handler = $this->getInstance($appParam[0], $args, $cache)) {
            if (isset($appParam[1]) && method_exists($handler, $appParam[1])) {
                $hookResult = \Facula\Framework::summonHook('call_' . $appParam[0] . '::' . $appParam[1] . '_before', $args, $errors);

                $callResult = $this->callFunction(array($handler, $appParam[1]), $args);

                \Facula\Framework::summonHook('call_' . $appParam[0] . '::' . $appParam[1] . '_after', array(
                    'Call' => $callResult,
                    'Hook' => $hookResult,
                ), $errors);
            } elseif (method_exists($handler, '_run')) {
                $hookResult = \Facula\Framework::summonHook('call_' . $appParam[0] . '_before', $args, $errors);

                $callResult = $this->callFunction(array($handler, '_run'), $args);

                \Facula\Framework::summonHook('call_' . $appParam[0] . '_after', array(
                    'Call' => $callResult,
                    'Hook' => $hookResult,
                ), $errors);
            }
        }

        return $handler;
    }
}
