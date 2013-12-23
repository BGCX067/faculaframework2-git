<?php

/**
 * FTP Operator
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

namespace Facula\Unit;

/*
    $setting Data Struct

    $setting = array(
        'PriorMethod' => 'Random|Order',
        'Servers' => array(
            array(
                'Host' => '127.0.0.1',
                'Port' => '21',
                'Timeout' => '1',
                'SSL' => false,
                'Username' => 'ftp',
                'Password' => 'password',
                'Path' => '/ftp/fileuploaded/',
            ),
            array(
                'Host' => '127.0.0.2',
                'Port' => '21',
                'Timeout' => '1',
                'SSL' => true,
                'Username' => 'ftp',
                'Password' => 'password',
                'Path' => '/ftp/uploadedfiles/',
            )
        )
    );
*/

/**
 * FTP Operator
 */
class FTP
{
    /** Instance of this singleton */
    private static $instance = null;

    /** Handler of the connection */
    private static $connection = null;

    /** Setting of this class */
    private static $setting = array();

    /** Information for currently connected server */
    private static $currentServer = array();

    /** Information for currently path this handler accessed */
    private static $currentPath = array();

    /**
     * Setup for this class
     *
     * @param array $setting Configuration of this class
     *
     * @return bool true when setup succeed, false when fail
     */
    public static function setup(array $setting)
    {
        if (self::$setting = $setting) {
            return true;
        }

        return false;
    }

    /**
     * Connect to a FTP server
     *
     * @return mixed A instance if this object when succeed, false when false
     */
    public static function connect()
    {
        if (self::$instance) {
            return self::$instance;
        } else {
            self::$instance = new self();

            if (self::$connection) {
                return self::$instance;
            } else {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_FTP_NO_SERVER_AVAILABLE|' . self::$error,
                    'ftp',
                    true
                );
            }
        }

        return false;
    }

    /**
     * Constructor. Build a link to FTP server.
     *
     * Notice that: The connection may silently fail, do check on the return value of
     * operator methods by your own to knowing that.
     *
     * @return bool true when succeed, false when fail
     */
    private function __construct()
    {
        $successed = false;
        $conn = null;
        $error = '';

        $_env = array(
            'CanUseSSL' => function_exists('ftp_ssl_connect'),
        );

        if (!isset(self::$setting['Servers']) || !count(self::$setting['Servers'])) {
            \Facula\Framework::core('debug')->exception(
                'ERROR_FTP_NO_SERVER',
                'ftp',
                true
            );

            return false;
        }

        if (isset(self::$setting['PriorMethod'])) {
            switch (self::$setting['PriorMethod']) {
                case 'Random':
                    shuffle(self::$setting['Servers']);
                    break;

                default:
                    break;
            }
        }

        // Enter critical section
        \Facula\Framework::core('debug')->criticalSection(true);

        try {
            foreach (self::$setting['Servers'] as $server) {
                $conn = null;

                if (isset($server['Host'][0])) {
                    if (isset($server['SSL']) && $server['SSL'] && $_env['CanUseSSL']) {
                        $conn = ftp_ssl_connect(
                            $server['Host'],
                            isset($server['Port']) ? $server['Port'] : 21,
                            isset($server['Timeout']) ? $server['Timeout'] : 30
                        );
                    } else {
                        $conn = ftp_connect(
                            $server['Host'],
                            isset($server['Port']) ? $server['Port'] : 21,
                            isset($server['Timeout']) ? $server['Timeout'] : 30
                        );
                    }

                    if ($conn) {
                        if (isset($server['Username'][0])) {
                            if (ftp_login(
                                $conn,
                                $server['Username'],
                                isset($server['Password'][0]) ? $server['Password'] : ''
                            )) {
                                self::$currentServer = $server;
                                break;
                            } else {
                                ftp_close($conn);
                                $conn = null;
                            }
                        } else {
                            self::$currentServer = $server;
                            break;
                        }
                    }
                }
            }

            if ($conn) {
                ftp_pasv($conn, false);

                $successed = true;
            }
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
        }

        // Exit critical section
        \Facula\Framework::core('debug')->criticalSection(false);

        if ($successed
            && (self::$connection = $conn)
            && (!isset(self::$currentServer['Path'])
            || $this->doEnterPath(self::$currentServer['Path']))) {
            return true;
        } else {
            ftp_close($conn);
        }

        return false;
    }

    /**
     * Destructor. Say good bye to the FTP server
     *
     * @return bool true when succeed, false when fail
     */
    public function __destruct()
    {
        if (self::$connection) {
            return ftp_close(self::$connection);
        }

        return true;
    }

    /**
     * Enter a path on FTP server
     *
     * @param string $remotePath Path on server to enter
     * @param string $enteredRemotePath A reference to get entered path
     *                                   (For half enter check and safely get entered path)
     *
     * @return bool true when succeed, false when fail
     */
    private function doEnterPath($remotePath, &$enteredRemotePath = '')
    {
        $folders = explode(
            '/',
            str_replace(array('/', '\\'), '/', $remotePath)
        );

        $chdirFailed = false;
        $error = '';
        $validFolders = $skipedFolders = $remainFolders = array();

        if (self::$connection && count($folders)) {
            // $validFolders: floders without empty
            $validFolders = array_values(array_filter($folders));

            \Facula\Framework::core('debug')->criticalSection(true);

            // If the path include / var as beginning,
            // we need to refresh the refer path to the root
            if (isset($folders[0]) && !$folders[0]) {
                // current vs new path, find out relative path
                foreach ($validFolders as $key => $folder) {
                    if (isset(self::$currentPath[$key])
                        && ($folder == self::$currentPath[$key])) {
                        $skipedFolders[] = $folder;

                        unset($validFolders[$key]);
                    } else {
                        break;
                    }
                }

                $remainFolders = array_values($validFolders); // return in right index

                if (count(array_diff(
                    self::$currentPath,
                    $skipedFolders
                ))) {
                    self::$currentPath = array();

                    if (!ftp_chdir(
                        self::$connection,
                        count($skipedFolders) ? '/' .
                        implode('/', $skipedFolders) : '/'
                    )) {
                        $chdirFailed = true;
                    } else {
                        self::$currentPath = $skipedFolders;
                    }
                }
            } else {
                $remainFolders = $validFolders;
            }

            if (!$chdirFailed) {
                foreach ($remainFolders as $folder) {
                    if (!$folder) {
                        continue;
                    }

                    try {
                        if (!ftp_chdir(self::$connection, $folder)
                            && (!ftp_mkdir(self::$connection, $folder)
                            || !ftp_chdir(self::$connection, $folder))) {
                            $chdirFailed = true;
                            break;
                        } else {
                            self::$currentPath[] = $folder;
                        }
                    } catch (\Exception $e) {
                        $error = $e->getMessage();
                    }
                }
            }

            \Facula\Framework::core('debug')->criticalSection(false);

            if (!$chdirFailed) {
                $enteredRemotePath = '/' . implode('/', self::$currentPath);

                return true;
            }
        }

        return false;
    }

    /**
     * Upload a file
     *
     * @param string $localFile Path to file that needs to be uploaded
     * @param string $remotePath Target path (not including file name) on remote server
     * @param string $remoteFileName File name
     *
     * @return string Return Path to the file on remote FTP server (/ftproot/blablabla/file1.txt)
     */
    public function upload($localFile, $remotePath, $remoteFileName)
    {
        $server = array();
        $path = $currentRemotePath = $resultPath = '';

        if ($server = $this->getCurrentServer()) {
            \Facula\Framework::core('debug')->criticalSection(true);

            if (isset($server['Path']) && !$this->doEnterPath(
                $server['Path'] . '/' . $remotePath,
                $currentRemotePath
            )) {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_FTP_CHANGEDIR_FAILED',
                    'ftp',
                    true
                );
            }

            if (is_readable($localFile)) {
                if (ftp_put(self::$connection, $remoteFileName, $localFile, FTP_BINARY)) {
                    $resultPath = $currentRemotePath . '/' . $remoteFileName;
                }
            } else {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_FTP_FILE_UNREADABLE|' . $localFile,
                    'ftp',
                    true
                );
            }

            \Facula\Framework::core('debug')->criticalSection(false);
        }

        return $resultPath;
    }
}
