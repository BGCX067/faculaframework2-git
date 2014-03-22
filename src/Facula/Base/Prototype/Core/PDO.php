<?php

/**
 * PDO Core Prototype
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

namespace Facula\Base\Prototype\Core;

/**
 * Prototype class for PDO core for make core remaking more easy
 */
abstract class PDO extends \Facula\Base\Prototype\Core implements \Facula\Base\Implement\Core\PDO
{
    /** Declare maintainer information */
    public static $plate = array(
        'Author' => 'Rain Lee',
        'Reviser' => '',
        'Updated' => '2013',
        'Contact' => 'raincious@gmail.com',
        'Version' => __FACULAVERSION__,
    );

    /** Instance setting for caching */
    protected $configs = array();

    /** Pool for databases */
    protected $pool = array();

    /** Databases map */
    protected $map = array();

    /** Connections map */
    protected $connMap = array();

    /**
     * Constructor
     *
     * @param array $cfg Array of core configuration
     * @param array $common Array of common configuration
     * @param \Facula\Framework $facula The framework itself
     *
     * @return void
     */
    public function __construct(&$cfg)
    {
        if (class_exists('PDO')) {
            $this->configs = array(
                'DefaultTimeout' => isset($cfg['DefaultTimeout'])
                                    ? (int)($cfg['DefaultTimeout']) : 1,

                'WaitTimeout' => isset($cfg['WaitTimeout'])
                                    ? (int)($cfg['WaitTimeout']) : 0,

                'SelectMethod' => isset($cfg['SelectMethod'])
                                    ? $cfg['SelectMethod'] : 'Normal',

                'PriorMethod' => isset($cfg['PriorMethod'])
                                    ? $cfg['PriorMethod'] : 'Redundance',
            );

            $supportedDrivers = \PDO::getAvailableDrivers();

            if (isset($cfg['DatabaseGroup'])
                && is_array($cfg['DatabaseGroup'])) {
                foreach ($cfg['DatabaseGroup'] as $index => $database) {
                    if (isset($database['Driver'][0])) {
                        if (in_array($database['Driver'], $supportedDrivers)) {
                            // Parse and save config to instance
                            $this->pool['DBs'][$index] = array(
                                'ID' => $index,
                                'Driver' => $database['Driver'],
                                'Connection' => isset($database['Connection'][0])
                                                ?
                                                $database['Connection']
                                                :
                                                'host'
                                                ,

                                'Prefix' => isset($database['Prefix'][0])
                                                ?
                                                $database['Prefix']
                                                :
                                                null
                                                ,

                                'Username' => isset($database['Username'][0])
                                                ?
                                                $database['Username']
                                                :
                                                null
                                                ,

                                'Password' => isset($database['Password'][0])
                                                ?
                                                $database['Password']
                                                :
                                                null
                                                ,

                                'Timeout' => isset($database['Timeout'])
                                                ?
                                                $database['Timeout']
                                                :
                                                $this->configs['DefaultTimeout']
                                                ,

                                'Wait' => isset($database['Wait'])
                                                ?
                                                $database['Wait']
                                                :
                                                $this->configs['WaitTimeout']
                                                ,

                                'LstConnected' => 0,

                                'Persistent' => isset($database['Persistent'])
                                                ?
                                                ($database['Persistent'] ? true : false)
                                                :
                                                false
                                                ,

                                'Options' => isset($database['Options'])
                                                && is_array($database['Options'])
                                                ?
                                                $database['Options']
                                                :
                                                array()
                                                ,
                            );

                            // If needed, add current item to Table mapping for search filter.
                            if (($this->configs['SelectMethod'] == 'Table'
                                || $this->configs['SelectMethod'] == 'Table+Operation')) {
                                if (isset($database['Tables'])
                                    && is_array($database['Tables'])) {
                                    foreach ($database['Tables'] as $table) {
                                        $this->pool['TTDBs'][$table][$index] = &$this->pool['DBs'][$index];

                                        // Add Tables to Database item
                                        $this->pool['DBs'][$index]['Tables'][] = $table;
                                    }
                                } else {
                                    throw new \Exception(
                                        'Specified database select method require table declaration'
                                        . ' which is missing for database No.' . $index . '.'
                                    );
                                }
                            }

                            // If needed, add current item to Permission mapping for search filter.
                            if (($this->configs['SelectMethod'] == 'Operation'
                                || $this->configs['SelectMethod'] == 'Table+Operation')) {
                                if (isset($database['Operates'])
                                    && is_array($database['Operates'])) {
                                    foreach ($database['Operates'] as $key => $operation) {
                                        $this->pool['OTDBs'][$operation][$index] =
                                            &$this->pool['DBs'][$index];

                                        // Add Operates to Database item
                                        $this->pool['DBs'][$index]['Operations'][] = $operation;
                                    }
                                } else {
                                    throw new \Exception(
                                        'Specified database select method require'
                                        . ' allowance setting which is missing for database'
                                        . ' No.' . $index . '.'
                                    );
                                }
                            }

                            // Mapping current database item to connection status store
                            $this->map['DBConn'][$index] = array(
                                'Connection' => null,
                                'Database' => &$this->pool['DBs'][$index],
                            );

                            // Mapping current database item to Prioritize store for later use
                            // DBP for sort the database item so we can shuffle it without disturb database index
                            $this->map['DBP'][$index] = &$this->pool['DBs'][$index];
                        } else {
                            throw new \Exception(
                                'Sorry, specified driver '
                                . $database['Driver']
                                . ' for database No.'
                                . $index
                                . ' is not supported on this server. It\'s only support: '
                                . implode(', ', $supportedDrivers)
                            );
                        }
                    } else {
                        throw new \Exception(
                            'You must specify the PDO driver for database No.'
                            . $index
                            . '.'
                        );
                    }
                }
            } else {
                throw new \Exception(
                    'Sorry, no database setting. So we can\'t set up for the PDO connection.'
                );
            }
        } else {
            throw new \Exception(
                'PHP Data Object (PDO) interface not found. This server may not support it.'
            );
        }
    }

    /**
     * Warm up initializer
     *
     * @return bool Return true when initialization complete, false otherwise
     */
    public function inited()
    {
        switch ($this->configs['PriorMethod']) {
            case 'Balance':
                $keys = array_keys($this->map['DBP']);
                $result = array();

                shuffle($keys);

                foreach ($keys as $key) {
                    $result[$key] = $this->map['DBP'][$key];
                }

                $this->map['DBP'] = $result;

                break;

            case 'Redundance':
                // Yeah, actually do nothing
                break;

            default:
                break;
        }

        return true;
    }

    /**
     * Get database which contains specified table
     *
     * @param string $tableName The name of that table
     *
     * @return array Return the array of databases which contain the table
     */
    protected function getDatabaseByTable($tableName)
    {
        if (isset($this->pool['TTDBs'][$tableName])) {
            return array_intersect_key(
                $this->map['DBP'],
                $this->pool['TTDBs'][$tableName]
            );

        }

        return array();
    }

    /**
     * Get database which allow specified operation
     *
     * @param string $operationName The name of that operation (Depends on configuration)
     *
     * @return array Return the array of databases which contain the table
     */
    protected function getDatabaseByOperation($operationName)
    {
        if (isset($this->pool['OTDBs'][$operationName])) {
            return array_intersect_key(
                $this->map['DBP'],
                $this->pool['OTDBs'][$operationName]
            );
        }

        return array();
    }

    /**
     * Get database which allow specified operation and also contain the table
     *
     * @param string $table The name of that table
     * @param string $operation The name of that operation (Depends on configuration)
     *
     * @return array Return the array of databases which contain the table
     */
    protected function getDatabaseByTableOperation($table, $operation)
    {
        $selected = array();

        $selectedByTable        = $this->getDatabaseByTable($table);
        $selectedByOperation    = $this->getDatabaseByOperation($operation);

        return array_intersect_key(
            $selectedByTable,
            $selectedByOperation
        );
    }

    /**
     * Get connection with settings
     *
     * @param array $setting Setting ('Table' => '', 'Operation' => '') to connect
     *
     * @return mixed Return PDO object that connected to a database server when success, false otherwise
     */
    public function getConnection(array $setting = array())
    {
        $tablekey = $error = '';

        switch ($this->configs['SelectMethod']) {
            case 'Normal':
                if (isset($this->connMap[$this->configs['SelectMethod']])) {
                    return $this->doPDOConnectivityCheck(
                        $this->connMap[$this->configs['SelectMethod']],
                        $error
                    );
                } else {
                    foreach ($this->map['DBP'] as $key => $database) {
                        if ($this->connMap[$this->configs['SelectMethod']] = $this->doPDOConnect(
                            $database['ID'],
                            $error
                        )) {
                            return $this->connMap[$this->configs['SelectMethod']];
                        }
                    }
                }
                break;

            case 'Table':
                if (isset($setting['Table'][0])) {
                    $tablekey = $setting['Table'];

                    if (isset($this->connMap[$this->configs['SelectMethod']][$tablekey])) {
                        return $this->doPDOConnectivityCheck(
                            $this->connMap[$this->configs['SelectMethod']][$tablekey],
                            $error
                        );
                    } else {
                        foreach ($this->getDatabaseByTable($setting['Table']) as $key => $database) {
                            if ($this->connMap[$this->configs['SelectMethod']][$tablekey]
                                = $this->doPDOConnect(
                                    $database['ID'],
                                    $error
                                )) {
                                // Mapping all tables to this database link
                                foreach ($database['Tables'] as $table) {
                                    if (!isset($this->connMap[$this->configs['SelectMethod']][$table])) {
                                        $this->connMap[$this->configs['SelectMethod']][$table] =
                                            &$this->connMap[$this->configs['SelectMethod']][$key];
                                    }
                                }

                                return $this->connMap[$this->configs['SelectMethod']][$tablekey];
                                break;
                            }
                        }
                    }
                } else {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_PDO_GETCONNECTION_SETTINGMISSION_TABLE',
                        'data',
                        true
                    );
                }
                break;

            case 'Operation':
                if (isset($setting['Operation'][0])) {
                    $tablekey = $setting['Operation'];

                    if (isset($this->connMap[$this->configs['SelectMethod']][$tablekey])) {
                        return $this->doPDOConnectivityCheck(
                            $this->connMap[$this->configs['SelectMethod']][$tablekey],
                            $error
                        );
                    } else {
                        foreach ($this->getDatabaseByOperation($setting['Operation']) as $key => $database) {
                            if ($this->connMap[$this->configs['SelectMethod']][$tablekey] =
                                    $this->doPDOConnect($database['ID'], $error)) {
                                // Mapping all tables to this database link
                                foreach ($database['Operations'] as $operation) {
                                    if (!isset($this->connMap[$this->configs['SelectMethod']][$operation])) {
                                        $this->connMap[$this->configs['SelectMethod']][$operation] =
                                            &$this->connMap[$this->configs['SelectMethod']][$key];
                                    }
                                }

                                return $this->connMap[$this->configs['SelectMethod']][$tablekey];
                                break;
                            }
                        }
                    }
                } else {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_PDO_GETCONNECTION_SETTINGMISSION_OPERATION',
                        'data',
                        true
                    );
                }
                break;

            case 'Table+Operation':
                if (isset($setting['Table'][0]) && isset($setting['Operation'][0])) {
                    $tablekey = $setting['Table'] . '#' . $setting['Operation'];

                    if (isset($this->connMap[$this->configs['SelectMethod']][$tablekey])) {
                        return $this->doPDOConnectivityCheck(
                            $this->connMap[$this->configs['SelectMethod']][$tablekey],
                            $error
                        );
                    } else {
                        foreach ($this->getDatabaseByTableOperation(
                            $setting['Table'],
                            $setting['Operation']
                        ) as $key => $database) {
                            if ($this->connMap[$this->configs['SelectMethod']][$tablekey]
                                = $this->doPDOConnect(
                                    $database['ID'],
                                    $error
                                )) {

                                // Mapping all tables to this database link
                                foreach ($database['Operations'] as $operation) {
                                    foreach ($database['Tables'] as $table) {

                                        if (!isset(
                                            $this->connMap[$this->configs['SelectMethod']]
                                            [$table . '#' . $operation]
                                        )) {
                                            $this->connMap[$this->configs['SelectMethod']]
                                            [$table . '#' . $operation]
                                                = &$this->connMap[$this->configs['SelectMethod']][$key];
                                        }
                                    }
                                }

                                return $this->connMap[$this->configs['SelectMethod']][$tablekey];
                                break;
                            }
                        }
                    }
                } else {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_PDO_GETCONNECTION_SETTINGMISSED_TABLEOPERATION',
                        'data',
                        true
                    );
                }
                break;

            default:
                \Facula\Framework::core('debug')->exception(
                    'ERROR_PDO_UNKNOWNSELECTMETHOD|' . $this->configs['SelectMethod'],
                    'data',
                    true
                );
                break;
        }

        \Facula\Framework::core('debug')->exception(
            'ERROR_PDO_NOSERVERAVAILABLE' . ($error ? '|' . $error : '|' . implode(',', $setting)),
            'data',
            true
        );

        return false;
    }

    /**
     * Check if we theoretically lost the connection, and perform reconnect automatically
     *
     * @param string $dbh The PDO connection object
     * @param string $error A reference for storage error detail.
     *
     * @return object Return PDO object that connected to a database server
     */
    protected function doPDOConnectivityCheck(\PDO &$dbh, &$error)
    {
        if ($dbh->_connection['Wait']
            && time() - $dbh->_connection['LstConnected'] >= $dbh->_connection['Wait']) {
            $dbh = $this->doPDOReconnect($dbh, $error);
        }

        return $dbh;
    }

    /**
     * Connect to a server
     *
     * @param string $dbIndex Index number of database server in configure file
     * @param string $error A reference for storage error detail.
     *
     * @return mixed Return PDO object that connected to a database server when success, false otherwise
     */
    protected function doPDOConnect($dbIndex, &$error)
    {
        $dbh = null;
        $successed = false;

        if (!isset($this->map['DBConn'][$dbIndex]['Connection'])) {
            // Enter Critical Section so no error below belowing code will cause error
            \Facula\Framework::core('debug')->criticalSection(true);

            try {
                $dbh = new \PDO(
                    $this->pool['DBs'][$dbIndex]['Driver'] . ':' . $this->pool['DBs'][$dbIndex]['Connection'],
                    $this->pool['DBs'][$dbIndex]['Username'],
                    $this->pool['DBs'][$dbIndex]['Password'],
                    array(
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING,
                        \PDO::ATTR_TIMEOUT => $this->pool['DBs'][$dbIndex]['Timeout'],
                        \PDO::ATTR_PERSISTENT => $this->pool['DBs'][$dbIndex]['Persistent']
                    ) + $this->pool['DBs'][$dbIndex]['Options']
                );

                $this->pool['DBs'][$dbIndex]['LstConnected'] = time();
                $dbh->_connection = &$this->pool['DBs'][$dbIndex];

                $successed = true;
            } catch (\PDOException $e) {
                $error = 'PDO Connection failed: Database No.'
                        . $dbIndex
                        . ' Error: '
                        . $e->getMessage();
            }

            // Exit Critical Section, restore error caught
            \Facula\Framework::core('debug')->criticalSection(false);

            if ($successed) {
                $this->map['DBConn'][$dbIndex]['Connection'] = &$dbh;

                return $this->map['DBConn'][$dbIndex]['Connection'];
            } else {
                \Facula\Framework::core('debug')->addLog('data', $error);
            }
        } else {
            return $this->map['DBConn'][$dbIndex]['Connection'];
        }

        return false;
    }

    /**
     * Reconnect to a server
     *
     * @param string $dbh The PDO connection object
     * @param string $error A reference for storage error detail.
     *
     * @return mixed Return PDO object that connected to a database server when success, false otherwise
     */
    protected function doPDOReconnect(\PDO &$dbh, &$error)
    {
        $dbIdx = $dbh->_connection['ID'];

        if (isset($dbh->_connection)) {
            if (isset($this->map['DBConn'][$dbh->_connection['ID']]['Connection'])) {
                $dbh = $this->map['DBConn'][$dbIdx]['Connection'] = null;

                unset($this->map['DBConn'][$dbIdx]['Connection']);
            }

            if ($dbh = $this->doPDOConnect($dbIdx, $error)) {
                return $dbh;
            }
        }

        return false;
    }
}
