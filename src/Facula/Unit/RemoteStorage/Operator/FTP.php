<?php

/**
 * FTP Operator for RemoteStorage
 *
 * Facula Framework 2014 (C) Rain Lee
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
 * @copyright  2014 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Unit\RemoteStorage\Operator;

use Facula\Unit\RemoteStorage\Exception\Operator as Exception;
use Facula\Unit\RemoteStorage\OperatorImplement;
use Facula\Unit\RemoteStorage\Common;
use Facula\Framework;

/**
 * Remote Storage FTP Operator
 */
class FTP extends Common implements OperatorImplement
{
    /** For activated FTP connection handler */
    private $connection = null;

    /** Configure for this connection */
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

    /** Current entered path */
    private $currentPath = array();

    /**
     * Constructor of the adapter
     *
     * @param array $setting Setting array
     *
     * @return void
     */
    public function __construct(array $setting)
    {
        $this->setting = array(
            'Username' => isset($setting['Username'][0])
                ? $setting['Username'] : 'anonymous',

            'Password' => isset($setting['Password'][0])
                ? $setting['Password'] : '',

            'Host' => isset($setting['Host'][0])
                ? $setting['Host'] : 'localhost',

            'Port' => isset($setting['Port'])
                ? $setting['Port'] : 21,

            'Timeout' => isset($setting['Timeout'])
                ? $setting['Timeout'] : 3,

            'SSL' => isset($setting['SSL'])
                && $setting['SSL']
                && function_exists('ftp_ssl_connect')
                ? true : false,

            // We already / by default, and // will cause problem
            'Path' => isset($setting['Path'][0]) && $setting['Path'] !== '/'
                ? $setting['Path'] . '/' : '/',

            'Access' => isset($setting['Access'][0])
                ? $setting['Access'] . '/' : '',
        );
    }

    /**
     * Destructor of the adapter
     *
     * @return void
     */
    public function __destruct()
    {
        $result = false;

        if ($this->connection) {
            Framework::core('debug')->criticalSection(true);

            if (ftp_close($this->connection)) {
                $result = true;
            }

            Framework::core('debug')->criticalSection(false);

            return $result;
        }

        return true;
    }

    /**
     * Upload the file
     *
     * @param string $localFile Path to the local file
     * @param string $remoteFileName Name of the file on remote server
     * @param string $error A reference to get error detail
     *
     * @return void
     */
    public function upload($localFile, $remoteFileName = '', &$error = '')
    {
        $currentRemotePath = $resultPath = $fileExt = $cdError = '';
        $success = false;

        if ($this->connection || $this->connect()) {
            Framework::core('debug')->criticalSection(true);

            if (!$remoteFileName) {
                $fileExt = strtolower(pathinfo($localFile, PATHINFO_EXTENSION));

                if (!$remoteFileName = md5_file($localFile)) {
                    $remoteFileName = mt_rand(0, 9999) . ($fileExt ? '.' . $fileExt : '');
                }
            }

            if (!$this->chDir(
                $this->setting['Path'] . $this->generatePath($remoteFileName),
                $currentRemotePath,
                $cdError
            )) {
                throw new Exception\EnterDirFailed($this->setting['Path'], $cdError);
            } else {
                if (ftp_put(
                    $this->connection,
                    $remoteFileName,
                    $localFile,
                    FTP_BINARY
                )) {
                    $resultPath =  $currentRemotePath
                        . '/'
                        . $remoteFileName;

                    $success = true;
                } else {
                    $error = 'ERROR_REMOTESTORAGE_UPLOAD_FAILED';
                }
            }

            Framework::core('debug')->criticalSection(false);

            return !$success ? false : ($this->setting['Access'] . substr(
                $resultPath,
                strlen($this->setting['Path'])
            ));
        } else {
            $error = 'ERROR_REMOTESTORAGE_CONNECTION_FAILED';
        }

        return false;
    }

    /**
     * Perform the server connect
     *
     * @return bool Return true when connect, or false when fail
     */
    private function connect()
    {
        $conn = null;

        Framework::core('debug')->criticalSection(true);

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
                if (!ftp_login(
                    $conn,
                    $this->setting['Username'],
                    isset($this->setting['Password'][0]) ? $this->setting['Password'] : ''
                )) {
                    ftp_close($conn);
                    $conn = null;
                } else {
                    ftp_pasv($conn, false);
                }
            }
        }

        Framework::core('debug')->criticalSection(false);

        if ($conn) {
            $this->connection = $conn;

            return true;
        }

        return false;
    }

    /**
     * Change the current folder for the connection hander
     *
     * @param string $remotePath Remote path that will be enter into
     * @param string $enteredRemotePath Entered path
     * @param string $error Reference to error feedback
     *
     * @return bool When path entered, return true, or return false on fail
     */
    private function chDir($remotePath, &$enteredRemotePath = '', &$error = '')
    {
        $folders = explode('/', str_replace(array('/', '\\'), '/', $remotePath));

        $chdirFailed = false;
        $validFolders = $skipedFolders = $remainFolders = array();

        if ($this->connection && !empty($folders)) {
            // $validFolders: folders without empty name like the one before the first /
            $validFolders = array_values(array_filter($folders));

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

                    if (!ftp_chdir(
                        $this->connection,
                        count($skipedFolders) ? '/' . implode('/', $skipedFolders) : '/'
                    )) {
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
                        if (!ftp_chdir($this->connection, $folder)
                        && (!ftp_mkdir($this->connection, $folder)
                        || !ftp_chdir($this->connection, $folder))) {
                            $chdirFailed = true;
                            break;
                        } else {
                            $this->currentPath[] = $folder;
                        }
                    } catch (\Exception $e) {
                        $error = $e->getMessage();
                    }
                }
            }

            if (!$chdirFailed) {
                $enteredRemotePath = '/' . implode('/', $this->currentPath);

                return true;
            }
        }

        return false;
    }
}
