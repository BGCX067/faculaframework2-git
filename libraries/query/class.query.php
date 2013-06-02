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
	public function select(&$settings);
	public function insert(&$settings);
	public function update(&$settings);
	public function delete(&$settings);
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
	
	private function __construct($table) {
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
		
		if (!is_array($value)) {
			$this->activeValues[':' . $index] = array(
				'Value' => $value,
				'Type' => $this->validParamTypes['STR'],
			);
		} else {
			if (isset($value[0])) {
				$this->activeValues[':' . $index]['Value'] = $value[0];
			} else {
				$this->activeValues[':' . $index]['Value'] = null;
			}
			
			if (isset($value[1]) && isset($this->validParamTypes[$value[1]])) {
				$this->activeValues[':' . $index]['Type'] = $this->validParamTypes[$value[1]];
			} else {
				$this->activeValues[':' . $index]['Type'] = $this->activeValues[':' . $index]['Value'] ? $this->validParamTypes['STR'] : null;
			}
		}
		
		return ':' . $index;
	}
	
	private function getSQLBuilder($operation) {
		$builderName = $fullTableName = '';
		
		if ($this->dbh = facula::core('pdo')->getConnection(array('Table' => $this->table, 'Operation' => $operation))) {
			$builderName = __CLASS__ . '_' . $this->dbh->_connection['Driver'];
			
			$fullTableName = $this->dbh->_connection['Prefix'] . $this->table;
			
			if (class_exists($builderName)) {
				return new $builderName($fullTableName);
			} else {
				facula::core('debug')->exception('ERROR_QUERY_SQLBUILDER_NOTFOUND|' . $builderName, 'query', true);
			}
		}
	}
	
	/* Primary CURD operates (Operation Typer) */
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
			
			foreach($data AS $key => $vals) {
				$result = array();
				
				foreach($this->activeParams['INSERT']['FIELDS'] AS $fieldkey) {
					$result[] = isset($vals[$fieldkey]) ? $this->setValueGetKey($vals[$fieldkey]) : $this->setValueGetKey(array(null, 'NULL'));
				}
				
				$this->activeParams['INSERT']['VALUEKEYS'][] = $result;
			}
		}
		
		return $this;
	}
	
	public function update($data) {
		$this->reset();
		
		$this->activeMethod = 'UPDATE';
		
		foreach($data AS $key => $val) {
			$this->activeParams['UPDATE']['VALUEKEYS'][$key] = $this->setValueGetKey($val);
		}
		
		return $this;
	}
	
	public function delete() {
		$this->reset();
		
		$this->activeMethod = 'DELETE';
		
		return $this;
	}
	
	/* WHERE */
	public function where($table, $sign, $value, $relation = '') {
		return $this->condition('WHERE', $table, $sign, $value, $relation);
	}
	
	private function condition($type, $table, $sign, $value, $relation = '') {
		if ($this->activeMethod == 'SELECT' || $this->activeMethod == 'UPDATE' || $this->activeMethod == 'DELETE') {
			if ($type == 'WHERE' || $type == 'HAVING') {
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
						'RELATION' => $relation == 'OR' || $relation == 'AND' ? $relation : 'AND', // Relation to the previous condition
					);
				}
				
				return $this;
			} else {
				facula::core('debug')->exception('ERROR_QUERY_SQLBuilder_UNSUPPORTED_CONDITION_TYPE|' . $type, 'query', true);
			}
		} else {
			facula::core('debug')->exception('ERROR_QUERY_SQLBuilder_NOT_SUPPORT_CONDITION|' . $this->activeMethod, 'query', true);
		}
			
		return false;
	}
	
	/* ORDER */
	public function order($field, $method) {
		if ($this->activeMethod == 'SELECT' || $this->activeMethod == 'UPDATE' || $this->activeMethod == 'DELETE') {
			$this->activeParams[$this->activeMethod]['ORDERBY'][] = array(
				'FIELD' => $field,
				'METHOD' => $method,
			);
		} else {
			facula::core('debug')->exception('ERROR_QUERY_SQLBuilder_NOT_SUPPORT_ORDER|' . $this->activeMethod, 'query', true);
		}
		
		return $this;
	}
	
	/* LIMIT */
	public function limit($start, $duration) {
		if ($this->activeMethod == 'SELECT' || $this->activeMethod == 'UPDATE' || $this->activeMethod == 'DELETE') {
			$this->activeParams[$this->activeMethod]['LIMIT'] = array(
				'START' => intval($start),
				'DURATION' => intval($duration),
			);
			
			return $this;
		} else {
			facula::core('debug')->exception('ERROR_QUERY_SQLBuilder_NOT_SUPPORT_LIMIT|' . $this->activeMethod, 'query', true);
		}
	}
	
	/* Action Performer */
	public function get() {
		if ($result = $this->fetch(0, 1)) {
			return $result[0];
		}
		
		return false;
	}
	
	public function fetch($start = 0, $duration = 0) {
		$builder = $statement = null;
		$sql = '';
		
		if ($this->activeMethod == 'SELECT') {
			if ($builder = $this->getSQLBuilder('Read')) {
				if ($sql = $builder->select($this->activeParams[$this->activeMethod])) {
					try {
						if ($statement = $this->dbh->prepare($sql)) {
							foreach($this->activeValues AS $key => $value) {
								$statement->bindValue($key, $value['Value'], $value['Type']);
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
			facula::core('debug')->exception('ERROR_QUERY_SQLBuilder_UNSUPPORTED_FETCH_TYPE|' . $this->activeMethod, 'query', true);
		}
		
		return false;
	}
	
	public function save() {
		$sql = '';
		$builder = $statement = null;
		
		if ($builder = $this->getSQLBuilder('Write')) {
			switch($this->activeMethod) {
				case 'UPDATE':
					if ($sql = $builder->update($this->activeParams[$this->activeMethod])) {
						try {
							if ($statement = $this->dbh->prepare($sql)) {
								foreach($this->activeValues AS $key => $value) {
									$statement->bindValue($key, $value['Value'], $value['Type']);
								}
								
								$statement->execute();
								
								return $statement->rowCount();
							}
						} catch(PDOException $e) {
							facula::core('debug')->exception('ERROR_QUERY_UPDATE_FAILED|' . $e->getMessage(), 'query', true);
						}
					}
					
					break;
					
				case 'INSERT':
					if ($sql = $builder->insert($this->activeParams[$this->activeMethod])) {
						try {
							if ($statement = $this->dbh->prepare($sql)) {
								foreach($this->activeValues AS $key => $value) {
									$statement->bindValue($key, $value['Value'], $value['Type']);
								}
								
								$statement->execute();
								
								return $this->dbh->lastInsertId();
							}
						} catch(PDOException $e) {
							facula::core('debug')->exception('ERROR_QUERY_INSERT_FAILED|' . $e->getMessage(), 'query', true);
						}
					}
					
					break;
					
				case 'DELETE':
					if ($sql = $builder->delete($this->activeParams[$this->activeMethod])) {
						try {
							if ($statement = $this->dbh->prepare($sql)) {
								foreach($this->activeValues AS $key => $value) {
									$statement->bindValue($key, $value['Value'], $value['Type']);
								}
								
								$statement->execute();
								
								return $statement->rowCount();
							}
						} catch(PDOException $e) {
							facula::core('debug')->exception('ERROR_QUERY_DELETE_FAILED|' . $e->getMessage(), 'query', true);
						}
					}
					break;
					
				default:
					facula::core('debug')->exception('ERROR_QUERY_SQLBuilder_UNSUPPORTED_FETCH_TYPE|' . $this->activeMethod, 'query', true);
					return false;
					break;
			}
		}
		
		return false;
	}
}

?>