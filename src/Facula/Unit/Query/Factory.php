<?php

/**
 * Query Operator
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

namespace Facula\Unit\Query;

use Facula\Unit\Query\Exception as Exception;
use Facula\Base\Factory\Operator as Base;

/**
 * Query Operator
 */
class Factory extends Base implements Implement
{
    /** Tag to anti reinitializing */
    private static $inited = false;

    /** Default operators */
    protected static $operators = array(
        'mysql' => 'Facula\Unit\Query\Operator\MySQL',
        'pgsql' => 'Facula\Unit\Query\Operator\PgSQL',
    );

    /** Shortcut for PDO data types */
    private static $pdoDataTypes = array(
        'BOOL' => \PDO::PARAM_BOOL,
        'NULL' => \PDO::PARAM_NULL,
        'INT' => \PDO::PARAM_INT,
        'STR' => \PDO::PARAM_STR,
        'LOB' => \PDO::PARAM_LOB,
    );

    /** Allowed query operators */
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

        // Specials
        'LIKE %' => 'LIKE',
        '% LIKE' => 'LIKE',
        '% LIKE %' => 'LIKE',
        'NOT LIKE %' => 'NOT LIKE',
        '% NOT LIKE' => 'NOT LIKE',
        '% NOT LIKE %' => 'NOT LIKE',
    );

    /** Allowed logic operators */
    private static $logicOperators = array(
        'AND' => 'AND',
        'OR' => 'OR',
    );

    /** Allowed order operators */
    private static $orderOperators = array(
        'DESC' => 'DESC',
        'ASC' => 'ASC',
    );

    /** Allowed math operators */
    private static $mathOperators = array(
        '+' => '+',
        '-' => '-',
        '*' => '*',
        '/' => '/',
    );

    /** Last statement cache */
    private static $lastStatement = array(
        'Statement' => null,
        'Identity' => '',
    );

    /** Query counter */
    protected static $queries = 0;

    /** Registered parsers */
    private static $parsers = array();

    /** Active connection for current query */
    private $connection = null;

    /** Active operator for current query */
    private $operator = null;

    /** Query information */
    protected $query = array();

    /*
    private $query = array(
        'Action' => 'Select', // SQL Syntax
        'Type' => 'Write|Read', // Query Type
        'From' => '', // Table name
        'Parser' => false, // Enable or disable auto parser
        'Required' => array(
            'Fields',
            'Where',
            'Group',
            'Having',
            'Order',
            'Limit',
            'Values',
            'Sets'
        ), // Required fields of this array for param validation
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

    /** Data index for saved data */
    private $dataIndex = 0;

    /** Map for saved data */
    protected $dataMap = array();

    /**
     * Start to build a new query
     *
     * @param string $tableName Table name for query
     * @param bool $autoParse Enable or disable the auto parser for this query
     *
     * @return object Instance of query instance
     */
    public static function from($tableName, $autoParse = false)
    {
        return new self($tableName, $autoParse);
    }

    /**
     * Get how many queries has been executed
     *
     * @return integer The number of queries
     */
    public static function countQueries()
    {
        return static::$queries;
    }

    /**
     * Add auto parser into the class
     *
     * @param string $name Name of the parser
     * @param string $type Operation type of the parser
     * @param closure $parser The parser itself in Closure
     *
     * @return bool Return true when succeed, false for fail
     */
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
                    throw new Exception\AutoParserTypeInvalid(
                        $type
                    );
                    break;
            }
        }

        return false;
    }

    /**
     * Do self initialize
     *
     * @return bool Always return true
     */
    private static function selfInit()
    {
        static::addAutoParser('Serialized', 'Reader', function ($data) {
            return $data ? unserialize($data) : '';
        });

        static::addAutoParser('Serialized', 'Writer', function ($data) {
            return $data ? serialize($data) : '';
        });

        static::addAutoParser('Trimmed', 'Reader', function ($data) {
            return trim($data);
        });

        static::addAutoParser('Trimmed', 'Writer', function ($data) {
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

        static::addAutoParser('Lower', 'Reader', function ($data) {
            return strtolower($data);
        });

        static::addAutoParser('Lower', 'Writer', function ($data) {
            return strtolower($data);
        });

        static::addAutoParser('Upper', 'Reader', function ($data) {
            return strtoupper($data);
        });

        static::addAutoParser('Upper', 'Writer', function ($data) {
            return strtoupper($data);
        });

        return true;
    }

    /**
     * Constructor
     *
     * @param string $tableName Table name to query
     * @param bool $autoParse Enable or disable the auto parser
     *
     * @return void
     */
    private function __construct($tableName, $autoParse = false)
    {
        $this->query['From'] = $tableName;
        $this->query['Parser'] = $autoParse ? true : false;

        if (!self::$inited && $this->query['Parser']) {
            self::$inited = true;

            self::selfInit();
        }
    }

    /**
     * Declare fields and set query type to SELECT for operating
     *
     * @param array $fields Fields in Name => Type pair
     *
     * @return mixed Return current object when succeed, or false otherwise
     */
    public function select(array $fields)
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
            throw new Exception\ActionAlreadyAssigned(
                $this->query['Action']
            );
        }

        return false;
    }

    /**
     * Declare fields and set query type to INSERT for operating
     *
     * @param array $fields Fields in Name => Type pair
     *
     * @return mixed Return current object when succeed, or false otherwise
     */
    public function insert(array $fields)
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
            throw new Exception\ActionAlreadyAssigned(
                $this->query['Action']
            );
        }

        return false;
    }

    /**
     * Declare fields and set query type to UPDATE for operating
     *
     * @param array $fields Fields in Name => Type pair
     *
     * @return mixed Return current object when succeed, or false otherwise
     */
    public function update(array $fields)
    {
        if (!isset($this->query['Action'])) {
            $this->query['Action'] = 'update';
            $this->query['Type'] = 'Write';
            $this->query['Required'] = array(array('Sets', 'Changes'));

            // Save fields
            if ($this->saveFields($fields)) {
                // Enable sets
                $this->query['Sets'] = array();

                // Enable Changes
                $this->query['Changes'] = array();

                // Enable Where
                $this->query['Where'] = array();

                // Enable Order
                $this->query['Order'] = array();

                // Enable Limit
                $this->query['Limit'] = array();

                return $this;
            }
        } else {
            throw new Exception\ActionAlreadyAssigned(
                $this->query['Action']
            );
        }

        return false;
    }

    /**
     * Declare fields and set query type to DELETE for operating
     *
     * @param array $fields Fields in Name => Type pair
     *
     * @return mixed Return current object when succeed, or false otherwise
     */
    public function delete(array $fields)
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
            throw new Exception\ActionAlreadyAssigned(
                $this->query['Action']
            );
        }

        return false;
    }

    /**
     * Save fields into current class
     *
     * @param array $fields Fields in Name => Type pair
     *
     * @return bool Always true
     */
    protected function saveFields(array $fields)
    {
        $fieldTypes = array();

        foreach ($fields as $fieldName => $fieldType) {
            if ((is_array($fieldType) && ($fieldTypes = $fieldType))
                || ($fieldTypes = explode(' ', $fieldType))) {
                foreach ($fieldTypes as $type) {
                    if (isset(static::$pdoDataTypes[$type])) {
                        $this->query['Fields'][$fieldName] = $type;
                    }

                    if (isset(static::$parsers[$type])) {
                        $this->query['FieldParsers'][$fieldName][] = $type;
                    }
                }
            }

            if (!isset($this->query['Fields'][$fieldName])
                || !$this->query['Fields'][$fieldName]) {
                $this->query['Fields'][$fieldName] = 'STR';
            }
        }

        return true;
    }

    /**
     * Save value into class
     *
     * @param string $value The value of the field
     * @param string $forField Name of the field
     * @param string $escapeMode Non-safety-related escape mode
     *
     * @return mixed Return current object when succeed, or false otherwise
     */
    protected function saveValue($value, $forField, $escapeMode = '')
    {
        $saveParser = null;

        $dataKey = ':' . $this->dataIndex++;

        if (isset($this->query['Fields'][$forField])) {
            if (isset(static::$pdoDataTypes[$this->query['Fields'][$forField]])) {
                $this->dataMap[$dataKey]['Type'] =
                    static::$pdoDataTypes[$this->query['Fields'][$forField]]; // Type

                $this->dataMap[$dataKey]['Value'] = $value;

                $this->dataMap[$dataKey]['Escape'] = $escapeMode;

                if ($this->query['Parser'] && isset($this->query['FieldParsers'][$forField])) {
                    foreach (array_reverse(
                        $this->query['FieldParsers'][$forField]
                    ) as $parserType) {

                        if (isset(static::$parsers[$parserType]['Writer'])) {
                            $saveParser = static::$parsers[$parserType]['Writer'];

                            $this->dataMap[$dataKey]['Value'] = $saveParser(
                                $this->dataMap[$dataKey]['Value']
                            ); // With parser
                        } else {
                            throw new Exception\WriterAutoParserNotSet(
                                $parserType,
                                $forField
                            );
                        }

                    }
                }

                return $dataKey;
            } else {
                throw new Exception\UnknownDataTypeForSaving(
                    $this->query['Fields'][$forField]
                );
            }
        } else {
            throw new Exception\UnknownFieldForSaving(
                $forField
            );
        }

        return false;
    }

    /**
     * Make condition operating configuration
     *
     * @param string $logic Logic to previous condition
     * @param string $fieldName Field name
     * @param string $operator Operator for the field name and value
     * @param string $value The value for the field
     *
     * @return mixed Return condition configuration when succeed, or false for failed
     */
    private function condition($logic, $fieldName, $operator, $value)
    {
        $params = array();

        if (!isset(static::$queryOperators[$operator])) {
            throw new Exception\UnknownOperatorForCondition(
                $operator
            );

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
                switch ($operator) {
                    case 'LIKE %':
                        $params = array(
                            'Operator' => 'LIKE',
                            'Value' => $this->saveValue($value, $fieldName, 'LIKE %'),
                        );
                        break;

                    case '% LIKE':
                        $params = array(
                            'Operator' => 'LIKE',
                            'Value' => $this->saveValue($value, $fieldName, '% LIKE'),
                        );
                        break;

                    case '% LIKE %':
                        $params = array(
                            'Operator' => 'LIKE',
                            'Value' => $this->saveValue($value, $fieldName, '% LIKE %'),
                        );
                        break;

                    default:
                        $params = array(
                            'Operator' => 'LIKE',
                            'Value' => $this->saveValue($value, $fieldName),
                        );
                        break;
                }
                break;

            case 'NOT LIKE':
                switch ($operator) {
                    case 'NOT LIKE %':
                        $params = array(
                            'Operator' => 'NOT LIKE',
                            'Value' => $this->saveValue($value, $fieldName, 'LIKE %'),
                        );
                        break;

                    case '% NOT LIKE':
                        $params = array(
                            'Operator' => 'NOT LIKE',
                            'Value' => $this->saveValue($value, $fieldName, '% LIKE'),
                        );
                        break;

                    case '% NOT LIKE %':
                        $params = array(
                            'Operator' => 'LIKE',
                            'Value' => $this->saveValue($value, $fieldName, '% LIKE %'),
                        );
                        break;

                    default:
                        $params = array(
                            'Operator' => 'NOT LIKE',
                            'Value' => $this->saveValue($value, $fieldName),
                        );
                        break;
                }
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
                    throw new Exception\InvaildOperatorParameterForCondition(
                        $operator
                    );

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
                    throw new Exception\InvaildOperatorParameterForCondition(
                        $operator
                    );

                    return false;
                }
                break;

            case 'IN':
                if (is_array($value)) {
                    if (empty($value)) {
                        return false;
                    }

                    $params['Operator'] = 'IN';

                    foreach ($value as $val) {
                        $params['Value'][] = $this->saveValue($val, $fieldName);
                    }
                } else {
                    throw new Exception\InvaildOperatorParameterForCondition(
                        $operator
                    );

                    return false;
                }
                break;

            case 'NOT IN':
                if (is_array($value)) {
                    if (empty($value)) {
                        return false;
                    }

                    $params['Operator'] = 'NOT IN';

                    foreach ($value as $val) {
                        $params['Value'][] = $this->saveValue($val, $fieldName);
                    }
                } else {
                    throw new Exception\InvaildOperatorParameterForCondition(
                        $operator
                    );

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
            throw new Exception\UnknownLogicOperatorForCondition(
                $logic
            );

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

    /**
     * Make WHERE operating configuration
     *
     * @param string $logic Logic to previous condition
     * @param string $fieldName Field name
     * @param string $operator Operator for the field name and value
     * @param string $value The value for the field
     *
     * @return mixed Return current object when succeed, or false otherwise
     */
    public function where($logic, $fieldName, $operator, $value)
    {
        $condition = array();

        if (!isset($this->query['Where'])) {
            throw new Exception\WhereNotSupported();

            return false;
        }

        if (!isset($this->query['Fields'][$fieldName])) {
            throw new Exception\WhereFieldUnknown($fieldName);

            return false;
        }

        if ($condition = $this->condition(
            $logic,
            $fieldName,
            $operator,
            $value
        )) {
            $this->query['Where'][] = $condition;

            return $this;
        }

        return false;
    }

    /**
     * Make HAVING operating configuration
     *
     * @param string $logic Logic to previous condition
     * @param string $fieldName Field name
     * @param string $operator Operator for the field name and value
     * @param string $value The value for the field
     *
     * @return mixed Return current object when succeed, or false otherwise
     */
    public function having($logic, $fieldName, $operator, $value)
    {
        $condition = array();

        if (!isset($this->query['Having'])) {
            throw new Exception\HavingNotSupported();

            return false;
        }

        if (!isset($this->query['Fields'][$fieldName])) {
            throw new Exception\HavingFieldUnknown($fieldName);

            return false;
        }

        if ($condition = $this->condition(
            $logic,
            $fieldName,
            $operator,
            $value
        )) {
            $this->query['Having'][] = $condition;

            return $this;
        }

        return false;
    }

    /**
     * Make GROUP operating configuration
     *
     * @param string $fieldName Field name
     *
     * @return mixed Return current object when succeed, or false otherwise
     */
    public function group($fieldName)
    {
        if (!isset($this->query['Group'])) {
            throw new Exception\GroupNotSupported();

            return false;
        }

        if (!isset($this->query['Fields'][$fieldName])) {
            throw new Exception\GroupFieldUnknown($fieldName);

            return false;
        }

        $this->query['Group'][] = $fieldName;

        // Enable Having for group by
        $this->query['Having'] = array();

        return $this;
    }

    /**
     * Make ORDER operating configuration
     *
     * @param string $fieldName Field name
     * @param string $sort Sort method, DESC or ASC
     *
     * @return mixed Return current object when succeed, or false otherwise
     */
    public function order($fieldName, $sort)
    {
        if (!isset($this->query['Order'])) {
            throw new Exception\OrderNotSupported();

            return false;
        }

        if (!isset(static::$orderOperators[$sort])) {
            throw new Exception\OrderSortOperatorUnknown($sort);

            return false;
        }

        if (!isset($this->query['Fields'][$fieldName])) {
            throw new Exception\OrderFieldUnknown($fieldName);

            return false;
        }

        $this->query['Order'][] = array(
            'Field' => $fieldName,
            'Sort' => static::$orderOperators[$sort],
        );

        return $this;
    }

    /**
     * Make VALUE operating configuration
     *
     * @param array $value Values in Field => Value pair
     *
     * @return mixed Return current object when succeed, or false otherwise
     */
    public function value(array $values)
    {
        $tempValueData = array();

        if (isset($this->query['Values'])) {
            foreach ($this->query['Fields'] as $field => $type) {
                if (isset($values[$field]) || array_key_exists($field, $values)) {
                    $tempValueData[] = $this->saveValue($values[$field], $field);
                } else {
                    throw new Exception\ValuesFieldNotSet($field);
                }
            }

            $this->query['Values'][] = $tempValueData;

            return $this;
        } else {
            throw new Exception\ValuesNotSupported($field);
        }

        return false;
    }

    /**
     * Make SET operating configuration
     *
     * @param array $values Values in Field => Value pair
     *
     * @return mixed Return current object when succeed, or false otherwise
     */
    public function set(array $values)
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
            throw new Exception\SetNotSupported();
        }

        return false;
    }

    /**
     * Make SET operating configuration for change existing number data
     *
     * @param array $values Arrays in array(Operator => '', Field => '', Value => '')
     *
     * @return mixed Return current object when succeed, or false otherwise
     */
    public function changes(array $values)
    {
        if (isset($this->query['Changes'])) {

            foreach ($values as $value) {
                if (!is_array($value)) {
                    throw new Exception\ChangeOperatorParameterInvaild();

                    return false;
                    break;
                }

                if (!isset(
                    $value['Operator'],
                    $value['Field'],
                    $value['Value']
                )) {
                    throw new Exception\ChangeOperatorParameterMissing(
                        implode(', ', array_keys($values))
                    );

                    return false;
                    break;
                }

                if (!isset(static::$mathOperators[$value['Operator']])) {
                    throw new Exception\ChangeOperatorInvalid(
                        $operator
                    );

                    return false;
                    break;
                }

                if (!is_numeric($value['Value'])) {
                    throw new Exception\ChangeValueNotNumber(
                        $value['Value']
                    );

                    return false;
                    break;
                }

                $this->query['Changes'][] = array(
                    'Field' => $value['Field'],
                    'Operator' => static::$mathOperators[$value['Operator']],
                    'Value' => $this->saveValue($value['Value'], $value['Field'])
                );
            }

            return $this;
        } else {
            throw new Exception\ChangeNotSupported();
        }

        return false;
    }

    /**
     * Make LIMIT operating configuration
     *
     * @param integer $offset Position of beginning cursor
     * @param integer $distance Distance the cursor will travel through
     *
     * @return mixed Return current object when succeed, or false otherwise
     */
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
                throw new Exception\LimitAlreadyAssigned();
            }
        } else {
            throw new Exception\LimitNotSupported();
        }

        return false;
    }

    /**
     * Get PDO Connection for active query
     *
     * @return mixed Return a PDO connection when succeed, or false for fail
     */
    private function getPDOConnection()
    {
        if ($this->connection = \Facula\Framework::core('pdo')->getConnection(
            array(
                'Table' => $this->query['From'],
                'Operation' => $this->query['Type']
            )
        )) {
            return $this->connection;
        } else {
            throw new Exception\GetConnectionFailed();
        }

        return false;
    }

    /**
     * Get operator for active query
     *
     * @return mixed Return a PDO connection when succeed, or false for fail
     */
    private function getDBOperator()
    {
        $operatorName = '';
        $operator = null;

        if (!$this->operator) {
            if (isset($this->connection->_connection['Driver'])) {
                $operatorName = static::getOperator(
                    $this->connection->_connection['Driver']
                );

                if (class_exists($operatorName)) {
                    $operator = new $operatorName(
                        $this->connection->_connection['Prefix']
                        . $this->query['From'],
                        $this->query
                    );

                    if ($operator instanceof OperatorImplement) {
                        return ($this->operator = $operator);
                    } else {
                        throw new Exception\OperatorInvalidInterface();
                    }

                } else {
                    throw new Exception\OperatorDriverNotSupported(
                        $this->connection->_connection['Driver']
                    );
                }
            } else {
                throw new Exception\OperatorNeedsConnection();
            }
        } else {
            return $this->operator;
        }

        return null;
    }

    /**
     * Execute the query
     *
     * @param array $requiredQueryParams Required query parameters for safe check
     *
     * @return array Return a PDO statement when succeed, or false for fail
     */
    protected function exec(
        array $requiredQueryParams = array()
    ) {
        $sql = $sqlID = '';
        $statement = $tempDataVal = null;
        $matchedParams = array();

        if (isset($this->query['Action'])) {
            // A little check before we actually do query. We need to know if
            // our required data in $this->query has been filled or we may make
            // mistake. And the mistake may cause query poisoning or inject
            foreach ($requiredQueryParams as $param) {
                if (is_array($param)) { // At least One of the param must be filled
                    $paramFilled = false;

                    foreach ($param as $paramName) {
                        if (!empty($this->query[$paramName])) {
                            $paramFilled = true;
                            break;
                        }
                    }

                    if (!$paramFilled) {
                        throw new Exception\ParameterRequiredForPrepare(implode(', ', $param));

                        return false;
                        break;
                    }
                } else {
                    if (empty($this->query[$param])) {
                        throw new Exception\ParameterRequiredForPrepare($param);

                        return false;
                        break;
                    }
                }
            }

            if ($this->getPDOConnection() && $this->getDBOperator()) {
                // Build SQL Syntax
                if ($sql = $this->operator->build()) {
                    try {
                        // Ensure the connection we used still the last one
                        $sqlID = crc32($sql . $this->connection->_connection['LstConnected']);

                        // Prepare statement
                        if (static::$lastStatement['Identity'] == $sqlID) {
                            $statement = static::$lastStatement['Statement'];
                        } else {
                            static::$lastStatement['Identity'] = $sqlID;
                            $statement = (static::$lastStatement['Statement']
                                            = $this->connection->prepare($sql));
                        }

                        if ($statement) {
                            // Search string and set key
                            if (preg_match_all('/(:[0-9]+)/', $sql, $matchedParams)) {

                                // Use key to search in datamap, and bind the value and types
                                foreach ($matchedParams[0] as $paramKey) {
                                    if (isset($this->dataMap[$paramKey])) {
                                        if ($this->dataMap[$paramKey]['Escape']) {
                                            $tempDataVal = $this->operator->escape(
                                                $this->dataMap[$paramKey]['Escape'],
                                                $this->dataMap[$paramKey]['Value']
                                            );
                                        } else {
                                            $tempDataVal = $this->dataMap[$paramKey]['Value'];
                                        }

                                        if (!$statement->bindValue(
                                            $paramKey,
                                            $tempDataVal,
                                            $this->dataMap[$paramKey]['Type']
                                        )) {
                                            throw new Exception\PrepareBindValueFailed(
                                                $paramKey,
                                                $tempDataVal,
                                                $this->dataMap[$paramKey]['Type']
                                            );
                                        }
                                    } else {
                                        throw new Exception\PrepareParameterNotSet(
                                            $paramKey
                                        );
                                    }
                                }
                            }

                            // We got need this
                            $statement->connection = &$this->connection;

                            if ($statement->execute()) {
                                static::$queries++;

                                return $statement;
                            }
                        }
                    } catch (PDOException $e) {
                        throw new Exception\ExceptionOnQuerying(
                            $e->getMessage()
                        );
                    }
                }
            }
        } else {
            throw new Exception\UnknownActionForPrepare();
        }

        return false;
    }

    /**
     * Perform the data query and return one result
     *
     * @return mixed Return the result when succeed, or false when fail
     */
    public function get()
    {
        $result = array();

        if ($this->limit(0, 1) && ($result = $this->fetch())) {
            return $result[0];
        }

        return false;
    }

    /**
     * Perform the data query and return requested results
     *
     * @param string $mode PDO Query method in short, ASSOC etc
     * @param string $className Class use to bind data if use Class mode
     *
     * @return mixed Return the result when succeed, or false when fail
     */
    public function fetch($mode = 'ASSOC', $className = null)
    {
        $sql = '';
        $statement = $readParser = null;
        $results = $rawResults = $readParsers = array();

        if (isset($this->query['Action']) && $this->query['Type'] == 'Read') {
            if ($statement = $this->exec($this->query['Required'])) {
                try {
                    // Yeah, Always returns assoc array
                    $statement->setFetchMode(\PDO::FETCH_ASSOC);

                    if ($this->query['Parser']
                    && isset($this->query['FieldParsers'])) {
                        $rawResults = $this->fetchWithParsers($this->operator->fetch($statement));
                    } else {
                        $rawResults = $this->fetchPure($this->operator->fetch($statement));
                    }

                    switch($mode) {
                        case 'ASSOC':
                            return $rawResults;
                            break;

                        case 'CLASS':
                            if (!class_exists($className)) {
                                throw new Exception\FetchContainerClassNotFound($className);

                                return false;
                            }

                            foreach ($rawResults as $key => $row) {
                                $tempClass = new $className();

                                foreach ($row as $rowKey => $rowVal) {
                                    $tempClass->$rowKey = $rowVal;
                                }

                                $results[] = $tempClass;
                            }

                            return $results;
                            break;

                        default:
                            throw new Exception\FetchModeUnknown($mode);

                            return false;
                            break;
                    }
                } catch (PDOException $e) {
                    throw new Exception\ExceptionOnFetching(
                        $e->getMessage()
                    );
                }
            }
        } else {
            throw new Exception\FetchNotSupported();
        }

        return false;
    }

    /**
     * Fetch data with parsers
     *
     * @param PDOStatement $statement The PDO statement
     *
     * @return array Return array of results when succeed, or empty array when fail
     */
    private function fetchWithParsers(\PDOStatement $statement)
    {
        $result = array();

        while ($row = $statement->fetch()) {

            foreach ($this->query['FieldParsers'] as $field => $parsers) {
                if (isset($row[$field])) {
                    foreach ($parsers as $parser) {
                        if (isset(static::$parsers[$parser]['Reader'])) {
                            if (!isset($readParsers[$parser])) {
                                $readParsers[$parser] =
                                    static::$parsers[$parser]['Reader'];
                            }

                            $row[$field] = $readParsers[$parser]($row[$field]);
                        }
                    }
                }
            }

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Fetch data in PDO statement
     *
     * @param PDOStatement $statement The PDO statement
     *
     * @return array Return array of results when succeed, or empty array when fail
     */
    private function fetchPure(\PDOStatement $statement)
    {
        $result = array();

        while ($row = $statement->fetch()) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Perform a data writing query
     *
     * @param string $param Additional argument
     *
     * @return mixed Return the result when succeed, or false when fail
     */
    public function save($param = null)
    {
        $statement = null;
        $seqFullName = '';

        if (isset($this->query['Action']) && $this->query['Type'] == 'Write') {
            if ($statement = $this->exec($this->query['Required'])) {
                try {

                    switch ($this->query['Action']) {
                        case 'insert':
                            return $this->operator->insert($statement, $param);
                            break;

                        case 'update':
                            return $this->operator->update($statement);
                            break;

                        case 'delete':
                            return $this->operator->delete($statement);
                            break;

                        default:
                            throw new Exception\SaveMethodNotSupported($this->query['Action']);

                            break;
                    }

                } catch (PDOException $e) {
                    throw new Exception\ExceptionOnSaving(
                        $e->getMessage()
                    );
                }
            }
        } else {
            throw new Exception\SaveNotSupported($this->query['Action']);
        }
    }
}
