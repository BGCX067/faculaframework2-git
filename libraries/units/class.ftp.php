<?php

/*****************************************************************************
    Facula Framework FTP Operator

    FaculaFramework 2013 (C) Rain Lee <raincious@gmail.com>

    @Copyright 2013 Rain Lee <raincious@gmail.com>
    @Author Rain Lee <raincious@gmail.com>
    @Package FaculaFramework
    @Version 2.0 prototype

    This file is part of Facula Framework.

    Facula Framework is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published
    by the Free Software Foundation, version 3.

    Facula Framework is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with Facula Framework. If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************/

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

class FTP
{
    private static $instance = null;
    private static $connection = null;

    private static $setting = array();

    private static $error = '';

    private static $currentServer = array();
    private static $currentPath = array();

    private $config = array();

    public static function setup($setting)
    {
        if (self::$setting = $setting) {
            return true;
        }

        return false;
    }

    public static function connect()
    {
        if (self::$instance) {
            return self::$instance;
        } else {
            self::$instance = new self();

            if (self::$connection) {
                return self::$instance;
            } else {
                Facula::core('debug')->exception('ERROR_FTP_NO_SERVER_AVAILABLE|' . self::$error, 'ftp', true);
            }
        }

        return false;
    }

    private function __construct()
    {
        $successed = false;
        $conn = null;
        $error = '';

        $_env = array(
            'CanUseSSL' => function_exists('ftp_ssl_connect'),
        );

        if (!isset(self::$setting['Servers']) || !count(self::$setting['Servers'])) {
            Facula::core('debug')->exception('ERROR_FTP_NO_SERVER', 'ftp', true);
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
        Facula::core('debug')->criticalSection(true);

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
                            if (ftp_login($conn, $server['Username'], isset($server['Password'][0]) ? $server['Password'] : '')) {
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
        } catch (Exception $e) {
            self::$error = $e->getMessage();
        }

        // Exit critical section
        Facula::core('debug')->criticalSection(false);

        if ($successed && (self::$connection = $conn) && (!isset(self::$currentServer['Path']) || $this->doEnterPath(self::$currentServer['Path']))) {
            return true;
        } else {
            ftp_close($conn);
        }

        return false;
    }

    private function doEnterPath($remotePath, &$enteredRemotePath = '')
    {
        $folders = explode('/', str_replace(array('/', '\\'), '/', $remotePath));

        $chdirFailed = false;
        $error = '';
        $validFolders = $skipedFolders = $remainFolders = array();

        if (self::$connection && count($folders)) {
            // $validFolders: floders without empty
            $validFolders = array_values(array_filter($folders));

            Facula::core('debug')->criticalSection(true);

            // If the path include / var as beginning, we need to refresh the refer path to the root
            if (isset($folders[0]) && !$folders[0]) {
                // current vs new path, find out relative path
                foreach ($validFolders as $key => $folder) {
                    if (isset(self::$currentPath[$key]) && ($folder == self::$currentPath[$key])) {
                        $skipedFolders[] = $folder;

                        unset($validFolders[$key]);
                    } else {
                        break;
                    }
                }

                $remainFolders = array_values($validFolders); // return in right index

                if (count(array_diff(self::$currentPath, $skipedFolders))) {
                    self::$currentPath = array();

                    if (!ftp_chdir(self::$connection, count($skipedFolders) ? '/' . implode('/', $skipedFolders) : '/')) {
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
                        if (!ftp_chdir(self::$connection, $folder) && (!ftp_mkdir(self::$connection, $folder) || !ftp_chdir(self::$connection, $folder))) {
                            $chdirFailed = true;
                            break;
                        } else {
                            self::$currentPath[] = $folder;
                        }
                    } catch (Exception $e) {
                        $error = $e->getMessage();
                    }
                }
            }

            Facula::core('debug')->criticalSection(false);

            if (!$chdirFailed) {
                $enteredRemotePath = '/' . implode('/', self::$currentPath);

                return true;
            }
        }

        return false;
    }

    public function __destruct()
    {
        if (self::$connection) {
            return ftp_close(self::$connection);
        }

        return true;
    }

    public function getCurrentServer()
    {
        if (self::$connection) {
            return &self::$currentServer;
        }

        return false;
    }

    public function upload($localFile, $remotePath, $remoteFileName)
    {
        $server = array();
        $path = $currentRemotePath = $resultPath = '';

        if ($server = $this->getCurrentServer()) {
            Facula::core('debug')->criticalSection(true);

            if (isset($server['Path']) && !$this->doEnterPath($server['Path'] . '/' . $remotePath, $currentRemotePath)) {
                Facula::core('debug')->exception('ERROR_FTP_CHANGEDIR_FAILED', 'ftp', true);
            }

            if (is_readable($localFile)) {
                if (ftp_put(self::$connection, $remoteFileName, $localFile, FTP_BINARY)) {
                    $resultPath = $currentRemotePath . '/' . $remoteFileName;
                }
            } else {
                Facula::core('debug')->exception('ERROR_FTP_FILE_UNREADABLE|' . $localFile, 'ftp', true);
            }

            Facula::core('debug')->criticalSection(false);
        }

        return $resultPath;
    }
}
