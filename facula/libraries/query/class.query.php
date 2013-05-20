<?php 

interface queryInterface {
	
}

class query {
	private $dbh = null;
	private $dbOperater = null;
	
	public function __construct($table, $operation) {
		$targetOperater = '';
		
		if ($this->dbh = facula::core('pdo')->getConnection(array('Table' => $table, 'Operation' => $operation))) {
			$targetOperater = 'query_' . $this->dbh->_connection['Database']['Driver'];
			
			if (class_exists($targetOperater)) {
				$this->dbOperater = new $targetOperater();
			} else {
				facula::core('debug')->exception('ERROR_QUERY_UNKNOWNPDODRIVER|' . $this->dbh->_connection['Database']['Driver'], 'data', true);
			}
		}
	}
	
	public function select() {}
	
	public function insert() {}
	
	public function update() {}
	
	public function delete() {}
	
}

?>