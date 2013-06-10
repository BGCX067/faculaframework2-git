<?php 

/*****************************************************************************
	Facula Framework Simple Object Relation Mapping

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
}

class SimpleORM implements ormInterface {
	protected $table = '';
	protected $fields = array();
	protected $primary = '';
	
	protected $data = array();
	
	public function __construct($table, $fields, $default = array()) {
		if (empty($this->table)) {
			$this->table = $table;
		}
		
		if (empty($this->fields)) {
			$this->fields = $fields;
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
			facula::core('debug')->exception('ERROR_ORM_SET_FIELDS_NOT_EXISTED|' . $key, 'query', true);
		}
		
		return false;
	}
	
	public function __get($key) {
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}
	
	public function get($param) { // array('FieldName' => 'Value');
		$data = array();
		
		if (($data = $this->fetch($param, 0, 1)) && isset($data)) {
			return $data[0];
		}
		
		return false;
	}
	
	public function fetch($param, $offset = 0, $dist = 0) {
		$query = null;
		$data = array();
		
		if ($this->table) {
			$query = query::from($this->table);
			$query->select($this->fields);
			
			foreach($param AS $field => $value) {
				$query->where('AND', $field, '=', $value);
			}
			
			if ($offset || $dist) {
				$query->limit($offset, $dist);
			}
			
			return $query->fetch('CLASSLATE', get_class($this));
		} else {
			facula::core('debug')->exception('ERROR_ORM_FETCH_TABLENAME_NOTSET', 'query', true);
		}
		
		return $data;
	}
	
	public function save() {
		$query = null;
		$data = array();
		
		if (isset($this->data[$this->primary])) {
			if ($this->table) {
				$data = $this->data;
				
				unset($data[$this->primary]);
				
				return query::from($this->table)->update($this->fields)->set($this->data)->where('AND', $this->primary, '=', $this->data[$this->primary])->save();
			} else {
				facula::core('debug')->exception('ERROR_ORM_FETCH_TABLENAME_NOTSET', 'query', true);
			}
		} else {
			facula::core('debug')->exception('ERROR_ORM_SAVE_PRIMARY_KEY_NOTSET', 'query', true);
		}
		
		return false;
	}
	
	public function insert() {
		$query = null;
		$keys = array();
		$data = array();
		
		if (!isset($this->data[$this->primary])) {
			if ($this->table) {
				$keys = $this->fields;
				$data = $this->data;
				
				unset($data[$this->primary], $keys[$this->primary]);
				
				return query::from($this->table)->insert($keys)->value($data)->save();
			} else {
				facula::core('debug')->exception('ERROR_ORM_FETCH_TABLENAME_NOTSET', 'query', true);
			}
		} else {
			facula::core('debug')->exception('ERROR_ORM_INSERT_PRIMARY_KEY_MUST_NOTSET', 'query', true);
		}
		
		return false;
	}
}

?>