<?php 

/*****************************************************************************
	Facula Framework MySQL Syntax Builder for Query Unit

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

class query_mysql implements queryInterface {
	private $dbh = null;
	
	private $table = '';
	
	public function __construct($table) {
		$this->table = $table;
	}
	
	public function select(&$settings) {
		$sql = 'SELECT';
		
		// Adding fields
		if (isset($settings['FIELDS'][0])) {
			$sql .= ' `' . implode('`, `', $settings['FIELDS']) . '`';
		} else {
			$sql .= ' *';
		}
		
		// Adding Table
		$sql .= " FROM `{$this->table}`";
		
		// Add Where
		if (isset($settings['WHERE'])) {
			$sql .= ' WHERE ' . $this->parseCondition($settings['WHERE']);
		} else {
			$sql .= ' WHERE 1';
		}
		
		if (isset($settings['ORDERBY'])) {
			$sql .= ' ORDER BY ' . $this->parseOrder($settings['ORDERBY']);
		}
		
		if (isset($settings['LIMIT'])) {
			$sql .= ' LIMIT ' . $this->parseLimit($settings['LIMIT']);
		}
		
		return $sql . ';';
	}
	
	public function insert(&$settings) {
		$values = array();
		$sql = "INSERT INTO `{$this->table}`";
		
		if (isset($settings['FIELDS'])) {
			$sql .= ' (`' . implode('`, `', $settings['FIELDS']) . '`)';
			
			if (isset($settings['VALUEKEYS'])) {
				$sql .= ' VALUES';
				
				foreach($settings['VALUEKEYS'] AS $val) {
					$values[] = '(' . implode(', ', $val) . ')';
				}
				
				$sql .= ' ' . implode(', ', $values);
			}
			
			return $sql . ';';
		}
		
		return false;
	}
	
	public function update(&$settings) {
		$values = array();
		$sql = "UPDATE `{$this->table}`";
		
		// Add Sets
		if (isset($settings['VALUEKEYS']) && !empty($settings['VALUEKEYS'])) {
			$sql .= ' SET';
			
			foreach($settings['VALUEKEYS'] AS $key => $val) {
				$values[] = '`' . $key . '` = ' . $val;
			}
			
			$sql .= ' ' . implode(', ', $values);
			
			// Add Where
			if (isset($settings['WHERE'])) {
				$sql .= ' WHERE ' . $this->parseCondition($settings['WHERE']);
			} else {
				$sql .= ' WHERE 1';
			}
			
			return $sql . ';';
		}
		
		return false;
	}
	
	public function delete(&$settings) {
		$sql = "DELETE FROM `{$this->table}`";
		
		// Add Where
		if (isset($settings['WHERE'][0])) {
			$sql .= ' WHERE ' . $this->parseCondition($settings['WHERE']);
		} else {
			$sql .= ' WHERE 1';
		}
		
		return $sql;
	}
	
	private function parseCondition(&$wheres) {
		$sql = '';
		
		foreach($wheres AS $key => $where) {
			if ($sql) {
				if (isset($where['RELATION'])) {
					$sql .= strtoupper($where['RELATION']) . " (`{$where['FIELD']}` {$where['SIGN']} {$where['VALUEKEY']})";
				} else {
					$sql .= " AND (`{$where['FIELD']}` {$where['SIGN']} {$where['VALUEKEY']})";
				}
			} else {
				$sql .= "(`{$where['FIELD']}` {$where['SIGN']} {$where['VALUEKEY']})";
			}
		}
		
		return $sql;
	}
	
	private function parseOrder(&$orders) {
		$sql = $method = '';
		
		foreach($orders AS $order) {
			if ($order['METHOD'] == 'DESC' || $order['METHOD'] == 'ASC') {
				$method = $order['METHOD'];
			} else {
				$method = 'DESC';
			}
			
			if ($sql) {
				$sql .= ", `{$order['FIELD']}` {$order['METHOD']}";
			} else {
				$sql .= "`{$order['FIELD']}` {$order['METHOD']}";
			}
		}
		
		return $sql;
	}
	
	private function parseLimit(&$limit) {
		return "{$limit['START']}, {$limit['DURATION']}";
	}
}

?>