<?php 

/*****************************************************************************
	Facula Framework Query Unit

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

interface queryInterface {
	public function select($fields);
	public function insert($fields);
	public function update($fields);
	public function delete($fields);
	
	public function where($logic = '', $fieldName, $operator, $value);
	public function having($logic = '', $fieldName, $operator, $value);
	
	public function group($fieldName);
	public function order($fieldName, $sort);
	
	public function value($value);
	public function set($field, $value);
	
	public function limit($offset, $distance);
	
	public function get();
	public function fetch();
	public function save();
}

interface queryBuilderInterface {
	public function __construct($tableName, &$querySet);
	public function build();
}

// Yeah, i reworked this unit because i hate the original one.
class query implements queryInterface {
	static private $pdoDataTypes = array(
		'BOOL' => PDO::PARAM_BOOL,
		'NULL' => PDO::PARAM_NULL,
		'INT' => PDO::PARAM_INT,
		'STR' => PDO::PARAM_STR,
		'LOB' => PDO::PARAM_LOB,
		'STMT' => PDO::PARAM_STMT,
	);
	
	static private $queryOperators = array(
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
	
	static private $logicOperators = array(
		'AND' => 'AND',
		'OR' => 'OR',
	);
	
	static private $orderOperators = array(
		'DESC' => 'DESC',
		'ASC' => 'ASC',
	);
	
	private $connection = null;
	private $builder = null;
	
	private $query = array();
	
	/*
	private $query = array(
		'Action' => 'Select', // SQL Syntax
		'Type' => 'Write|Read', // Query Type
		'From' => '', // Table name
		'Required' => array('Fields', 'Where', 'Group', 'Having', 'Order', 'Limit', 'Values', 'Sets'), // Required fields of this array for param validation
		'FieldTypes' => array('STR', 'INT', 'BOOL'), // FieldTypes for insert
		'Fields' => array(), // Fields name
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
	private $dataMap = array();
	
	static public function from($tableName) {
		return new self($tableName);
	}
	
	private function __construct($tableName) {
		$this->query['From'] = $tableName;
		
		return true;
	}
	
	// Select
	public function select($fields) {
		if (!isset($this->query['Action'])) {
			$this->query['Action'] = 'select';
			$this->query['Type'] = 'Read';
			$this->query['Required'] = array();
			
			if ($this->saveFields($fields, true)) {
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
			facula::core('debug')->exception('ERROR_QUERY_ACTION_ASSIGNED|' . $this->query['Action'], 'query', true);
		}
		
		return false;
	}
	
	// Insert
	public function insert($fields) {
		if (!isset($this->query['Action'])) {
			$this->query['Action'] = 'insert';
			$this->query['Type'] = 'Write';
			$this->query['Required'] = array('Fields', 'Values');
			
			if ($this->saveFields($fields, false)) {
				// Enable values
				$this->query['Values'] = array();
				
				return $this;
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_ACTION_ASSIGNED|' . $this->query['Action'], 'query', true);
		}
		
		return false;
	}
	
	// Update
	public function update($fields) {
		if (!isset($this->query['Action'])) {
			$this->query['Action'] = 'update';
			$this->query['Type'] = 'Write';
			$this->query['Required'] = array('Sets');
			
			// Save fields
			if ($this->saveFields($fields, false)) {
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
			facula::core('debug')->exception('ERROR_QUERY_ACTION_ASSIGNED|' . $this->query['Action'], 'query', true);
		}
		
		return false;
	}
	
	// Delete
	public function delete($fields) {
		if (!isset($this->query['Action'])) {
			$this->query['Action'] = 'delete';
			$this->query['Type'] = 'Write';
			$this->query['Required'] = array('Where');
			
			// Save fields
			if ($this->saveFields($fields, false)) {
				// Enable Where
				$this->query['Where'] = array();
				
				// Enable Order
				$this->query['Order'] = array();
				
				// Enable Limit
				$this->query['Limit'] = array();
				
				return $this;
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_ACTION_ASSIGNED|' . $this->query['Action'], 'query', true);
		}
		
		return false;
	}
	
	// Save data and data type to data map for bindValue
	private function saveFields($fields, $addfield = true) {
		if (is_array($fields)) {
			foreach($fields AS $fieldName => $fieldType) {
				if ($addfield) {
					$this->query['Fields'][] = $fieldName;
				}
				
				$this->query['FieldTypes'][$fieldName] = $fieldType;
			}
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_QUERY_SAVEFIELD_FIELDS_INVALID|' . $type, 'query', true);
		}

		return false;
	}
	
	private function saveValue($value, $forField) {
		$dataKey = ':' . $this->dataIndex++;
		
		if (isset($this->query['FieldTypes'][$forField])) {
			if (isset(self::$pdoDataTypes[$this->query['FieldTypes'][$forField]])) {
				$this->dataMap[$dataKey] = array(
					'Value' => $value,
					'Type' => self::$pdoDataTypes[$this->query['FieldTypes'][$forField]],
				);
				
				return $dataKey;
			} else {
				facula::core('debug')->exception('ERROR_QUERY_SAVEVALUE_TYPE_UNKNOWN|' . $type, 'query', true);
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_SAVEVALUE_FIELD_UNKNOWN|' . $forField, 'query', true);
		}
		
		return false;
	}
	
	// Conditions like where and having
	private function condition($logic = '', $fieldName, $operator, $value) {
		$params = array();
		
		if (isset(self::$queryOperators[$operator])) {
			switch(self::$queryOperators[$operator]) {
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
					if ($value === null) {
						$params = array(
							'Operator' => '<=>',
							'Value' => $this->saveValue('NULL', fieldName),
						);
					} else {
						facula::core('debug')->exception('ERROR_QUERY_CONDITION_OPERATOR_VALUE_MUST_NULL|' . $operator, 'query', true);
						
						return false;
					}
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
						facula::core('debug')->exception('ERROR_QUERY_CONDITION_OPERATOR_INVALID_PARAM|' . $operator, 'query', true);
						
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
						facula::core('debug')->exception('ERROR_QUERY_CONDITION_OPERATOR_INVALID_PARAM|' . $operator, 'query', true);
						
						return false;
					}
					break;
					
				case 'IN':
					if (is_array($value)) {
						$params['Operator'] = 'IN';
						
						foreach($value AS $val) {
							$params['Value'][] = $this->saveValue($val, $fieldName);
						}
						
					} else {
						facula::core('debug')->exception('ERROR_QUERY_CONDITION_OPERATOR_INVALID_PARAM|' . $operator, 'query', true);
						
						return false;
					}
					break;
					
				case 'NOT IN':
					if (is_array($value)) {
						$params['Operator'] = 'NOT IN';
						
						foreach($value AS $val) {
							$params['Value'][] = $this->saveValue($val, $fieldName);
						}
						
					} else {
						facula::core('debug')->exception('ERROR_QUERY_CONDITION_OPERATOR_INVALID_PARAM|' . $operator, 'query', true);
						
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
			
			if (isset(self::$logicOperators[$logic])) {
				switch(self::$logicOperators[$logic]) {
					case 'AND':
						$params['Logic'] = 'AND';
						break;
						
					case 'OR':
						$params['Logic'] = 'OR';
						break;
						
					default:
						$params['Logic'] = 'AND';
						break;
				}
			} else {
				facula::core('debug')->exception('ERROR_QUERY_CONDITION_UNKNOWN_LOGIC|' . $logic, 'query', true);
				return false;
			}
			
			$condition = array(
				'Field' => $fieldName,
				'Operator' => $params['Operator'],
				'Value' => $params['Value'],
				'Logic' => $params['Logic'],
			);
			
			return $condition;
		} else {
			facula::core('debug')->exception('ERROR_QUERY_CONDITION_UNKNOWN_OPERATOR|' . $operator, 'query', true);
		}
		
		return false;
	}
	
	// Where
	public function where($logic = '', $fieldName, $operator, $value) {
		if (isset($this->query['Where'])) {
			if ($this->query['Where'][] = $this->condition($logic, $fieldName, $operator, $value)) {
				return $this;
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_WHERE_NOT_SUPPORTED', 'query', true);
		}
		
		return false;
	}
	
	// Having
	public function having($logic = '', $fieldName, $operator, $value) {
		if (isset($this->query['Having'])) {
			if ($this->query['Having'][] = $this->condition($logic, $fieldName, $operator, $value)) {
				return $this;
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_HAVING_NOT_SUPPORTED', 'query', true);
		}
		
		return false;
	}
	
	// Group
	public function group($fieldName) {
		if (isset($this->query['Group'])) {
			$this->query['Group'][] = array(
				'Field' => $fieldName,
			);
			
			// Enable Having for group by
			$this->query['Having'] = array();
			
			return $this;
		} else {
			facula::core('debug')->exception('ERROR_QUERY_GROUP_NOT_SUPPORTED', 'query', true);
		}
		
		return false;
	}
	
	// Order
	public function order($fieldName, $sort) {
		$sortMethod = '';
		
		if (isset($this->query['Order'])) {
			if (isset(self::$orderOperators[$sort])) {
				
				$this->query['Order'][] = array(
					'Field' => $fieldName,
					'Sort' => self::$orderOperators[$sort],
				);
				
				return $this;
			} else {
				facula::core('debug')->exception('ERROR_QUERY_ORDER_SORTOPERATOR_UNKOWN', 'query', true);
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_ORDER_NOT_SUPPORTED', 'query', true);
		}
		
		return false;
	}
	
	// Values
	public function value($value) {
		$tempValueData = array();
		
		if (isset($this->query['Values'])) {
			foreach($value AS $key => $val) {
				if (in_array($key, $this->query['Fields'])) {
					$tempValueData[] = $this->saveValue($val, $key);
				} else {
					facula::core('debug')->exception('ERROR_QUERY_VALUES_FIELD_NOTSET|' . $field, 'query', true);
				}
			}
			
			$this->query['Values'][] = $tempValueData;
			
			return $this;
		} else {
			facula::core('debug')->exception('ERROR_QUERY_VALUES_NOT_SUPPORTED', 'query', true);
		}
		
		return false;
	}
	
	// Sets
	public function set($field, $value) {
		if (isset($this->query['Sets'])) {
			
			$this->query['Sets'][] = array(
				'Field' => $field,
				'Value' => $this->saveValue($value, $field),
			);
			
			return $this;
		} else {
			facula::core('debug')->exception('ERROR_QUERY_SETS_NOT_SUPPORTED', 'query', true);
		}
		
		return false;
	}
	
	// Limit
	public function limit($offset, $distance) {
		if (isset($this->query['Limit'])) {
			if (empty($this->query['Limit'])) {
				
				$this->query['Limit'] = array(
					'Offset' => $offset,
					'Distance' => $distance,
				);
				
				return $this;
			} else {
				facula::core('debug')->exception('ERROR_QUERY_LIMIT_ALREADY_ASSIGNED', 'query', true);
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_LIMIT_NOT_SUPPORTED', 'query', true);
		}
		
		return false;
	}
	
	// Get data output
	public function getCurrentInput() {
		return array(
			'Query' => $this->query,
			'Data' => $this->dataMap,
		);
	}
	
	// Task Preparers
	private function getPDOConnection() {
		if ($this->connection = facula::core('pdo')->getConnection(array('Table' => $this->query['From'], 'Permission' => $this->query['Type']))) {
			return $this->connection;
		} else {
			facula::core('debug')->exception('ERROR_QUERY_PDO_CONNECTION_FAILED', 'query', true);
		}
		
		return false;
	}
	
	private function getSQLBuilder() {
		$builderName = '';
		$builder = null;
		
		if (isset($this->connection->_connection['Driver'])) {
			$builderName = __CLASS__ . '_' . $this->connection->_connection['Driver'];
			
			if (class_exists($builderName)) {
				$builder = new $builderName($this->connection->_connection['Prefix'] . $this->query['From'], $this->query);
				
				if ($builder instanceof queryBuilderInterface) {
					$this->builder = $builder;
					
					return $this->builder;
				} else {
					facula::core('debug')->exception('ERROR_QUERY_BUILDER_INTERFACE_INVALID', 'query', true);
				}
				
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_SQL_BUILDER_NEEDS_CONNECTION', 'query', true);
		}
		
		return false;
	}
	
	private function prepare($requiredQueryParams = array()) {
		$sql = '';
		$statement = null;
		
		if (isset($this->query['Action'])) {
			// A little check before we actually do query. We need to know if our required data in $this->query has been filled or we may make big mistake.
			foreach($requiredQueryParams AS $param) {
				if (empty($this->query[$param])) {
					facula::core('debug')->exception('ERROR_QUERY_PREPARE_PARAM_REQUIRED|' . $param, 'query', true);
					
					return false;
					break;
				}
			}
			
			if ($this->getPDOConnection() && $this->getSQLBuilder()) {
				// Build SQL Syntax
				if ($sql = $this->builder->build()) {
					try {
						// Prepare statement
						if ($statement = $this->connection->prepare($sql)) {
							foreach($this->dataMap AS $mapKey => $mapVal) {
								$statement->bindValue($mapKey, $mapVal['Value'], $mapVal['Type']);
							}
							
							// We got need this
							$statement->connection = &$this->connection;
							
							if ($statement->execute()) {
								return $statement;
							}
						}
					} catch(PDOException $e) {
						facula::core('debug')->exception('ERROR_QUERY_PREPARE_FAILED|' . $e->getMessage(), 'query', true);
					}
				}
				
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_PREPARE_MUST_INITED', 'query', true);
		}
		
		return false;
	}
	
	// Operator: Query performers
	public function get() {
		$result = array();
		
		if ($this->limit(0, 1) && ($result = $this->fetch())) {
			return $result[0];
		}
		
		return false;
	}
	
	public function fetch() {
		$sql = '';
		$statement = null;
		
		if (isset($this->query['Action']) && $this->query['Type'] == 'Read') {
			if ($statement = $this->prepare($this->query['Required'])) {
				try {
					return $statement->fetchAll(PDO::FETCH_ASSOC);
				} catch(PDOException $e) {
					facula::core('debug')->exception('ERROR_QUERY_FETCH_FAILED|' . $e->getMessage(), 'query', true);
				}
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_FETCH_NOT_SUPPORTED', 'query', true);
		}
		
		return false;
	}
	
	public function save() {
		$statement = null;
		
		if (isset($this->query['Action']) && $this->query['Type'] == 'Write') {
			if ($statement = $this->prepare($this->query['Required'])) {
				try {
					switch($this->query['Action']) {
						case 'insert':
							return $statement->connection->lastInsertId();
							break;
							
						case 'update':
							return $statement->rowCount();
							break;
							
						case 'delete':
							return $statement->rowCount();
							break;
							
						default:
							facula::core('debug')->exception('ERROR_QUERY_SAVE_METHOD_UNSUPPORTED|' . $this->query['Action'], 'query', true);
							break;
					}
				} catch(PDOException $e) {
					facula::core('debug')->exception('ERROR_QUERY_SAVE_FAILED|' . $e->getMessage(), 'query', true);
				}
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_SAVE_NOT_SUPPORTED', 'query', true);
		}
	}
}

?>