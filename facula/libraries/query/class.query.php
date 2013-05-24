<?php 

interface queryInterface {
	
}

class query {
	private $dbh = null;
	
	private $table = '';
	
	private $activeMethod = '';
	
	private $valueIndex = 0;
	
	private $activeParams = array();
	
	private $activeValues = array();
	
	private $validParamTypes = array(
		'BOOL' => PDO::PARAM_BOOL,
		'NULL' => PDO::PARAM_NULL,
		'INT' => PDO::PARAM_INT,
		'STR' => PDO::PARAM_STR,
		'LOB' => PDO::PARAM_LOB,
		'STMT' => PDO::PARAM_STMT,
	);
	
	/*
	private $paramTemplate = array(
		'SELECT' => array(
						'FIELDS' => array(),
						'WHERE' => array(),
						'ORDERBY' => array(),
						'LIMIT' => array(),
						), 
		'INSERT' => array(
						'FIELDS' => array(),
						'VALUES' => array(),
						), 
		'UPDATE' => array(
						'FIELDS' => array(),
						'VALUES' => array(),
						'WHERE' => array(),
						), 
		'DELETE' => array(
						'WHERE' => array(),
						), 
	);
	*/
	
	static public function from($table) {
		return new self($table);
	}
	
	public function __construct($table) {
		$this->table = $table;
	}
	
	private function reset() {
		$this->activeMethod = '';
	
		$this->valueIndex = 0;
	
		$this->activeParams = $this->activeValues = array();
		
		return true;
	}
	
	private function setValueGetKey($value) {
		$index = $this->valueIndex++;
		
		if (!isset($value[0])) {
			$this->activeValues[$index] = array(
				'Value' => null,
				'Type' => $this->validParamTypes['NULL'],
				'Length' => 0,
			);
		} else {
			$this->activeValues[$index] = array(
				'Value' => isset($value[0]) ? $value[0] : null,
				'Type' => isset($value[1]) && isset($this->validParamTypes[$value[1]]) ? $this->validParamTypes[$value[1]] : $this->validParamTypes['STR'],
				'Length' => isset($value[2]) ? intval($value[2]) : null,
			);
		}
		
		return $index;
	}
	
	private function getOperator($operation) {
		$operatorName = $fullTableName = '';
		
		if ($this->dbh = facula::core('pdo')->getConnection(array('Table' => $this->table, 'Operation' => $operation))) {
			$operatorName = __CLASS__ . '_' . $this->dbh->_connection['Driver'];
			
			$fullTableName = $this->dbh->_connection['Prefix'] . $this->table;
			
			if (class_exists($operatorName)) {
				return new $operatorName($fullTableName);
			} else {
				facula::core('debug')->exception('ERROR_QUERY_OPERATOR_NOTFOUND|' . $operatorName, 'query', true);
			}
		}
	}
	
	public function select($fields) {
		$this->reset();
		
		$this->activeMethod = 'SELECT';
		$this->activeParams['SELECT']['FIELDS'] = $fields;
		
		return $this;
	}
	
	public function insert($data) {
		$this->reset();
		
		$this->activeMethod = 'INSERT';
		$this->activeParams['INSERT']['FIELDS'] = $data;
		
		if (is_array($data[0])) {
			$this->activeParams['INSERT']['FIELDS'] = array_keys($data[0]);
			
			foreach($data AS $key => $val) {
				$this->activeParams['INSERT']['VALUES'][] = $val;
			}
		}
		
		return $this;
	}
	
	public function update($data) {
		$this->reset();
		
		$this->activeMethod = 'INSERT';
		$this->activeParams['INSERT']['FIELDS'] = $data;
		
		if (is_array($data[0])) {
			$this->activeParams['INSERT']['FIELDS'] = array_keys($data[0]);
			
			foreach($data AS $key => $val) {
				$this->activeParams['INSERT']['VALUES'][] = $val;
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_OPERATOR_UPDATE_PARAMS|' . $operatorName, 'query', true);
		}
		
		return $this;
	}
	
	public function delete() {
		$this->reset();
		
		return $this;
	}
	
	public function where($table, $sign, $value, $relation = '') {
		return $this->condition('WHERE', $table, $sign, $value, $relation);
	}
	
	public function limit($start, $duration) {
		if ($this->activeMethod) {

			$this->activeParams[$this->activeMethod]['LIMIT'] = array(
				'START' => intval($start),
				'DURATION' => intval($duration),
			);
			
			return $this;
		} else {
			facula::core('debug')->exception('ERROR_QUERY_OPERATOR_UNSPECIFIED_METHOD_FOR_LIMIT', 'query', true);
		}
	}
	
	private function condition($type, $table, $sign, array $value, $relation = '') {
		if ($type != 'WHERE' || $type != 'HAVING') {
			if ($this->activeMethod) {
				if (!isset($this->activeParams[$this->activeMethod][$type][0])) {
					$this->activeParams[$this->activeMethod][$type][] = array(
						'FIELD' => $table,
						'SIGN' => $sign,
						'VALUEKEY' => $this->setValueGetKey($value),
					);
				} else {
					$this->activeParams[$this->activeMethod][$type][] = array(
						'FIELD' => $table,
						'SIGN' => $sign,
						'VALUEKEY' => $this->setValueGetKey($value),
						'RELATION' => $relation, // Relation to the previous condition
					);
				}
				
				return $this;
			} else {
				facula::core('debug')->exception('ERROR_QUERY_OPERATOR_UNSPECIFIED_METHOD_FOR_WHERE', 'query', true);
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_OPERATOR_UNSUPPORTED_CONDITION_TYPE|' . $type, 'query', true);
		}
		
		return false;
	}
	
	public function order($field, $method) {
		if ($this->activeMethod) {
			$this->activeParams[$this->activeMethod]['ORDERBY'][] = array(
				'FIELD' => $field,
				'METHOD' => $method,
			);
		} else {
			facula::core('debug')->exception('ERROR_QUERY_OPERATOR_UNSPECIFIED_METHOD_FOR_ORDER', 'query', true);
		}
		
		return $this;
	}
	
	public function save() {
		
	}
	
	public function get() {
		if ($result = $this->fetch(0, 1)) {
			return $result[0];
		}
		
		return false;
	}
	
	public function fetch($start = 0, $duration = 0) {
		$operator = $statement = null;
		$sql = '';
		
		if ($this->activeMethod == 'SELECT') {
			if ($operator = $this->getOperator('Read')) {
				if ($sql = $operator->select($this->activeParams[$this->activeMethod])) {
					try {
						if ($statement = $this->dbh->prepare($sql)) {
							foreach($this->activeValues AS $key => $value) {
								$statement->bindParam(':' . $key, $value['Value'], $value['Type'], $value['Length']);
							}
							
							$statement->execute();
							
							return $statement->fetchAll(PDO::FETCH_ASSOC);
						}
					} catch(PDOException $e) {
						facula::core('debug')->exception('ERROR_QUERY_FEATCH_FAILED|' . $e->getMessage(), 'query', true);
					}
				}
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_OPERATOR_UNSUPPORTED_FETCH_TYPE|' . $this->activeMethod, 'query', true);
		}
		
		return false;
	}
}

?>