<?php

/**
 * Remote Storage
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

/**
 * Remote Storage Operator
 */
class Factory extends \Facula\Base\Factory\Operator
{
    /** The operator instance that will be use to upload files */
    private static $handler = null;

    /** Default operators */
    protected static $operators = array(
        'ftp' => '\Facula\Unit\RemoteStorage\Operator\FTP',
    );

    /** Servers as name */
    private static $servers = array();

    /**
     * Setup the class for ready
     *
     * @param array $setting Setting in array
     *
     * @return bool Return true when setup success, false otherwise
     */
    public static function setup(array $setting)
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

    /**
     * Upload a file
     *
     * @param string $localFile Path to the file
     * @param string $error Error detail
     *
     * @return mixed Return path on the remote server when success, false otherwise
     */
    public static function upload($localFile, &$error = '')
    {
        $handler = null;
        $operatorName = '';
        $result = '';

        if (is_readable($localFile)) {
            // Create handler instance
            if (!self::$handler) {
                foreach (self::$servers as $server) {
                    if (isset($server['Type'][0])) {
                        $operatorName = static::getOperator($server['Type']);

                        if (class_exists($operatorName)) {
                            $handler = new $operatorName(
                                isset($server['Option']) ? $server['Option'] : array()
                            );

                            if ($handler instanceof OperatorImplement) {
                                if ($result = $handler->upload($localFile, $error)) {
                                    self::$handler = $handler;

                                    return $result;
                                } else {
                                    unset($handler);
                                }
                            } else {
                                \Facula\Framework::core('debug')->exception(
                                    'ERROR_REMOTESTORAGE_INTERFACE_INVALID',
                                    'remote storage',
                                    true
                                );
                            }
                        } else {
                            \Facula\Framework::core('debug')->exception(
                                'ERROR_REMOTESTORAGE_SERVER_TYPE_UNSUPPORTED',
                                'remote storage',
                                true
                            );
                        }
                    } else {
                        \Facula\Framework::core('debug')->exception(
                            'ERROR_REMOTESTORAGE_SERVER_NOTYPE',
                            'remote storage',
                            true
                        );
                    }
                }
            } else {
                return self::$handler->upload($localFile, $error);
            }
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_REMOTESTORAGE_FILE_UNREADABLE|' . $localFile,
                'remote storage',
                true
            );
        }

        return false;
    }
}
