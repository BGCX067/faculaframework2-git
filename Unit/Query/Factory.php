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

namespace Facula\Unit\Query;

class Factory extends \Facula\Base\Factory\Adapter implements Implement
{
    private static $inited = false;

    protected static $adapters = array(
        'mysql' => 'Facula\Unit\Query\Adapter\Mysql',
        'pgsql' => 'Facula\Unit\Query\Adapter\Pgsql',
    );

    private static $pdoDataTypes = array(
        'BOOL' => \PDO::PARAM_BOOL,
        'NULL' => \PDO::PARAM_NULL,
        'INT' => \PDO::PARAM_INT,
        'STR' => \PDO::PARAM_STR,
        'LOB' => \PDO::PARAM_LOB,
    );

    private static $queryOperators = array(
        '=' => '=',
        '<' => '<',
        '>' => '>',
        '<>' => '<>',
        '<=>' => '<=>',
        '<=' => '<=',
        '>=' => '>=',
        'IS' => 'IS',
        'IS NOT' => 'IS NOT',
        'LIKE' => 'LIKE',
        'NOT LIKE' => 'NOT LIKE',
        'BETWEEN' => 'BETWEEN',
        'NOT BETWEEN' => 'NOT BETWEEN',
        'IN' => 'IN',
        'NOT IN' => 'NOT IN',
        'IS NULL' => 'IS NULL',
        'IS NOT NULL' => 'IS NOT NULL',
    );

    private static $logicOperators = array(
        'AND' => 'AND',
        'OR' => 'OR',
    );

    private static $orderOperators = array(
        'DESC' => 'DESC',
        'ASC' => 'ASC',
    );

    private static $lastStatement = array(
        'Statement' => null,
        'Identity' => '',
    );

    private static $parsers = array();

    private $connection = null;
    private $adapter = null;

    protected $query = array();

    /*
    private $query = array(
        'Action' => 'Select', // SQL Syntax
        'Type' => 'Write|Read', // Query Type
        'From' => '', // Table name
        'Parser' => false, // Enable or disable auto parser
        'Required' => array('Fields', 'Where', 'Group', 'Having', 'Order', 'Limit', 'Values', 'Sets'), // Required fields of this array for param validation
        'Fields' => array('STR', 'INT', 'BOOL', 'PARSERS'), // Fields => Types
        'Where' => array(), // Where conditions
        'Group' => array(), // Group by
        'Having' => array(), // Group having
        'Order' => array(), // Order by
        'Limit' => array(), // Limit by

        'Values' => array(), // Values for insert

        'Sets' => array(), // Sets for update
    );
    */

    private $dataIndex = 0;
    protected $dataMap = array();

    public static function from($tableName, $autoParse = false)
    {
        return new self($tableName, $autoParse);
    }

    public static function addAutoParser($name, $type, \Closure $parser)
    {
        if (!isset(static::$parsers[$name][$type])) {
            switch ($type) {
                case 'Reader':
                    static::$parsers[$name]['Reader'] = $parser;

                    return true;
                    break;

                case 'Writer':
                    static::$parsers[$name]['Writer'] = $parser;

                    return true;
                    break;

                default:
                    \Facula\Framework::core('debug')->exception('ERROR_QUERY_PARSER_TYPE_INVALID|' . $type, 'query', true);
                    break;
            }
        }

        return false;
    }

    private static function selfInit()
    {
        if (self::$inited) {
            return true;
        } else {
            self::$inited = true;
        }

        static::addAutoParser('Serialized', 'Reader', function ($data) {
            return $data ? unserialize($data) : '';
        });

        static::addAutoParser('Serialized', 'Writer', function ($data) {
            return $data ? serialize($data) : '';
        });

        static::addAutoParser('Trimed', 'Reader', function ($data) {
            return trim($data);
        });

        static::addAutoParser('Trimed', 'Writer', function ($data) {
            return trim($data);
        });

        static::addAutoParser('Integer', 'Reader', function ($data) {
            return (int)($data);
        });

        static::addAutoParser('Integer', 'Writer', function ($data) {
            return (int)($data);
        });

        static::addAutoParser('Float', 'Reader', function ($data) {
            return (float)($data);
        });

        static::addAutoParser('Float', 'Writer', function ($data) {
            return (float)($data);
        });

        return true;
    }

    private function __construct($tableName, $autoParse = false)
    {
        $this->query['From'] = $tableName;
        $this->query['Parser'] = $autoParse ? true : false;

        self::selfInit();

        return true;
    }

    // Select
    public function select($fields)
    {
        if (!isset($this->query['Action'])) {
            $this->query['Action'] = 'select';
            $this->query['Type'] = 'Read';
            $this->query['Required'] = array();

            if ($this->saveFields($fields)) {
                // Enable where
                $this->query['Where'] = array();

                // Enable group
                $this->query['Group'] = array();

                // Order by
                $this->query['Order'] = array();

                // Limit
                $this->query['Limit'] = array();

                return $this;
            }
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_ACTION_ASSIGNED|' . $this->query['Action'], 'query', true);
        }

        return false;
    }

    // Insert
    public function insert($fields)
    {
        if (!isset($this->query['Action'])) {
            $this->query['Action'] = 'insert';
            $this->query['Type'] = 'Write';
            $this->query['Required'] = array('Fields', 'Values');

            if ($this->saveFields($fields)) {
                // Enable values
                $this->query['Values'] = array();

                return $this;
            }
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_ACTION_ASSIGNED|' . $this->query['Action'], 'query', true);
        }

        return false;
    }

    // Update
    public function update($fields)
    {
        if (!isset($this->query['Action'])) {
            $this->query['Action'] = 'update';
            $this->query['Type'] = 'Write';
            $this->query['Required'] = array('Sets');

            // Save fields
            if ($this->saveFields($fields)) {
                // Enable sets
                $this->query['Sets'] = array();

                // Enable Where
                $this->query['Where'] = array();

                // Enable Order
                $this->query['Order'] = array();

                // Enable Limit
                $this->query['Limit'] = array();

                return $this;
            }
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_ACTION_ASSIGNED|' . $this->query['Action'], 'query', true);
        }

        return false;
    }

    // Delete
    public function delete($fields)
    {
        if (!isset($this->query['Action'])) {
            $this->query['Action'] = 'delete';
            $this->query['Type'] = 'Write';
            $this->query['Required'] = array('Where');

            // Save fields
            if ($this->saveFields($fields)) {
                // Enable Where
                $this->query['Where'] = array();

                // Enable Order
                $this->query['Order'] = array();

                // Enable Limit
                $this->query['Limit'] = array();

                return $this;
            }
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_ACTION_ASSIGNED|' . $this->query['Action'], 'query', true);
        }

        return false;
    }

    // Save data and data type to data map for bindValue
    protected function saveFields($fields)
    {
        $fieldTypes = array();

        if (is_array($fields)) {
            foreach ($fields as $fieldName => $fieldType) {
                if ((is_array($fieldType) && ($fieldTypes = $fieldType)) || ($fieldTypes = explode(' ', $fieldType))) {
                    foreach ($fieldTypes as $type) {
                        if (isset(static::$pdoDataTypes[$type])) {
                            $this->query['Fields'][$fieldName] = $type;
                        }

                        if (isset(static::$parsers[$type])) {
                            $this->query['FieldParsers'][$fieldName][] = $type;
                        }
                    }
                }

                if (!isset($this->query['Fields'][$fieldName]) || !$this->query['Fields'][$fieldName]) {
                    $this->query['Fields'][$fieldName] = 'STR';
                }
            }

            return true;
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_SAVEFIELD_FIELDS_INVALID', 'query', true);
        }

        return false;
    }

    protected function saveValue($value, $forField)
    {
        $saveParser = null;

        $dataKey = ':' . $this->dataIndex++;

        if (isset($this->query['Fields'][$forField])) {
            if (isset(static::$pdoDataTypes[$this->query['Fields'][$forField]])) {
                $this->dataMap[$dataKey]['Type'] = static::$pdoDataTypes[$this->query['Fields'][$forField]]; // Type
                $this->dataMap[$dataKey]['Value'] = $value;

                if ($this->query['Parser'] && isset($this->query['FieldParsers'][$forField])) {
                    foreach (array_reverse($this->query['FieldParsers'][$forField]) as $parserType) {
                        if (isset(static::$parsers[$parserType]['Writer'])) {
                            $saveParser = static::$parsers[$parserType]['Writer'];

                            $this->dataMap[$dataKey]['Value'] = $saveParser($this->dataMap[$dataKey]['Value']); // With parser
                        } else {
                            \Facula\Framework::core('debug')->exception('ERROR_QUERY_SAVEVALUE_PARSER_WRITER_NOTSET|' . $forField, 'query', true);
                        }
                    }
                }

                return $dataKey;
            } else {
                \Facula\Framework::core('debug')->exception('ERROR_QUERY_SAVEVALUE_TYPE_UNKNOWN|' . $this->query['Fields'][$forField], 'query', true);
            }
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_SAVEVALUE_FIELD_UNKNOWN|' . $forField, 'query', true);
        }

        return false;
    }

    // Conditions like where and having
    private function condition($logic, $fieldName, $operator, $value)
    {
        $params = array();

        if (!isset(static::$queryOperators[$operator])) {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_CONDITION_UNKNOWN_OPERATOR|' . $operator, 'query', true);

            return false;
        }

        switch (static::$queryOperators[$operator]) {
            case '=':
                $params = array(
                    'Operator' => '=',
                    'Value' => $this->saveValue($value, $fieldName),
                );
                break;

            case '>':
                $params = array(
                    'Operator' => '>',
                    'Value' => $this->saveValue($value, $fieldName),
                );
                break;

            case '<':
                $params = array(
                    'Operator' => '<',
                    'Value' => $this->saveValue($value, $fieldName),
                );
                break;

            case '<>':
                $params = array(
                    'Operator' => '<>',
                    'Value' => $this->saveValue($value, $fieldName),
                );
                break;

            case '<=>':
                $params = array(
                    'Operator' => '<=>',
                    'Value' => $this->saveValue($value, $fieldName),
                );
                break;

            case '<=':
                $params = array(
                    'Operator' => '<=',
                    'Value' => $this->saveValue($value, $fieldName),
                );
                break;

            case '>=':
                $params = array(
                    'Operator' => '>=',
                    'Value' => $this->saveValue($value, $fieldName),
                );
                break;

            case 'IS':
                $params = array(
                    'Operator' => 'IS',
                    'Value' => $this->saveValue($value, $fieldName),
                );
                break;

            case 'IS NOT':
                $params = array(
                    'Operator' => 'IS NOT',
                    'Value' => $this->saveValue($value, $fieldName),
                );
                break;

            case 'LIKE':
                $params = array(
                    'Operator' => 'LIKE',
                    'Value' => $this->saveValue($value, $fieldName),
                );
                break;

            case 'NOT LIKE':
                $params = array(
                    'Operator' => 'NOT LIKE',
                    'Value' => $this->saveValue($value, $fieldName),
                );
                break;

            case 'BETWEEN':
                if (is_array($value) && isset($value[0]) && isset($value[1])) {
                    $params = array(
                        'Operator' => 'BETWEEN',
                        'Value' => array(
                            $this->saveValue($value[0], $fieldName),
                            $this->saveValue($value[1], $fieldName)
                        ),
                    );
                } else {
                    \Facula\Framework::core('debug')->exception('ERROR_QUERY_CONDITION_OPERATOR_INVALID_PARAM|' . $operator, 'query', true);

                    return false;
                }
                break;

            case 'NOT BETWEEN':
                if (is_array($value) && isset($value[0]) && isset($value[1])) {
                    $params = array(
                        'Operator' => 'NOT BETWEEN',
                        'Value' => array(
                            $this->saveValue($value[0], $fieldName),
                            $this->saveValue($value[1], $fieldName)
                        ),
                    );
                } else {
                    \Facula\Framework::core('debug')->exception('ERROR_QUERY_CONDITION_OPERATOR_INVALID_PARAM|' . $operator, 'query', true);

                    return false;
                }
                break;

            case 'IN':
                if (is_array($value) && !empty($value)) {
                    $params['Operator'] = 'IN';

                    foreach ($value as $val) {
                        $params['Value'][] = $this->saveValue($val, $fieldName);
                    }

                } else {
                    \Facula\Framework::core('debug')->exception('ERROR_QUERY_CONDITION_OPERATOR_INVALID_PARAM|' . $operator, 'query', true);

                    return false;
                }
                break;

            case 'NOT IN':
                if (is_array($value) && !empty($value)) {
                    $params['Operator'] = 'NOT IN';

                    foreach ($value as $val) {
                        $params['Value'][] = $this->saveValue($val, $fieldName);
                    }

                } else {
                    \Facula\Framework::core('debug')->exception('ERROR_QUERY_CONDITION_OPERATOR_INVALID_PARAM|' . $operator, 'query', true);

                    return false;
                }
                break;

            case 'IS NULL':
                $params = array(
                    'Operator' => 'IS NULL',
                    'Value' => '',
                );
                break;

            case 'IS NOT NULL':
                $params = array(
                    'Operator' => 'IS NULL',
                    'Value' => '',
                );
                break;
        }

        if (isset(static::$logicOperators[$logic])) {
            $params['Logic'] = static::$logicOperators[$logic];
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_CONDITION_UNKNOWN_LOGIC|' . $logic, 'query', true);
            return false;
        }

        $condition = array(
            'Field' => $fieldName,
            'Operator' => $params['Operator'],
            'Value' => $params['Value'],
            'Logic' => $params['Logic'],
        );

        return $condition;
    }

    // Where
    public function where($logic, $fieldName, $operator, $value)
    {
        if (!isset($this->query['Where'])) {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_WHERE_NOT_SUPPORTED', 'query', true);

            return false;
        }

        if (!isset($this->query['Fields'][$fieldName])) {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_WHERE_FIELD_UNKOWN|' . $fieldName, 'query', true);

            return false;
        }

        if ($this->query['Where'][] = $this->condition($logic, $fieldName, $operator, $value)) {
            return $this;
        }

        return false;
    }

    // Having
    public function having($logic, $fieldName, $operator, $value)
    {
        if (!isset($this->query['Having'])) {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_HAVING_NOT_SUPPORTED', 'query', true);

            return false;
        }

        if (!isset($this->query['Fields'][$fieldName])) {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_HAVING_FIELD_UNKOWN|' . $fieldName, 'query', true);

            return false;
        }

        if ($this->query['Having'][] = $this->condition($logic, $fieldName, $operator, $value)) {
            return $this;
        }

        return false;
    }

    // Group
    public function group($fieldName)
    {
        if (!isset($this->query['Group'])) {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_GROUP_NOT_SUPPORTED', 'query', true);

            return false;
        }

        if (!isset($this->query['Fields'][$fieldName])) {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_GROUP_FIELD_UNKOWN|' . $fieldName, 'query', true);

            return false;
        }

        $this->query['Group'][] = $fieldName;

        // Enable Having for group by
        $this->query['Having'] = array();

        return $this;
    }

    // Order
    public function order($fieldName, $sort)
    {
        if (!isset($this->query['Order'])) {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_ORDER_NOT_SUPPORTED', 'query', true);

            return false;
        }

        if (!isset(static::$orderOperators[$sort])) {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_ORDER_SORTOPERATOR_UNKOWN|' . $sort, 'query', true);

            return false;
        }

        if (!isset($this->query['Fields'][$fieldName])) {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_ORDER_FIELD_UNKOWN|' . $fieldName, 'query', true);

            return false;
        }

        $this->query['Order'][] = array(
            'Field' => $fieldName,
            'Sort' => static::$orderOperators[$sort],
        );

        return $this;
    }

    // Values
    public function value($value)
    {
        $tempValueData = array();

        if (isset($this->query['Values'])) {
            foreach ($this->query['Fields'] as $field => $type) {
                if (isset($value[$field])) {
                    $tempValueData[] = $this->saveValue($value[$field], $field);
                } else {
                    \Facula\Framework::core('debug')->exception('ERROR_QUERY_VALUES_FIELD_NOTSET|' . $field, 'query', true);
                }
            }

            $this->query['Values'][] = $tempValueData;

            return $this;
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_VALUES_NOT_SUPPORTED', 'query', true);
        }

        return false;
    }

    // Sets
    public function set($values)
    {
        if (isset($this->query['Sets'])) {

            foreach ($values as $field => $value) {
                $this->query['Sets'][] = array(
                    'Field' => $field,
                    'Value' => $this->saveValue($value, $field),
                );
            }

            return $this;
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_SETS_NOT_SUPPORTED', 'query', true);
        }

        return false;
    }

    // Limit
    public function limit($offset, $distance)
    {
        if (isset($this->query['Limit'])) {
            if (empty($this->query['Limit'])) {

                $this->query['Limit'] = array(
                    'Offset' => (int)($offset),
                    'Distance' => (int)($distance),
                );

                return $this;
            } else {
                \Facula\Framework::core('debug')->exception('ERROR_QUERY_LIMIT_ALREADY_ASSIGNED', 'query', true);
            }
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_LIMIT_NOT_SUPPORTED', 'query', true);
        }

        return false;
    }

    // Task Preparers
    private function getPDOConnection()
    {
        if ($this->connection = \Facula\Framework::core('pdo')->getConnection(array('Table' => $this->query['From'], 'Operation' => $this->query['Type']))) {
            return $this->connection;
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_PDO_CONNECTION_FAILED', 'query', true);
        }

        return false;
    }

    private function getDBAdapter()
    {
        $adapterName = '';
        $adapter = null;

        if (!$this->adapter) {
            if (isset($this->connection->_connection['Driver'])) {
                $adapterName = static::getAdapter($this->connection->_connection['Driver']);

                if (class_exists($adapterName)) {
                    $adapter = new $adapterName($this->connection->_connection['Prefix'] . $this->query['From'], $this->query);

                    if ($adapter instanceof AdapterImplement) {
                        return ($this->adapter = $adapter);
                    } else {
                        \Facula\Framework::core('debug')->exception('ERROR_QUERY_BUILDER_INTERFACE_INVALID', 'query', true);
                    }

                } else {
                    \Facula\Framework::core('debug')->exception('ERROR_QUERY_BUILDER_DRIVER_NOTSUPPORTED|' . $this->connection->_connection['Driver'], 'query', true);
                }
            } else {
                \Facula\Framework::core('debug')->exception('ERROR_QUERY_SQL_BUILDER_NEEDS_CONNECTION', 'query', true);
            }
        } else {
            return $this->adapter;
        }

        return null;
    }

    protected function exec($requiredQueryParams = array())
    {
        $sql = $sqlID = '';
        $statement = null;
        $matchedParams = array();

        if (isset($this->query['Action'])) {
            // A little check before we actually do query. We need to know if our required data in $this->query has been filled or we may make big mistake.
            foreach ($requiredQueryParams as $param) {
                if (empty($this->query[$param])) {
                    \Facula\Framework::core('debug')->exception('ERROR_QUERY_PREPARE_PARAM_REQUIRED|' . $param, 'query', true);

                    return false;
                    break;
                }
            }

            if ($this->getPDOConnection() && $this->getDBAdapter()) {
                // Build SQL Syntax
                if ($sql = $this->adapter->build()) {
                    try {
                        // Ensure the connection we used still the last one
                        $sqlID = crc32($sql . $this->connection->_connection['LstConnected']);

                        // Prepare statement
                        if (static::$lastStatement['Identity'] == $sqlID) {
                            $statement = static::$lastStatement['Statement'];
                        } else {
                            static::$lastStatement['Identity'] = $sqlID;
                            $statement = (static::$lastStatement['Statement'] = $this->connection->prepare($sql));
                        }

                        if ($statement) {
                            // Search string and set key
                            if (preg_match_all('/(:[0-9]+)/', $sql, $matchedParams)) {

                                // Use key to search in datamap, and bind the value and types
                                foreach ($matchedParams[0] as $paramKey) {
                                    if (isset($this->dataMap[$paramKey])) {
                                        if (!$statement->bindValue($paramKey, $this->dataMap[$paramKey]['Value'], $this->dataMap[$paramKey]['Type'])) {
                                            \Facula\Framework::core('debug')->exception('ERROR_QUERY_PREPARE_PARAM_BINDVALUE_FAILED|' . ($paramKey . ' = (' . $this->dataMap[$paramKey]['Type'] . ')' . $this->dataMap[$paramKey]['Value']), 'query', true);
                                        }
                                    } else {
                                        \Facula\Framework::core('debug')->exception('ERROR_QUERY_PREPARE_PARAM_NOTSET|' . $paramKey, 'query', true);
                                    }
                                }
                            }

                            // We got need this
                            $statement->connection = &$this->connection;

                            if ($statement->execute()) {
                                return $statement;
                            }
                        }
                    } catch (PDOException $e) {
                        \Facula\Framework::core('debug')->exception('ERROR_QUERY_PREPARE_FAILED|' . $e->getMessage(), 'query', true);
                    }
                }
            }
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_PREPARE_MUST_INITED', 'query', true);
        }

        return false;
    }

    // Operator: Query performers
    public function get()
    {
        $result = array();

        if ($this->limit(0, 1) && ($result = $this->fetch())) {
            return $result[0];
        }

        return false;
    }

    public function fetch($mode = 'ASSOC', $argument = null)
    {
        $sql = '';
        $statement = $readParser = null;
        $result = $readParsers = array();

        $pdoFetchStyle = array(
            'ASSOC' => \PDO::FETCH_ASSOC,
            'BOUND' => \PDO::FETCH_BOUND,
            'INTO' => \PDO::FETCH_INTO,
            'NUM' => \PDO::FETCH_NUM,
            'LAZY' => \PDO::FETCH_LAZY,
            'OBJ' => \PDO::FETCH_OBJ,
            'BOTH' => \PDO::FETCH_BOTH,
            'COLUMN' => \PDO::FETCH_COLUMN,
            'CLASS' => \PDO::FETCH_CLASS,
            'CLASSLATE' => \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE,
            'FUNC' => \PDO::FETCH_FUNC,
        );

        if (isset($this->query['Action']) && $this->query['Type'] == 'Read') {
            if ($statement = $this->exec($this->query['Required'])) {
                try {
                    if (isset($pdoFetchStyle[$mode])) {
                        if ($argument !== null) {
                            $statement->setFetchMode($pdoFetchStyle[$mode], $argument);
                        } else {
                            $statement->setFetchMode($pdoFetchStyle[$mode]);
                        }

                        if ($result = $this->adapter->fetch($statement)) {
                            if ($this->query['Parser'] && isset($this->query['FieldParsers'])) {
                                foreach ($result as $statKey => $statVal) {
                                    foreach ($this->query['FieldParsers'] as $field => $parsers) {
                                        if (isset($statVal[$field])) {
                                            foreach ($parsers as $parser) {
                                                if (isset(static::$parsers[$parser]['Reader'])) {
                                                    if (!isset($readParsers[$parser])) {
                                                        $readParsers[$parser] = static::$parsers[$parser]['Reader'];
                                                    }

                                                    $statVal[$field] = $readParsers[$parser]($statVal[$field]);
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            return $result;
                        }
                    } else {
                        \Facula\Framework::core('debug')->exception('ERROR_QUERY_FETCH_UNKNOWN_METHOD|' . $mode, 'query', true);
                    }
                } catch (PDOException $e) {
                    \Facula\Framework::core('debug')->exception('ERROR_QUERY_FETCH_FAILED|' . $e->getMessage(), 'query', true);
                }
            }
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_FETCH_NOT_SUPPORTED', 'query', true);
        }

        return false;
    }

    public function save($param = null)
    {
        $statement = null;
        $seqFullName = '';

        if (isset($this->query['Action']) && $this->query['Type'] == 'Write') {
            if ($statement = $this->exec($this->query['Required'])) {
                try {

                    switch ($this->query['Action']) {
                        case 'insert':
                            return $this->adapter->insert($statement, $param);
                            break;

                        case 'update':
                            return $this->adapter->update($statement);
                            break;

                        case 'delete':
                            return $this->adapter->delete($statement);
                            break;

                        default:
                            \Facula\Framework::core('debug')->exception('ERROR_QUERY_SAVE_METHOD_UNSUPPORTED|' . $this->query['Action'], 'query', true);
                            break;
                    }

                } catch (PDOException $e) {
                    \Facula\Framework::core('debug')->exception('ERROR_QUERY_SAVE_FAILED|' . $e->getMessage(), 'query', true);
                }
            }
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_QUERY_SAVE_NOT_SUPPORTED', 'query', true);
        }
    }
}
