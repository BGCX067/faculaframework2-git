<?php 

/*****************************************************************************
	Facula Framework Simple Object Relation Mapping (!Experimental!)
	
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

interface ormInterface {
	public function __set($key, $val);
	public function __get($key);
	public function __isset($key);
	
	public function getPrimaryValue();
	public function getFields();
	public function getData();
	
	public function get(array $param, $returnType = 'CLASS', $whereOperator = '=');
	public function fetch(array $param, $offset = 0, $dist = 0, $returnType = 'CLASS', $whereOperator = '=');
	
	public function finds(array $param, $offset = 0, $dist = 0, $returnType = 'CLASS');
	
	public function getInKey($keyField, $value, $param = array(), $returnType = 'CLASS', $whereOperator = '=');
	public function fetchInKeys($keyField, array $values, array $param = array(), $offset = 0, $dist = 0, $returnType = 'CLASS', $whereOperator = '=');
	
	public function getByPK($key, $returnType = 'CLASS');
	public function fetchByPKs($keys, array $param = array(), $offset = 0, $dist = 0, $returnType = 'CLASS', $whereOperator = '=');
	
	public function getWith(array $joinModels, array $whereParams, $whereOperator = '=');
	public function fetchWith(array $joinModels, array $currentParams, $offset = 0, $dist = 0, $whereOperator = '=');
	
	public function save();
	public function insert();
	public function delete();
}

abstract class SimpleORM implements ormInterface, ArrayAccess {
	protected $table = '';
	protected $fields = array();
	protected $primary = '';
	
	private $data = array();
	private $dataOriginal = array();

	public $cachedObjectFilePath = '';
	public $cachedObjectSaveTime = 0;
	
	public function __set($key, $val) {
		if (!isset($this->data[$key])) {
			$this->dataOriginal[$key] = $val;
		}
		
		$this->data[$key] = $val;
	}
	
	public function __get($key) {
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}
	
	public function __isset($key) {
		isset($this->data[$key]);
	}
	
	public function __unset($key) {
		unset($this->data[$key]);
	}
	
	public function offsetSet($offset, $value) {
		$this->data[$offset] = $value;
	}
	
	public function offsetGet($offset) {
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
	}
	
	public function offsetExists($offset) {
		isset($this->data[$offset]);
	}
	
	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}
	
	public function getPrimaryValue() {
		if (isset($this->data[$this->primary])) {
			return $this->data[$this->primary];
		} else {
			facula::core('debug')->exception('ERROR_ORM_GETPRIMARY_PRIMARYDATA_EMPTY', 'orm', true);
		}
		
		return null;
	}
	
	public function getFields() {
		return $this->fields;
	}
	
	public function getData() {
		return $this->data;
	}
	
	private function &getDataRef() {
		return $this->data;
	}
	
	public function get(array $param, $returnType = 'CLASS', $whereOperator = '=') { // array('FieldName' => 'Value');
		$data = array();
		
		if (($data = $this->fetch(array('Where' => $param), 0, 1, $returnType = 'CLASS', $whereOperator = '=')) && isset($data[0])) {
			return $data[0];
		}
		
		return false;
	}
	
	public function fetch(array $param, $offset = 0, $dist = 0, $returnType = 'CLASS', $whereOperator = '=') {
		$whereParams = array();
		
		$query = null;
		
		$query = query::from($this->table);
		$query->select($this->fields);
		
		if (isset($param['Where'])) {
			foreach($param['Where'] AS $field => $value) {
				if (is_array($value)) {
					$whereParams['Operator'] = isset($value[1]) ? $value[1] : $whereOperator;
					$whereParams['Value'] = isset($value[0]) ? $value[0] : 'NULL';
				} else {
					$whereParams['Operator'] = $whereOperator;
					$whereParams['Value'] = $value;
				}
				
				$query->where('AND', $field, $whereParams['Operator'], $whereParams['Value']);
			}
		}
		
		if (isset($param['Order'])) {
			foreach($param['Order'] AS $field => $value) {
				$query->order($field, $value);
			}
		}
		
		if ($offset || $dist) {
			$query->limit($offset, $dist);
		}
		
		switch($returnType) {
			case 'CLASS':
				return $query->fetch('CLASSLATE', get_class($this));
				break;
				
			default:
				return $query->fetch();
				break;
		}
		
		return array();
	}
	
	public function finds(array $param, $offset = 0, $dist = 0, $returnType = 'CLASS') {
		return $this->fetch($param, $offset, $dist, $returnType, $whereOperator = 'LIKE');
	}
	
	public function getByPK($key, $returnType = 'CLASS') {
		return $this->getInKey($this->primary, $key, array(), $returnType);
	}
	
	public function fetchByPKs($keys, array $param = array(), $offset = 0, $dist = 0, $returnType = 'CLASS', $whereOperator = '=') {
		return $this->fetchInKeys($this->primary, $keys, $param, $offset, $dist, $returnType, $whereOperator);
	}
	
	public function getInKey($keyField, $value, $param = array(), $returnType = 'CLASS', $whereOperator = '=') {
		$data = array();
		
		if ($data = array_values($this->fetchInKeys($keyField, array($value), $param, 0, 1, $returnType, $whereOperator))) {
			if (isset($data[0])) {
				return $data[0];
			}
		}
		
		return false;
	}
	
	public function fetchInKeys($keyField, array $values, array $param = array(), $offset = 0, $dist = 0, $returnType = 'CLASS', $whereOperator = '=') {
		$fetched = $where = array();
		
		$param['Where'][$keyField] = array($values, 'IN');
		
		if ($fetched = $this->fetch($param, $offset, $dist, $returnType, $whereOperator)) {
			return $fetched;
		}
		
		return array();
	}
	
	private function fetchWith_JoinParamParser(array &$joinModels, array &$joinedMap, $parnetName = 'main') {
		if (is_array($joinModels)) {
			foreach($joinModels AS $jMkey => $jMVal) {
				if (!isset($jMVal['Field']) && $jMVal['Field']) {
					facula::core('debug')->exception('ERROR_ORM_FETCHWITH_JOIN_FIELDNAME_NOTSET', 'orm', true);
					
					return false;
					break;
				}
				
				if (!isset($jMVal['Model']) && $jMVal['Model']) {
					facula::core('debug')->exception('ERROR_ORM_FETCHWITH_JOIN_MODELNAME_NOTSET', 'orm', true);
					
					return false;
					break;
				}
				
				if (!isset($jMVal['Key']) && $jMVal['Key']) {
					facula::core('debug')->exception('ERROR_ORM_FETCHWITH_JOIN_MODELKEYNAME_NOTSET', 'orm', true);
					
					return false;
					break;
				}
				
				$tempJoinedModelAlias = isset($jMVal['Alias']) ? $jMVal['Alias'] : ($jMVal['Field']);
				$tempJoinedModelAddr = $parnetName . '.' . $tempJoinedModelAlias;
				
				$joinedMap[$tempJoinedModelAddr] = array(
					'Field' => $jMVal['Field'],
					'Model' => $jMVal['Model'],
					'Key' => $jMVal['Key'],
					'Alias' => $tempJoinedModelAlias,
					'Single' => isset($jMVal['Single']) && $jMVal['Single'] ? true : false,
					'Param' => isset($jMVal['Param']) ? $jMVal['Param'] : array(),
					'With' => $parnetName,
				);
				
				if (isset($jMVal['With'])) {
					$this->fetchWith_JoinParamParser($jMVal['With'], $joinedMap, $tempJoinedModelAddr);
				}
			}
		} else {
			facula::core('debug')->exception('ERROR_ORM_FETCHWITH_JOIN_WITH_INVALID', 'orm', true);
		}
		
		return false;
	}
	
	private function fetchWith_GetColumnDataRootRef(array &$dataMap, $dataMapName, $elementKey) {
		$result = array();
		
		if (isset($dataMap[$dataMapName])) {
			foreach($dataMap[$dataMapName] AS $key => $val) {
				if (isset($val[$elementKey])) {
					$result[$val[$elementKey]] = &$dataMap[$dataMapName][$key];
				}
			}
		}
		
		return $result;
	}
	
	public function getWith(array $joinModels, array $whereParams, $whereOperator = '=') {
		$data = array();
		$currentParams = array(
			'Where' => $whereParams,
		);
		
		if ($data = $this->fetchWith($joinModels, $currentParams, 0, 1, $whereOperator)) {
			if (isset($data[0])) {
				return $data[0];
			}
		}
		
		return false;
	}
	
	public function fetchWith(array $joinModels, array $currentParams, $offset = 0, $dist = 0, $whereOperator = '=') {
		$principals = $participants = array();
		$principal = $participant = null;
		
		$joinedMap = $dataMap = $colAddress = array();
		
		/*************
			$joinModels = array(
				array(
					'Field' => 'TargetField', // Field name of the key in primary table
					'Model' => 'ModelName2', // Model name of the table you want to join
					'Key' => 'JoinedKey', // Field name of the key use to query
					'Alias' => 'JoinResultASFieldName', // Save result in to another name
					'Single' => true, // Only return one result, use for primary or unique field
					'Param' => array( // Fetch params for joined table
						'Where' => array(
							'key' => 'val',
						)
					)
					'With' => array( // Join Sub table of this sub table
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
		*************/
		
		if ($principals = $this->fetch($currentParams, $offset, $dist, 'CLASS', $whereOperator)) {
			// First step is, fetch data from master table, and save reference to total reference map
			foreach($principals AS $principalKey => $principal) {
				$dataMap['main'][$principalKey] = &$principal->getDataRef();
			}
			
			// Handle With Joined Params after master table successful queried
			$this->fetchWith_JoinParamParser($joinModels, $joinedMap, 'main');
			
			// Now, Init data container for joined tables
			foreach($joinedMap AS $joinedMapKey => $joinedMapVal) {
				$dataMap[$joinedMapKey] = array();
				
				$joinedMap[$joinedMapKey]['Data'] = &$dataMap[$joinedMapKey];
			}
			
			// Query joined table one by one
			foreach($joinedMap AS $joinedKey => $JoinedVal) {
				if ($participant = facula::core('object')->getInstance($JoinedVal['Model'], array(), true)) {
					$tempJoinedKeys = $this->fetchWith_GetColumnDataRootRef($dataMap, $JoinedVal['With'], $JoinedVal['Field']);
					
					if (!empty($tempJoinedKeys) && ($participants = $participant->fetchInKeys($JoinedVal['Key'], array_keys($tempJoinedKeys), $JoinedVal['Param']))) {
						foreach($participants AS $participantKey => $participantVal) {
							$JoinedVal['Data'][$participantKey] = $participantVal->getData();
							
							if (isset($tempJoinedKeys[$participantVal[$JoinedVal['Key']]][$JoinedVal['Alias']]) && !is_array($tempJoinedKeys[$participantVal[$JoinedVal['Key']]][$JoinedVal['Alias']])) {
								$tempJoinedKeys[$participantVal[$JoinedVal['Key']]][$JoinedVal['Alias']] = array();
							}
							
							if ($JoinedVal['Single'] && empty($tempJoinedKeys[$participantVal[$JoinedVal['Key']]][$JoinedVal['Alias']])) {
								$tempJoinedKeys[$participantVal[$JoinedVal['Key']]][$JoinedVal['Alias']] = &$JoinedVal['Data'][$participantKey];
							} else {
								$tempJoinedKeys[$participantVal[$JoinedVal['Key']]][$JoinedVal['Alias']][] = &$JoinedVal['Data'][$participantKey];
							}
						}
					}
				}
			}
			
			return $principals;
		}
		
		return array();
	}
	
	public function save() {
		$primaryKey = $result = null;
		
		if (isset($this->data[$this->primary])) {
			if (isset($this->dataOriginal[$this->primary])) {
				$primaryKey = $this->dataOriginal[$this->primary];
			} else {
				$primaryKey = $this->data[$this->primary];
			}
			
			if ($result = query::from($this->table)->update($this->fields)->set($this->data)->where('AND', $this->primary, '=', $primaryKey)->save()) {
				$this->dataOriginal = $this->data;
				
				return $result;
			}
		} else {
			facula::core('debug')->exception('ERROR_ORM_SAVE_PRIMARY_KEY_NOTSET', 'orm', true);
		}
		
		return false;
	}
	
	public function insert() {
		$result = null;
		$keys = array();
		$data = $this->data;
		
		foreach($data AS $key => $val) {
			if (isset($data[$key]) && isset($this->fields[$key])) {
				$keys[$key] = $this->fields[$key];
			} else {
				facula::core('debug')->exception('ERROR_ORM_INSERT_FIELD_NOTSET|' . $key, 'query', true);
			}
		}
		
		if ($result = query::from($this->table)->insert($keys)->value($data)->save($this->primary)) {
			$this->dataOriginal = $this->data;
			
			return $result;
		}
		
		return false;
	}
	
	public function delete() {
		$result = null;
		
		if (isset($this->data[$this->primary])) {
			if ($result = query::from($this->table)->delete($this->fields)->where('AND', $this->primary, '=', $this->data[$this->primary])->save()) {
				$this->dataOriginal = $this->data = array();
				
				return $result;
			}
		} else {
			facula::core('debug')->exception('ERROR_ORM_SAVE_PRIMARY_KEY_NOTSET', 'orm', true);
		}
		
		return false;
	}
}

?>