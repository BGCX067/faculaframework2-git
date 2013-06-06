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

class ORM {
	// Empty until i know how to make it.
	
	/* // Need time to rework
	protected $data = array();
	
	static public function getModel($model) {
		$modelInstance = new $model();
		
		if ($modelInstance instanceof self) {
			return $modelInstance;
		} else {
			// ERROR
		}
		
		return false;
	}
	
	public function __set($key, $val) {
		$this->data[$key] = $val;
		
		return true;
	}
	
	public function __get($key) {
		if (isset($this->data[$key])) {
			return $this->data[$key];
		}
		
		return null;
	}
	
	public function __isset($key) {
		if (isset($this->data[$key])) {
			return true;
		}
	
		return true;
	}
	
	public function getByPK($value) {
		if (!isset($this->primary)) {
			facula::core('debug')->exception('ERROR_MODEL_GETBYPK_PRIMARY_NOTSET', 'model', true);
			return false;
		}
		
		return $this->get($this->primary, $value);
	}
	
	public function get($field, $value) {
		if (!isset($this->table)) {
			facula::core('debug')->exception('ERROR_MODEL_GET_TABLE_NOTSET|' . $field, 'model', true);
			return false;
		}
		
		if (!isset($this->fields[$field])) {
			facula::core('debug')->exception('ERROR_MODEL_GET_FIELD_NOTFOUND|' . $field, 'model', true);
			return false;
		}
		
		if ($this->data = query::from($this->table)->select($this->fields)->where($field, '=', [$value, $this->fields[$field]])->get()) {
			return true;
		}
		
		return false;
	}
	
	public function save() {
		$dataToSave = array();
		
		if (!isset($this->table)) {
			facula::core('debug')->exception('ERROR_MODEL_SAVE_TABLENAME_NOTSET', 'model', true);
			return false;
		}
		
		if (!isset($this->fields)) {
			facula::core('debug')->exception('ERROR_MODEL_SAVE_FIELDS_NOTSET', 'model', true);
			return false;
		}
		
		if (!isset($this->fields[$this->primary])) {
			facula::core('debug')->exception('ERROR_MODEL_SAVE_PRIMARY_NOTFOUND', 'model', true);
			return false;
		}
		
		foreach($this->fields AS $key => $fieldType) {
			if (isset($this->data[$key]) && $key != $this->primary) {
				$dataToSave[$key] = $this->data[$key];
			}
		}
	
		if (query::from($this->table)->update($dataToSave)->where($this->primary, '=', [$this->primary, $this->fields[$this->primary]])->save()) {
			return true;
		}
		
		return false;
	}
	
	*/
}

?>