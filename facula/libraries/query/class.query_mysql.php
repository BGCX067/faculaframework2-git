<?php 

class query_mysql implements queryInterface {
	private $dbh = null;
	
	private $table = '';
	
	public function __construct($table) {
		$this->table = $table;
	}
	
	public function select(&$settings) {
		$sql = 'SELECT';
		
		// Adding fields
		if (isset($settings['FIELDS'])) {
			$sql .= ' `' . implode('`, `', $settings['FIELDS']) . '`';
		} else {
			$sql .= ' * ';
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
		
		return $sql;
	}
	
	public function insert(&$settings) {
	
	}
	
	public function update(&$settings) {
	
	}
	
	public function delete(&$settings) {
	
	}
	
	private function parseCondition(&$wheres) {
		$sql = '';
		
		foreach($wheres AS $key => $where) {
			if ($sql) {
				if (isset($where['RELATION'])) {
					$sql .= strtoupper($where['RELATION']) . " (`{$where['FIELD']}` {$where['SIGN']} :{$where['VALUEKEY']}) ";
				} else {
					$sql .= "AND (`{$where['FIELD']}` {$where['SIGN']} :{$where['VALUEKEY']}) ";
				}
			} else {
				$sql .= "(`{$where['FIELD']}` {$where['SIGN']} :{$where['VALUEKEY']}) ";
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