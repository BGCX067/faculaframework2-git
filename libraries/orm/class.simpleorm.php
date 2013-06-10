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
	
	public function get($param);
	public function fetch($param, $offset = 0, $dist = 0);
	
	public function getByPK($id);
	public function fetchByPKs($pks);
	
	public function fetchWith($ormObjectName, $keyName, $paramsForCurrent, $offset = 0, $dist = 0);
	
	public function save();
	public function insert();
}

class SimpleORM implements ormInterface {
	protected $table = '';
	protected $fields = array();
	protected $primary = '';
	
	protected $data = array();
	
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
		if (isset($this->fields[$key])) {
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
	
	public function get($param) { // array('FieldName' => 'Value');
		$data = array();
		
		if (($data = $this->fetch($param, 0, 1)) && isset($data[0])) {
			return $data[0];
		}
		
		return false;
	}
	
	public function getPrimaryValue() {
		return isset($this->data[$this->primary]) ? $this->data[$this->primary] : null;
	}
	
	public function getFields() {
		return $this->fields;
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function fetch($param, $offset = 0, $dist = 0) {
		$query = null;
		
		$query = query::from($this->table);
		$query->select($this->fields);
		
		foreach($param AS $field => $value) {
			$query->where('AND', $field, '=', $value);
		}
		
		if ($offset || $dist) {
			$query->limit($offset, $dist);
		}
		
		return $query->fetch('CLASSLATE', get_class($this));
	}
	
	public function getByPK($id) {
		$data = $ids = array();
		
		$ids[] = $id;
		
		if (($data = array_values($this->fetchByPKs($ids))) && isset($data[0])) {
			return $data[0];
		}
		
		return false;
	}
	
	public function fetchByPKs($pks) {
		$fetched = $result = array();
		
		if ($fetched = query::from($this->table)->select($this->fields)->where('AND', $this->primary, 'IN', $pks)->fetch('CLASSLATE', get_class($this))) {
			// Convert primary key as array index key
			foreach($fetched AS $object) {
				$result[$object->getPrimaryValue()] = $object;
			}
			
			return $result;
		}
		
		return array();
	}
	
	public function fetchWith($ormObjectName, $keyName, $paramsForCurrent, $offset = 0, $dist = 0) {
		$fetchedObjects = $targetFetched = $keys = $keyToObjMap = array();
		$targetORMObject = null;
		
		if ($fetchedObjects = $this->fetch($paramsForCurrent, $offset, $dist)) {
			foreach($fetchedObjects AS $object) {
				if (isset($object->$keyName)) { // Key must set
					if ($object->$keyName) { // Key must not empty
						$keys[] = $object->$keyName;
						
						$keyToObjMap[$object->$keyName] = $object; // Object should auto referenced
					}
				} else {
					facula::core('debug')->exception('ERROR_ORM_FETCHWITH_KEY_NOT_EXIST|' . $keyName, 'orm', true);
					return false;
				}
			}
			
			$targetORMObject = new $ormObjectName();
			
			if ($targetFetched = $targetORMObject->fetchByPKs(array_unique($keys))) {
				foreach($targetFetched AS $key => $targetObject) {
					if (isset($keyToObjMap[$key])) {
						$keyToObjMap[$key]->$keyName = $targetObject;
					}
				}
			}
			
			return $fetchedObjects;
		}
		
		return array();
	}
	
	public function save() {
		$data = array();
		
		if (isset($this->data[$this->primary])) {
			$data = $this->data;
			
			unset($data[$this->primary]);
			
			return query::from($this->table)->update($this->fields)->set($this->data)->where('AND', $this->primary, '=', $this->data[$this->primary])->save();
		} else {
			facula::core('debug')->exception('ERROR_ORM_SAVE_PRIMARY_KEY_NOTSET', 'orm', true);
		}
		
		return false;
	}
	
	public function insert() {
		$keys = array();
		$data = array();
		
		if (!isset($this->data[$this->primary])) {
			$keys = $this->fields;
			$data = $this->data;
			
			unset($data[$this->primary], $keys[$this->primary]);
			
			return query::from($this->table)->insert($keys)->value($data)->save();
		} else {
			facula::core('debug')->exception('ERROR_ORM_INSERT_PRIMARY_KEY_MUST_NOTSET', 'query', true);
		}
		
		return false;
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