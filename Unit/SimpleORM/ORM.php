<?php

/**
 * SimpleORM Database Abstract
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

namespace Facula\Unit\SimpleORM;

/**
 * SimpleORM
 */
abstract class ORM implements Implement, \ArrayAccess
{
    /** Current Table */
    protected static $table = '';

    /** Declared fields */
    protected static $fields = array();

    /** The primary key */
    protected static $primary = '';

    /** Trigger to enable or disable auto parser */
    protected static $noParser = false;

    /** Container of data */
    private $data = array();

    /** Backup container for original data */
    private $dataOriginal = array();

    /** Path to the Object cached */
    public $cachedObjectFilePath = '';

    /** Time of when the object cached */
    public $cachedObjectSaveTime = 0;

    /**
     * Magic Setter: Set a data in the current ORM session
     *
     * @param string $key The data key name of the property
     * @param string $val Value of the property
     *
     * @return void
     */
    public function __set($key, $val)
    {
        // Behaver changed. It will not try to protect original value,
        // but backup last value.
        if (!isset($this->data[$key])) {
            $this->dataOriginal[$key] = $val;
        }

        $this->data[$key] = $val;
    }

    /**
     * Magic Getter: Get a data in the current ORM session
     *
     * @param string $key The data key name of the property
     *
     * @return mixed Return the data when success, or null when data not set
     */
    public function __get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Magic Isset: Check of the data is existed in current ORM session
     *
     * @param string $key The data key name of the property
     *
     * @return bool Return true when data exist, or false when fail
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Magic Unset: Release the data that existed in current ORM session
     *
     * @param string $key The data key name of the property
     *
     * @return void
     */
    public function __unset($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }
    }

    /**
     * Array Operation: Set data
     *
     * @param integer $offset The data key name of the property
     * @param string $value The value of the property
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (!isset($this->data[$offset])) {
            $this->dataOriginal[$offset] = $value;
        }

        $this->data[$offset] = $value;
    }

    /**
     * Array Operation: Get data
     *
     * @param integer $offset The data key name of the property
     *
     * @return bool Return true when data exist, or null when data not found
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * Array Operation: Check of the data is existed in current ORM session
     *
     * @param integer $offset The data key name of the property
     *
     * @return bool Return true when data exist, or false when fail
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Array Operation: Release the data that existed in current ORM session
     *
     * @param integer $offset The data key name of the property
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Get the value of primary key
     *
     * @param integer $offset The data key name of the property
     *
     * @return mixed Return the value of the key when success, or null when not set
     */
    public function getPrimaryValue()
    {
        if (isset($this->data[static::primary])) {
            return $this->data[static::primary];
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_ORM_GETPRIMARY_PRIMARYDATA_EMPTY',
                'orm',
                true
            );
        }

        return null;
    }

    /**
     * Export field declaration
     *
     * @return array Return the field declaration
     */
    public function getFields()
    {
        return static::$fields;
    }

    /**
     * Export current data
     *
     * @return array Return the data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get current data reference to operate data outside the class
     *
     * @return array Return the data reference
     */
    protected function & getDataRef()
    {
        return $this->data;
    }

    /**
     * Get one row from database
     *
     * @param array $param The WHERE param for query
     * @param string $returnType Data return type for Query class
     * @param string $whereOperator Default operator for WHERE condition
     *
     * @return array Return the result of query when success, false otherwise
     */
    public static function get(
        array $param,
        $returnType = 'CLASS',
        $whereOperator = '='
    ) {
        $data = array();

        if (($data = self::fetch(
            array('Where' => $param),
            0,
            1,
            $returnType = 'CLASS',
            $whereOperator = '='
        )) && isset($data[0])) {
            return $data[0];
        }

        return false;
    }

    /**
     * Get datas from database
     *
     * @param array $param The params for query (Where, Order, Limit)
     * @param integer $offset The start point of cursor
     * @param integer $dist Length of how long the cursor will travel
     * @param string $returnType Data return type for Query class
     * @param string $whereOperator Default operator for WHERE condition
     *
     * @return array Return the result of query when success, false otherwise
     */
    public static function fetch(
        array $param,
        $offset = 0,
        $dist = 0,
        $returnType = 'CLASS',
        $whereOperator = '='
    ) {
        $whereParams = array();

        $query = null;

        $query = \Facula\Unit\Query\Factory::from(static::$table, !static::$noParser);
        $query->select(static::$fields);

        if (isset($param['Where'])) {
            foreach ($param['Where'] as $field => $value) {
                if (is_array($value)) {
                    $whereParams['Operator'] = isset($value[1])
                        ? $value[1] : $whereOperator;

                    $whereParams['Value'] = isset($value[0])
                        ? $value[0] : 'NULL';
                } else {
                    $whereParams['Operator'] = $whereOperator;
                    $whereParams['Value'] = $value;
                }

                $query->where(
                    'AND',
                    $field,
                    $whereParams['Operator'],
                    $whereParams['Value']
                );
            }
        }

        if (isset($param['Order'])) {
            foreach ($param['Order'] as $field => $value) {
                $query->order($field, $value);
            }
        }

        if ($offset || $dist) {
            $query->limit($offset, $dist);
        }

        switch ($returnType) {
            case 'CLASS':
                return $query->fetch(
                    'CLASS',
                    get_called_class()
                );
                break;

            default:
                return $query->fetch();
                break;
        }

        return array();
    }

    /**
     * Search data in database
     *
     * @param array $param The params for query (Where, Order, Limit)
     * @param integer $offset The start point of cursor
     * @param integer $dist Length of how long the cursor will travel
     * @param string $returnType Data return type for Query class
     *
     * @return array Return the result of query when success, false otherwise
     */
    public static function finds(
        array $param,
        $offset = 0,
        $dist = 0,
        $returnType = 'CLASS'
    ) {
        return self::fetch($param, $offset, $dist, $returnType, 'LIKE');
    }

    /**
     * Get data using specified key
     *
     * @param string $field Field name to get
     * @param string $value Value of primary key
     * @param string $returnType Data return type for Query class
     *
     * @return array Return the result of query when success, false otherwise
     */
    public static function getBy($field, $value, $returnType = 'CLASS')
    {
        return self::get(
            array(
                $field => $value,
            ),
            $returnType
        );
    }

    /**
     * Get data using value of primary key
     *
     * @param string $value Value of primary key
     * @param string $returnType Data return type for Query class
     *
     * @return array Return the result of query when success, false otherwise
     */
    public static function getByPK($value, $returnType = 'CLASS')
    {
        return self::get(array(
            static::$primary => $value
        ), $returnType);
    }

    /**
     * Get data using value of primary keys
     *
     * @param array $values Values of primary keys
     * @param array $param Params for query (Where, Order, Limit)
     * @param integer $offset The start point of cursor
     * @param integer $dist Length of how long the cursor will travel
     * @param string $returnType Data return type for Query class
     * @param string $whereOperator Default operator for WHERE condition
     *
     * @return array Return the result of query when success, false otherwise
     */
    public static function fetchByPKs(
        array $values,
        array $param = array(),
        $offset = 0,
        $dist = 0,
        $returnType = 'CLASS',
        $whereOperator = '='
    ) {
        return self::fetchInKeys(
            static::$primary,
            $values,
            $param,
            $offset,
            $dist,
            $returnType,
            $whereOperator
        );
    }

    /**
     * Get data using value of specified key
     *
     * @param string $keyField Field of the key
     * @param mixed $value Value of the key field
     * @param array $param Parameters for query
     * @param string $returnType Data return type for Query class
     * @param string $whereOperator Default operator for WHERE condition
     *
     * @return array Return the result of query when success, false otherwise
     */
    public static function getInKey(
        $keyField,
        $value,
        $param = array(),
        $returnType = 'CLASS',
        $whereOperator = '='
    ) {
        $data = array();

        if ($data = array_values(
            self::fetchInKeys(
                $keyField,
                array($value),
                $param,
                0,
                1,
                $returnType,
                $whereOperator
            )
        )) {
            if (isset($data[0])) {
                return $data[0];
            }
        }

        return false;
    }

    /**
     * Get data using value of specified keys
     *
     * @param string $keyField Field of the key
     * @param array $values Value of the key field
     * @param array $param Parameters for query
     * @param integer $offset The start point of cursor
     * @param integer $dist Length of how long the cursor will travel
     * @param string $returnType Data return type for Query class
     * @param string $whereOperator Default operator for WHERE condition
     *
     * @return array Return the result of query when success, false otherwise
     */
    public static function fetchInKeys(
        $keyField,
        array $values,
        array $param = array(),
        $offset = 0,
        $dist = 0,
        $returnType = 'CLASS',
        $whereOperator = '='
    ) {
        $fetched = $where = array();

        $param['Where'][$keyField] = array($values, 'IN');

        if ($fetched = self::fetch(
            $param,
            $offset,
            $dist,
            $returnType,
            $whereOperator
        )) {
            return $fetched;
        }

        return array();
    }

    /**
     * Parser the result of fetch for Fetch With
     *
     * @param array $joinModels Setting of joined models
     * @param array $joinedMap Map of joined models
     * @param string $parentName Parent name of current joined model
     *
     * @return array Return true when data parsed, false otherwise
     */
    protected static function fetchWithJoinParamParser(
        array &$joinModels,
        array &$joinedMap,
        $parentName = 'main'
    ) {
        if (is_array($joinModels)) {
            foreach ($joinModels as $jMkey => $jMVal) {
                if (!isset($jMVal['Field'][0])) {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_ORM_FETCHWITH_JOIN_FIELDNAME_NOTSET',
                        'orm',
                        true
                    );

                    return false;
                    break;
                }

                if (!isset($jMVal['Model'][0])) {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_ORM_FETCHWITH_JOIN_MODELNAME_NOTSET',
                        'orm',
                        true
                    );

                    return false;
                    break;
                }

                if (!isset($jMVal['Key'][0])) {
                    \Facula\Framework::core('debug')->exception(
                        'ERROR_ORM_FETCHWITH_JOIN_MODELKEYNAME_NOTSET',
                        'orm',
                        true
                    );

                    return false;
                    break;
                }

                $tempJoinedModelAlias = isset($jMVal['Alias'])
                    ? $jMVal['Alias'] : ($jMVal['Field']);

                $tempJoinedModelAddr = $parentName
                                        . '.'
                                        . $tempJoinedModelAlias;

                $joinedMap[$tempJoinedModelAddr] = array(
                    'Field' => $jMVal['Field'],
                    'Model' => $jMVal['Model'],
                    'Key' => $jMVal['Key'],
                    'Alias' => $tempJoinedModelAlias,
                    'Single' => isset($jMVal['Single'])
                                && $jMVal['Single'] ? true : false,
                    'Param' => isset($jMVal['Param']) ? $jMVal['Param'] : array(),
                    'With' => $parentName,
                );

                if (isset($jMVal['With'])) {
                    self::fetchWithJoinParamParser(
                        $jMVal['With'],
                        $joinedMap,
                        $tempJoinedModelAddr
                    );
                }
            }

            return true;
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_ORM_FETCHWITH_JOIN_WITH_INVALID',
                'orm',
                true
            );
        }

        return false;
    }

    /**
     * Convert current data into columned array
     *
     * @param array $dataMap Data struct that will be converted
     * @param array $dataMapName Name of the map
     * @param string $elementKey Key of the field
     *
     * @return array Return the converted array
     */
    protected static function fetchWithGetColumnDataRootRef(
        array &$dataMap,
        $dataMapName,
        $elementKey
    ) {
        $result = array();

        if (isset($dataMap[$dataMapName])) {
            foreach ($dataMap[$dataMapName] as $key => $val) {
                if (isset($val[$elementKey])) {
                    $result[$val[$elementKey]][] = &$dataMap[$dataMapName][$key];
                }
            }
        }

        return $result;
    }

    /**
     * Get a query result with multi tables
     *
     * @param array $joinModels Join setting
     * @param array $whereParams Where conditions
     * @param string $whereOperator Default operator of WHERE
     *
     * @return array Return the result of query when success, or false when fail
     */
    public static function getWith(
        array $joinModels,
        array $whereParams,
        $whereOperator = '='
    ) {
        $data = array();
        $currentParams = array(
            'Where' => $whereParams,
        );

        if ($data = self::fetchWith(
            $joinModels,
            $currentParams,
            0,
            1,
            $whereOperator
        )) {
            if (isset($data[0])) {
                return $data[0];
            }
        }

        return false;
    }

    /**
     * Get query results with multi tables
     *
     * @param array $joinModels Join setting
     * @param array $currentParams Where conditions for primary table
     * @param integer $offset The start point of cursor
     * @param integer $dist Length of how long the cursor will travel
     * @param string $whereOperator Default operator of WHERE
     *
     * @return array Return the query result when query is succeed, or a empty array when query's failed
     */
    public static function fetchWith(
        array $joinModels,
        array $currentParams,
        $offset = 0,
        $dist = 0,
        $whereOperator = '='
    ) {
        $principals = array();
        $principal = null;

        $joinedMap = $dataMap = $colAddress = array();

        /*
            Format:

            $joinModels = array(
                array(
                    // Field name of the key in primary table
                    'Field' => 'TargetField',

                    // Model name of the table you want to join
                    'Model' => 'ModelName2',

                    // Field name of the key use to query
                    'Key' => 'JoinedKey',

                    // Save result in to another name
                    'Alias' => 'JoinResultASFieldName',

                    // Only return one result, use for primary or unique field
                    'Single' => true,

                    // Fetch params for joined table
                    'Param' => array(
                        'Where' => array(
                            'key' => 'val',
                        )
                    )

                    // Join Sub table of this sub table
                    'With' => array(
                        array(
                            'Field' => 'TargetField',
                            'Model' => 'ModelName2',
                            'Key' => 'JoinedKey',
                            'Alias' => 'JoinResultASFieldName',
                        ),
                    ),
                ),
                array(
                    'Field' => 'TargetField',
                    'Model' => 'ModelName2',
                    'Key' => 'JoinedKey',
                    'Alias' => 'JoinResultASFieldName',
                ),
            );
        */

        if ($principals = self::fetch(
            $currentParams,
            $offset,
            $dist,
            'CLASS',
            $whereOperator
        )) {
            // First step is, fetch data from master table, and save reference
            // to total reference map
            foreach ($principals as $principalKey => $principal) {
                $dataMap['main'][$principalKey] = &$principal->getDataRef();
            }

            // Handle With Joined Params after master table successful queried
            self::fetchWithJoinParamParser($joinModels, $joinedMap, 'main');

            // Now, Init data container for joined tables
            foreach ($joinedMap as $joinedMapKey => $joinedMapVal) {
                $dataMap[$joinedMapKey] = array();

                $joinedMap[$joinedMapKey]['Data'] = &$dataMap[$joinedMapKey];
            }

            // Query joined table one by one
            foreach ($joinedMap as $joinedKey => $JoinedVal) {
                $tempJoinedKeys = self::fetchWithGetColumnDataRootRef(
                    $dataMap,
                    $JoinedVal['With'],
                    $JoinedVal['Field']
                );

                if (!empty($tempJoinedKeys)) {
                    foreach ($JoinedVal['Model']::fetchInKeys(
                        $JoinedVal['Key'],
                        array_keys($tempJoinedKeys),
                        $JoinedVal['Param'],
                        0,
                        0,
                        'ASSOC'
                    ) as $pKey => $pVal) {
                        $JoinedVal['Data'][$pKey] = $pVal;

                        foreach ($tempJoinedKeys[$pVal[$JoinedVal['Key']]] as $tkJoinedKey => $tkJoinedVal) {
                            if (isset(
                                $tempJoinedKeys[$pVal[$JoinedVal['Key']]][$tkJoinedKey][$JoinedVal['Alias']]
                            )
                            &&
                            !is_array(
                                $tempJoinedKeys[$pVal[$JoinedVal['Key']]][$tkJoinedKey][$JoinedVal['Alias']]
                            )) {
                                $tempJoinedKeys[$pVal[$JoinedVal['Key']]][$tkJoinedKey][$JoinedVal['Alias']] =
                                    array();
                            }

                            if ($JoinedVal['Single']
                            && empty(
                                $tempJoinedKeys[$pVal[$JoinedVal['Key']]][$tkJoinedKey][$JoinedVal['Alias']]
                            )) {
                                $tempJoinedKeys[$pVal[$JoinedVal['Key']]][$tkJoinedKey][$JoinedVal['Alias']] =
                                    &$JoinedVal['Data'][$pKey];
                            } else {
                                $tempJoinedKeys[$pVal[$JoinedVal['Key']]][$tkJoinedKey][$JoinedVal['Alias']] =
                                    &$JoinedVal['Data'][$pKey];
                            }
                        }
                    }
                }
            }

            return $principals;
        }

        return array();
    }

    /**
     * Save current data into Database
     *
     * @return mixed Return count of affected data when success, or false otherwise
     */
    public function save()
    {
        $primaryKey = $result = $query = null;
        $sets = $changes = array();
        $changeOperator = '';
        $changesTo = 0;

        $tempNewValType = '';
        $tempNewValIsNum = false;

        if (isset($this->data[static::$primary])) {
            // Must call the hook before we handle data
            $this->onSave();

            if (isset($this->dataOriginal[static::$primary])) {
                $primaryKey = $this->dataOriginal[static::$primary];
            } else {
                $primaryKey = $this->data[static::$primary];
            }

            foreach ($this->data as $key => $val) {
                if (isset(static::$fields[$key])) {
                    // Determine data type of current val
                    if (is_int($val)) {
                        $tempNewValType = 'int';
                        $tempNewValIsNum = true;
                    } elseif (is_float($val)) {
                        $tempNewValType = 'float';
                        $tempNewValIsNum = true;
                    } else {
                        $tempNewValType = 'other';
                        $tempNewValIsNum = false;
                    }

                    // If this val is number, use change method to change it
                    if ($tempNewValIsNum
                    && isset($this->dataOriginal[$key])
                    && (is_int($this->dataOriginal[$key]) || is_float($this->dataOriginal[$key]))) {

                        switch ($tempNewValType) {
                            case 'int':
                                $changesTo = (int)$this->dataOriginal[$key] - $val;
                                break;

                            case 'float':
                                $changesTo = (float)$this->dataOriginal[$key] - $val;
                                break;

                            default:
                                $changesTo = 0;
                                continue;
                                break;
                        }

                        if ($changesTo > 0) {
                            $changeOperator = '-';
                        } elseif ($changesTo < 0) {
                            $changeOperator = '+';
                        } else {
                            $changeOperator = '';
                        }

                        if ($changeOperator) {
                            $changes[] = array(
                                'Field' => $key,
                                'Operator' => $changeOperator,
                                'Value' => abs($changesTo)
                            );
                        } else {
                            $sets[$key] = $val;
                        }
                    } else {
                        // Or, use replace method
                        $sets[$key] = $val;
                    }
                }
            }

            $query = \Facula\Unit\Query\Factory::from(
                static::$table,
                !static::$noParser
            )->update(static::$fields);

            if (!empty($sets)) {
                $query->set($sets);
            }

            if (!empty($changes)) {
                $query->changes($changes);
            }

            if ($result = $query->where(
                'AND',
                static::$primary,
                '=',
                $primaryKey
            )->save()) {
                $this->dataOriginal = $this->data;

                return $result;
            }
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_ORM_SAVE_PRIMARY_KEY_NOTSET',
                'orm',
                true
            );
        }

        return false;
    }

    /**
     * Insert current data into database
     *
     * @return mixed Return the primary key of new inserted data when success, or false when fail
     */
    public function insert()
    {
        $result = null;
        $data = $keys = array();

        // Must call the hook before we handle data
        $this->onInsert();

        foreach ($this->data as $key => $val) {
            if (isset(static::$fields[$key])) {
                $keys[$key] = static::$fields[$key];
                $data[$key] = $val;
            }
        }

        // Must returning primary key
        if ($result = \Facula\Unit\Query\Factory::from(
            static::$table,
            !static::$noParser
        )->insert(
            $keys
        )->value(
            $data
        )->save(
            static::$primary
        )) {

            $this->dataOriginal = $this->data;

            if (!isset($this->data[static::$primary])) {
                $this->data[static::$primary] = $result;
            }

            return $result;
        }

        return false;
    }

    /**
     * Delete data from database
     *
     * @return mixed Return count of affected data when success, or false for otherwise
     */
    public function delete()
    {
        $result = null;

        if (isset($this->data[static::$primary])) {
            $this->onDelete();

            if ($result = \Facula\Unit\Query\Factory::from(
                static::$table,
                !static::$noParser
            )->delete(
                static::$fields
            )->where(
                'AND',
                static::$primary,
                '=',
                $this->data[static::$primary]
            )->save()) {
                $this->dataOriginal = $this->data = array();

                return $result;
            }
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_ORM_SAVE_PRIMARY_KEY_NOTSET',
                'orm',
                true
            );
        }

        return false;
    }

    /**
     * Hook for data insert
     *
     * @return void
     */
    protected function onInsert()
    {
    }

    /**
     * Hook for data update
     *
     * @return void
     */
    protected function onSave()
    {
    }

    /**
     * Hook for data delete
     *
     * @return void
     */
    protected function onDelete()
    {
    }
}
