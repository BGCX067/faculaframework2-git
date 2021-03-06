<?php

/**
 * SimpleORM Database Abstract
 *
 * Facula Framework 2015 (C) Rain Lee
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
 * @copyright  2015 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Unit\SimpleORM;

use Facula\Unit\SimpleORM\Exception as Exception;
use Facula\Unit\Query\Factory as Query;

/**
 * SimpleORM
 */
abstract class ORM implements Implement, \ArrayAccess
{
    /** Current Table */
    protected static $table = '';

    /** Declared fields */
    protected static $fields = array();

    /** Declared field aliases */
    protected static $aliases = array();

    /** Declared default values */
    protected static $defaults = array();

    /** Declared with query parameters */
    protected static $withs = array();

    /** Declare integer fields that use increase / decrease method for updating */
    protected static $creases = array();

    /** Declare which fields will be used in query, empty for all in static::$fields */
    protected static $uses = array();

    /** The primary key */
    protected static $primary = '';

    /** Trigger to enable or disable auto parser */
    protected static $noParser = false;

    /** Loaded models */
    private static $loadedModels = array();

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
        // but it will backup the last value.
        if (!isset($this->data[$key])) {
            $this->dataOriginal[$key] = $val;
        }

        // Save the data
        $this->data[$key] = $val;

        // Create alias if needed
        if (!isset(static::$aliases[$key])) {
            return;
        }

        if (isset(static::$fields[static::$aliases[$key]])) {
            throw new Exception\FieldNameConflictAlias(static::$aliases[$key]);
        }

        $this->data[static::$aliases[$key]] = &$this->data[$key];
    }

    /**
     * Magic Getter: Get a data in the current ORM session
     *
     * @param string $key The data key name of the property
     *
     * @return mixed Return the data when success, or null when data not set
     */
    public function &__get($key)
    {
        if (isset($this->data[$key]) || array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        if (isset(static::$defaults[$key])) {
            $this->data[$key] = static::$defaults[$key];

            return $this->data[$key];
        }

        $this->data[$key] = null;

        return $this->data[$key];
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
        if (!isset($this->data[$key])) {
            return;
        }

        // Unsetting a alias
        if (isset(static::$aliases[$key], $this->data[static::$aliases[$key]])) {
            unset($this->data[$key], $this->data[static::$aliases[$key]]);
        } else {
            // Unsetting a actual key
            $this->data[$key] = null;

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

        if (!isset(static::$aliases[$offset])) {
            return;
        }

        if (isset(static::$fields[static::$aliases[$offset]])) {
            throw new Exception\FieldNameConflictAlias(static::$aliases[$offset]);
        }

        $this->data[static::$aliases[$offset]] = &$this->data[$offset];
    }

    /**
     * Array Operation: Get data
     *
     * @param integer $offset The data key name of the property
     *
     * @return bool Return true when data exist, or null when data not found
     */
    public function &offsetGet($offset)
    {
        if (isset($this->data[$offset]) || array_key_exists($offset, $this->data)) {
            return $this->data[$offset];
        }

        if (isset(static::$defaults[$offset])) {
            $this->data[$offset] = static::$defaults[$offset];

            return $this->data[$offset];
        }

        $this->data[$offset] = null;

        return $this->data[$offset];
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
        if (!isset($this->data[$offset])) {
            return;
        }

        if (isset(static::$aliases[$offset], $this->data[static::$aliases[$offset]])) {
            unset($this->data[$offset], $this->data[static::$aliases[$offset]]);
        } else {
            $this->data[$offset] = null;

            unset($this->data[$offset]);
        }
    }

    /**
     * Is data has changed since load?
     *
     * @return bool Return true when changed, false otherwise.
     */
    public function isChanged()
    {
        if ($this->dataOriginal != $this->data) {
            return true;
        }

        return false;
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
            throw new Exception\PrimaryKeyValueEmpty();
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
     * Get current data
     *
     * @return array Return the data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the name of current class (late binded class name)
     *
     * @return string The full name of this (or extender) class
     */
    public static function className()
    {
        return get_called_class();
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
     * Get current using fields according to static::$uses and static::$fields
     *
     * @return string The full name of this (or extender) class
     */
    protected static function getUsingFields()
    {
        $fields = array();

        if (!empty(static::$uses)) {
            foreach (static::$uses as $fieldName) {
                if (isset(static::$fields[$fieldName])) {
                    $fields[$fieldName] = static::$fields[$fieldName];
                } else {
                    throw new Exception\UseOfUndeclaredField($fieldName);
                }
            }
        } else {
            $fields = static::$fields;
        }

        return $fields;
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
            $returnType,
            $whereOperator
        )) && !empty($data)) {
            return array_shift($data);
        }

        return false;
    }

    /**
     * Make query parameter and use Query to query
     *
     * @param array $param The params for query (Where, Order, Limit)
     * @param integer $offset The start point of cursor
     * @param integer $dist Length of how long the cursor will travel
     * @param string $returnType Data return type for Query class
     * @param string $whereOperator Default operator for WHERE condition
     *
     * @return array Return the result of query when success, false otherwise
     */
    protected static function query(
        array $param = array(),
        $offset = 0,
        $dist = 0,
        $returnType = 'CLASS',
        $whereOperator = '='
    ) {
        $whereParams = array();

        $query = null;

        $query = Query::from(static::$table, !static::$noParser);
        $query->select(static::getUsingFields());

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
                if ($results = $query->fetch()) {
                    foreach ($results as $resKey => $resVal) {
                        foreach (static::$aliases as $fieldName => $alias) {
                            if (isset($results[$resKey][$fieldName])) {
                                $results[$resKey][$alias] = &$results[$resKey][$fieldName];
                            }
                        }
                    }

                    return $results;
                }
                break;
        }

        return array();
    }

    /**
     * Get datas from database with configuration
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
        array $param = array(),
        $offset = 0,
        $dist = 0,
        $returnType = 'CLASS',
        $whereOperator = '='
    ) {
        // If current class has withs option, force use with to perform join query
        // Notice this will ignore return type as always return CLASS instance for the result.
        if (!empty(static::$withs)) {
            return static::fetchWith(
                static::$withs,
                $param,
                $offset,
                $dist,
                $whereOperator
            );
        }

        return static::query($param, $offset, $dist, $returnType, $whereOperator);
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
        $modelName = '';
        $fields = array();

        if (!is_array($joinModels)) {
            throw new Exception\JoinParameterInvalid();

            return false;
        }

        foreach ($joinModels as $jMkey => $jMVal) {
            if (!is_array($jMVal)) {
                throw new Exception\JoinOptionInvalid();

                return false;
                break;
            }

            if (empty($jMVal['Field'])) {
                throw new Exception\JoinFieldNameNotSet();

                return false;
                break;
            }

            if (empty($jMVal['Model'])) {
                throw new Exception\JoinModelNameNotSet();

                return false;
                break;
            }

            if (empty($jMVal['Key'])) {
                throw new Exception\JoinKeyNameNotSet();

                return false;
                break;
            }

            if (!isset(self::$loadedModels[$jMVal['Model']]) && !class_exists($jMVal['Model'])) {
                throw new Exception\JoinModelNotFound(
                    $jMVal['Model']
                );

                return false;
                break;
            } else {
                self::$loadedModels[$jMVal['Model']] = true;
            }

            $modelName = $jMVal['Model'];

            $fields = $modelName::getUsingFields();

            $tempJoinedModelAlias = !empty($jMVal['Alias']) ?
                $jMVal['Alias'] : ('_' . $jMVal['Field']);

            if (isset($fields[$tempJoinedModelAlias])) {
                throw new Exception\JoinAliasConflicted(
                    $tempJoinedModelAlias,
                    $modelName
                );

                return false;
                break;
            }

            if (!isset($fields[$jMVal['Key']])) {
                throw new Exception\JoinKeyNameNotFound(
                    $jMVal['Key'],
                    $modelName
                );

                return false;
                break;
            }

            $tempJoinedModelAddr = $parentName
                                    . '.'
                                    . $tempJoinedModelAlias;

            $joinedMap[$tempJoinedModelAddr] = array(
                'Field' => $jMVal['Field'],
                'Model' => $jMVal['Model'],
                'Key' => $jMVal['Key'],
                'Alias' => $tempJoinedModelAlias,
                'Single' => !isset($jMVal['Single']) || $jMVal['Single'] ?
                    true : false,
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

        if (!empty(static::$withs)) {
            $joinModels = array_merge(static::$withs, $joinModels);
        }

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

        if ($principals = self::query(
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

                if (empty($tempJoinedKeys)) {
                    continue;
                }

                foreach ($JoinedVal['Model']::fetchInKeys(
                    $JoinedVal['Key'],
                    array_keys($tempJoinedKeys),
                    $JoinedVal['Param'],
                    0,
                    0,
                    'ASSOC'
                ) as $pKey => $pVal) {
                    if (is_object($pVal)) {
                        $JoinedVal['Data'][$pKey] = $pVal->getData();
                    } else {
                        $JoinedVal['Data'][$pKey] = $pVal;
                    }

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

                        if ($JoinedVal['Single']) {
                            if (empty(
                                $tempJoinedKeys[$pVal[$JoinedVal['Key']]][$tkJoinedKey][$JoinedVal['Alias']]
                            )) {
                                $tempJoinedKeys[$pVal[$JoinedVal['Key']]][$tkJoinedKey][$JoinedVal['Alias']] =
                                    &$JoinedVal['Data'][$pKey];
                            }
                        } else {
                            $tempJoinedKeys[$pVal[$JoinedVal['Key']]][$tkJoinedKey][$JoinedVal['Alias']][] =
                                &$JoinedVal['Data'][$pKey];
                        }
                    }
                }
            }

            return $principals;
        }

        return array();
    }

    /**
     * Import data from parent class instance
     *
     * @return bool Return true when success, false otherwise
     */
    public function import($instance)
    {
        if (!($this instanceof $instance)) {
            throw new Exception\IncompatibleImporting(
                get_class($instance),
                get_class($this)
            );

            return false;
        }

        foreach (static::getUsingFields() as $name => $ppt) {
            $this->$name = $instance->$name;
        }

        return true;
    }

    /**
     * Save current data into Database
     *
     * @return mixed Return count of affected data when success, or false otherwise
     */
    public function save()
    {
        $primaryKey = $result = $query = null;
        $sets = $changes = $fields = array();
        $changeOperator = '';
        $changesTo = 0;

        $tempNewValType = '';
        $tempNewValIsNum = false;

        if (isset($this->data[static::$primary])) {
            $fields = static::getUsingFields();

            // Must call the hook before we handle data
            if (!$this->onSave()) {
                return false;
            }

            if (isset($this->dataOriginal[static::$primary])) {
                $primaryKey = $this->dataOriginal[static::$primary];
            } else {
                $primaryKey = $this->data[static::$primary];
            }

            foreach ($this->data as $key => $val) {
                if (!isset($fields[$key])) {
                    continue;
                }

                if (isset(static::$creases[$key])) {
                    // Check if we need to Xcrease it

                    /*
                        Notice that: Xcrease use to reduce the problem caused in high concurrence condition
                        But it will not solve it.

                        To avoid hallucinate reading problem, you need to LOCK the table or row when reading
                        for updating which not supported by this ORM and it's base structure currently.

                        Use with care.
                    */
                    if (is_int($val)) {
                        $changesTo = (int)$this->dataOriginal[$key] - $val;
                    } elseif (is_float($val)) {
                        $changesTo = (float)$this->dataOriginal[$key] - $val;
                    } else {
                        throw new Exception\CreasingANonNumber($val, gettype($val));

                        return false;
                    }

                    if ($changesTo > 0) {
                        $changeOperator = '-';
                    } elseif ($changesTo < 0) {
                        $changeOperator = '+';
                    }

                    $changes[] = array(
                        'Field' => $key,
                        'Operator' => $changeOperator,
                        'Value' => abs($changesTo)
                    );
                } else {
                    // Or fine with replace
                    $sets[$key] = $val;
                }
            }

            $query = Query::from(
                static::$table,
                !static::$noParser
            )->update($fields);

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
            throw new Exception\PrimaryKeyNotSetSave();
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
        $fields = static::getUsingFields();

        // Must call the hook before we handle data
        if (!$this->onInsert()) {
            return false;
        }

        foreach ($this->data as $key => $val) {
            if (isset($fields[$key])) {
                $keys[$key] = $fields[$key];
                $data[$key] = $val;
            }
        }

        // Must returning primary key
        if ($result = Query::from(
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
            if (!$this->onDelete()) {
                return false;
            }

            if ($result = Query::from(
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
            throw new Exception\PrimaryKeyNotSetDelete();
        }

        return false;
    }

    /**
     * Hook for data insert
     *
     * @return bool
     */
    protected function onInsert()
    {
        return true;
    }

    /**
     * Hook for data update
     *
     * @return bool
     */
    protected function onSave()
    {
        return true;
    }

    /**
     * Hook for data delete
     *
     * @return bool
     */
    protected function onDelete()
    {
        return true;
    }
}
