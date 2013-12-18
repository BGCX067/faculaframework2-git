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

namespace Facula\Unit\RemoteStorage;

/*

Here's how to use

$setting = array {
    'SelectMethod' => 'Normal|Random',
    'Servers' => array(
        array(
            'Type' => 'ftp',
            'Option' => array(
                'Username' => 'test',
                'Password' => 'test',
                'Host' => '192.168.1.16',
                'Port' => 21,
                'Timeout' => 3,
                'SSL' => true,
                'Path' => '/ftp/test/',
                'Access' => 'http://files.localhost.local/',
            ),
        ),
        array(
            'Type' => 'skydrive',
            'Option' => array(
                'Username' => 'test',
                'Password' => 'test',
                'Host' => '192.168.1.16',
                'Port' => 21,
                'Timeout' => 3,
                'SSL' => true,
                'Path' => '/ftp/test/',
                'Access' => 'http://files.localhost.local/',
            ),
        )
    )
}

\Facula\Unit\RemoteStorage::setup($setting);

\Facula\Unit\RemoteStorage::upload($file);

*/

class Factory
{
    private static $handler = null;

    private static $servers = array();

    public static function setup($setting)
    {
        if (isset($setting['Servers'])) {
            if (isset($setting['SelectMethod'])) {
                switch ($setting['SelectMethod']) {
                    case 'Random':
                        shuffle($setting['Servers']);
                        break;

                    default:
                        break;
                }
            }

            self::$servers = $setting['Servers'];

            return true;
        }

        return false;
    }

    public static function upload($localFile, &$error = '')
    {
        $handler = null;
        $handlerName = $adapterName = '';
        $result = '';

        if (is_readable($localFile)) {
            // Create handler instance
            if (!self::$handler) {
                foreach (self::$servers as $server) {
                    if (isset($server['Type'][0])) {
                        $adapterName = $server['Type'];
                        $adapterName[0] = strtoupper($adapterName[0]);

                        $handlerName = __NAMESPACE__ . NAMESPACE_SEPARATER . 'Handler' . $adapterName;

                        if (class_exists($handlerName)) {
                            $handler = new $handlerName(isset($server['Option']) ? $server['Option'] : array());

                            if ($handler instanceof RemoteStorageInterface) {
                                if ($result = $handler->upload($localFile, $error)) {
                                    self::$handler = $handler;

                                    return $result;
                                } else {
                                    unset($handler);
                                }
                            } else {
                                \Facula\Framework::core('debug')->exception('ERROR_REMOTESTORAGE_INTERFACE_INVALID', 'remote storage', true);
                            }
                        } else {
                            \Facula\Framework::core('debug')->exception('ERROR_REMOTESTORAGE_SERVER_TYPE_UNSUPPORTED', 'remote storage', true);
                        }
                    } else {
                        \Facula\Framework::core('debug')->exception('ERROR_REMOTESTORAGE_SERVER_NOTYPE', 'remote storage', true);
                    }
                }
            } else {
                return self::$handler->upload($localFile, $error);
            }
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_REMOTESTORAGE_FILE_UNREADABLE|' . $localFile, 'remote storage', true);
        }

        return false;
    }
}
