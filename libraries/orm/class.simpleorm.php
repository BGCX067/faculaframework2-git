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
	public function __construct($table = '', $fields = array(), $primary = '', $default = array()); // Actually, you better not set your own __construct if you using this orm
	public function __set($key, $val);
	public function __get($key);
	public function __isset($key);
	
	public function getPrimaryValue();
	public function getFields();
	public function getData();
	
	public function get($param);
	public function fetch($param, $offset = 0, $dist = 0, $returnType = 'CLASS', $whereOperator = '=');
	
	public function finds($param, $offset = 0, $dist = 0, $returnType = 'CLASS');
	
	public function getInKey($keyField, $value);
	public function fetchInKeys($keyField, $values);
	
	public function getByPK($key);
	public function fetchByPKs($keys);
	
	public function fetchWith($joinModels, $currentParams, $offset = 0, $dist = 0, $whereOperator = '=');
	
	public function save();
	public function insert();
	public function delete();
}

class SimpleORM implements ormInterface {
	protected $table = '';
	protected $fields = array();
	protected $primary = '';
	
	private $data = array();
	
	public function __construct($table = '', $fields = array(), $primary = '', $default = array()) {
		if (empty($this->table) && !($this->table = $table)) {
			facula::core('debug')->exception('ERROR_ORM_TABLENAME_MUST_SET', 'orm', true);
		}
		
		if (empty($this->fields) && !($this->fields = $fields)) {
			facula::core('debug')->exception('ERROR_ORM_FIELDS_MUST_SET', 'orm', true);
		}
		
		if (empty($this->primary) && !($this->primary = $primary)) {
			facula::core('debug')->exception('ERROR_ORM_PRIMARYKEY_MUST_SET', 'orm', true);
		}
		
		if (empty($this->data)) {
			$this->data = $default;
		}
		
		return true;
	}
	
	public function __set($key, $val) {
		if (isset($this->fields[$key]) || $key[0] == '_') {
			$this->data[$key] = $val;
		} else {
			facula::core('debug')->exception('ERROR_ORM_SET_FIELDS_NOT_EXISTED|' . $key, 'orm', true);
		}
		
		return false;
	}
	
	public function __get($key) {
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}
	
	public function __isset($key) {
		return isset($this->data[$key]);
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
	
	public function get($param) { // array('FieldName' => 'Value');
		$data = array();
		
		if (($data = $this->fetch(array('Where' => $param), 0, 1)) && isset($data[0])) {
			return $data[0];
		}
		
		return false;
	}
	
	public function fetch($param, $offset = 0, $dist = 0, $returnType = 'CLASS', $whereOperator = '=') {
		$query = null;
		
		$query = query::from($this->table);
		$query->select($this->fields);
		
		if (isset($param['Where'])) {
			foreach($param['Where'] AS $field => $value) {
				$query->where('AND', $field, $whereOperator, $value);
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
	
	public function finds($param, $offset = 0, $dist = 0, $returnType = 'CLASS') {
		return $this->fetch($param, $offset, $dist, $returnType, $whereOperator = 'LIKE');
	}
	
	public function getByPK($key) {
		return $this->getInKey($this->primary, $key);
	}
	
	public function fetchByPKs($keys) {
		return $this->fetchInKeys($this->primary, $keys);
	}
	
	public function getInKey($keyField, $value) {
		$data = $values = array();
		
		$values[] = $value;
		
		if ($data = array_values($this->fetchInKeys($keyField, $values))) {
			foreach($data AS $d) {
				return $d; // Return the first element
				break;
			}
		}
		
		return false;
	}
	
	public function fetchInKeys($keyField, $values) {
		$fetched = $result = array();
		
		if ($fetched = query::from($this->table)->select($this->fields)->where('AND', $keyField, 'IN', $values)->fetch('CLASSLATE', get_class($this))) {
			// Convert primary key as array index key
			foreach($fetched AS $object) {
				$result[$object->$keyField] = $object; // It will just return one result if key value is the same.
			}
			
			return $result;
		}
		
		return array();
	}
	
	public function fetchWith($joinModels, $currentParams, $offset = 0, $dist = 0, $whereOperator = '=') {
		$principals = $joined = array();
		
		$lastJoinedModel = null;
		
		/*************
			$joinModels = array(
				array(
					'Field' => 'TargetField',
					'Model' => 'ModelName2',
					'Key' => 'JoinedKey',
					'As' => 'JoinResultASFieldName',
				),
				array(
					'Field' => 'TargetField',
					'Model' => 'ModelName2',
					'Key' => 'JoinedKey',
					'As' => 'JoinResultASFieldName',
				),
			);
		*************/
		
		if ($principals = $this->fetch($currentParams, $offset, $dist, 'CLASS', $whereOperator)) {
			// Scan all the needed key from principal results
			foreach($principals AS $principalKey => $principal) {
				foreach($joinModels AS $modelKey => $model) {
					if (isset($model['Field']) && isset($principal->$model['Field'])) {
						$joinModels[$modelKey]['NewKey'] = (isset($joinModels[$modelKey]['As']) ? '_' . $joinModels[$modelKey]['As'] : $joinModels[$modelKey]['Field']);
						$joinModels[$modelKey]['InKeys'][] = $principal->$model['Field'];
						$joinModels[$modelKey]['InKeyMap'][$principal->$model['Field']][] = &$principals[$principalKey]; // Should by auto referenced actually.
					} else {
						facula::core('debug')->exception('ERROR_ORM_FETCHWITH_KEY_NOT_EXIST|' . $model['Field'], 'orm', true);
						return false;
						break; break;
					}
				}
			}
			
			// Get joined model one by one, get data out from it
			foreach($joinModels AS $modelKey => $modelSetting) {
				$lastJoinedModel = new $modelSetting['Model'](); // Create model instance
				
				if ($joined = $lastJoinedModel->fetchInKeys($modelSetting['Key'], $modelSetting['InKeys'])) { // Create model instance for each result
					foreach($joined AS $joinedKey => $joinedObj) {
						// If found the property we needed
						if (isset($modelSetting['InKeyMap'][$joinedKey])) {
							foreach($modelSetting['InKeyMap'][$joinedKey] AS $inMap) {
								$inMap->$modelSetting['NewKey'] = $joinedObj->getData(); // Replace the principal with it.
							}
						}
						
						$joinedObj = null; // Try release it.
					}
				}
				
				$lastJoinedModel = null; // Unset this and hopefully, release memory.
			}
			
			return $principals;
		}
		
		return array();
	}
	
	public function save() {
		$data = array();
		
		if (isset($this->data[$this->primary])) {
			$data = $this->data;
			
			unset($data[$this->primary]); // No need to update primary key
			
			return query::from($this->table)->update($this->fields)->set($this->data)->where('AND', $this->primary, '=', $this->data[$this->primary])->save();
		} else {
			facula::core('debug')->exception('ERROR_ORM_SAVE_PRIMARY_KEY_NOTSET', 'orm', true);
		}
		
		return false;
	}
	
	public function insert() {
		$keys = array();
		$data = $this->data;
		
		foreach($data AS $key => $val) {
			if (isset($data[$key]) && isset($this->fields[$key])) {
				$keys[$key] = $this->fields[$key];
			} else {
				facula::core('debug')->exception('ERROR_ORM_INSERT_FIELD_NOTSET|' . $key, 'query', true);
			}
		}
		
		return query::from($this->table)->insert($keys)->value($data)->save($this->primary);
	}
	
	public function delete() {
		if (isset($this->data[$this->primary])) {
			return query::from($this->table)->delete($this->fields)->where('AND', $this->primary, '=', $this->data[$this->primary])->save();
		} else {
			facula::core('debug')->exception('ERROR_ORM_SAVE_PRIMARY_KEY_NOTSET', 'orm', true);
		}
		
		return false;
	}
}

?>