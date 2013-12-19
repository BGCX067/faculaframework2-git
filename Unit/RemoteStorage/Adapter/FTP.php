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
 * @package    Facula
 * @version    2.2 prototype
 * @see        https://github.com/raincious/facula FYI
 */

namespace Facula\Unit\RemoteStorage\Adapter;

class FTP implements \Facula\Unit\RemoteStorage\AdapterImplement
{
    private $connection = null;

    private $setting = array(
        'Username' => '',
        'Password' => '',
        'Host' => '',
        'Port' => 0,
        'Timeout' => 0,
        'SSL' => false,
        'Path' => '/',
        'Access' => '',
    );

    private $currentPath = array();

    public function __construct($setting)
    {
        $this->setting = array(
            'Username' => isset($setting['Username'][0]) ? $setting['Username'] : 'anonymous',
            'Password' => isset($setting['Password'][0]) ? $setting['Password'] : '',
            'Host' => isset($setting['Host'][0]) ? $setting['Host'] : 'localhost',
            'Port' => isset($setting['Port']) ? $setting['Port'] : 21,
            'Timeout' => isset($setting['Timeout']) ? $setting['Timeout'] : 3,
            'SSL' => isset($setting['SSL']) && $setting['SSL'] && function_exists('ftp_ssl_connect') ? true : false,
            'Path' => isset($setting['Path'][0]) ? $setting['Path'] : '/',
            'Access' => isset($setting['Access'][0]) ? $setting['Access'] . '/' : '',
        );

        return false;
    }

    public function __destruct()
    {
        $result = false;

        if ($this->connection) {
            \Facula\Framework::core('debug')->criticalSection(true);

            if (ftp_close($this->connection)) {
                $result = true;
            }

            \Facula\Framework::core('debug')->criticalSection(false);

            return $result;
        }

        return true;
    }

    public function upload($localFile, &$error = '')
    {
        $currentRemotePath = $remoteFileName = $resultPath = $fileExt = '';
        $failed = false;

        if ($this->connection || $this->connect()) {
            \Facula\Framework::core('debug')->criticalSection(true);

            if ($this->setting['Path'] && !$this->chDir($this->setting['Path'] . $this->generatePath(), $currentRemotePath)) {
                \Facula\Framework::core('debug')->exception('ERROR_FTP_CHANGEDIR_FAILED', 'ftp', false);
                $failed = true;
            } else {
                $fileExt = strtolower(pathinfo($localFile, PATHINFO_EXTENSION));

                if (!$remoteFileName = md5_file($localFile)) {
                    $remoteFileName = rand(0, 9999) . ($fileExt ? '.' . $fileExt : '');
                } else {
                    $remoteFileName .= ($fileExt ? '.' . $fileExt : '');
                }

                if (ftp_put($this->connection, $remoteFileName, $localFile, FTP_BINARY)) {
                    $resultPath =  $currentRemotePath . '/' . $remoteFileName;
                } else {
                    $error = 'ERROR_REMOTESTORAGE_UPLOAD_FAILED';
                }
            }

            \Facula\Framework::core('debug')->criticalSection(false);

            return $failed ? false : ($this->setting['Access'] . substr($resultPath, strlen($this->setting['Path'])));
        } else {
            $error = 'ERROR_REMOTESTORAGE_CONNECTION_FAILED';
        }

        return false;
    }

    private function connect()
    {
        $conn = null;

        \Facula\Framework::core('debug')->criticalSection(true);

        if ($this->setting['SSL']) {
            $conn = ftp_ssl_connect(
                $this->setting['Host'],
                isset($this->setting['Port']) ? $this->setting['Port'] : 21,
                isset($this->setting['Timeout']) ? $this->setting['Timeout'] : 30
            );
        } else {
            $conn = ftp_connect(
                $this->setting['Host'],
                isset($this->setting['Port']) ? $this->setting['Port'] : 21,
                isset($this->setting['Timeout']) ? $this->setting['Timeout'] : 30
            );
        }

        if ($conn) {
            if (isset($this->setting['Username'][0])) {
                if (!ftp_login($conn, $this->setting['Username'], isset($this->setting['Password'][0]) ? $this->setting['Password'] : '')) {
                    ftp_close($conn);
                    $conn = null;
                }
            }

            ftp_pasv($conn, false);
        }

        \Facula\Framework::core('debug')->criticalSection(false);

        if ($conn) {
            $this->connection = $conn;

            return true;
        }

        return false;
    }

    private function chDir($remotePath, &$enteredRemotePath = '')
    {
        $folders = explode('/', str_replace(array('/', '\\'), '/', $remotePath));

        $chdirFailed = false;
        $error = '';
        $validFolders = $skipedFolders = $remainFolders = array();

        if ($this->connection && count($folders)) {
            // $validFolders: floders without empty
            $validFolders = array_values(array_filter($folders));

            \Facula\Framework::core('debug')->criticalSection(true);

            // If the path include / var as beginning, we need to refresh the refer path to the root
            if (isset($folders[0]) && !$folders[0]) {
                // current vs new path, find out relative path
                foreach ($validFolders as $key => $folder) {
                    if (isset($this->currentPath[$key]) && ($folder == $this->currentPath[$key])) {
                        $skipedFolders[] = $folder;

                        unset($validFolders[$key]);
                    } else {
                        break;
                    }
                }

                $remainFolders = array_values($validFolders); // return in right index

                if (count(array_diff($this->currentPath, $skipedFolders))) {
                    $this->currentPath = array();

                    if (!ftp_chdir($this->connection, count($skipedFolders) ? '/' . implode('/', $skipedFolders) : '/')) {
                        $chdirFailed = true;
                    } else {
                        $this->currentPath = $skipedFolders;
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
                        if (!ftp_chdir($this->connection, $folder) && (!ftp_mkdir($this->connection, $folder) || !ftp_chdir($this->connection, $folder))) {
                            $chdirFailed = true;
                            break;
                        } else {
                            $this->currentPath[] = $folder;
                        }
                    } catch (Exception $e) {
                        $error = $e->getMessage();
                    }
                }
            }

            \Facula\Framework::core('debug')->criticalSection(false);

            if (!$chdirFailed) {
                $enteredRemotePath = '/' . implode('/', $this->currentPath);

                return true;
            }
        }

        return false;
    }

    private function generatePath()
    {
        return '/' . date('Y') . '/' . abs((int)(crc32(date('m/w')) / 10240));
    }
}
