<?php 

class handlerIndex extends Controller {
	private $settings = array();
	
	// Read setting and essentials
	public function __construct() {
		
	}
	
	// do some wakeup init
	public function _inited() {
	
	}
	
	public function get(&$request) {
		$message = 'hello word';
		echo $message;
		
		
		/*
		
		$q = new query();
		
		$q->get('table');
		
		$q->fetch('table', array(
			'field1' => 'value1',
			'field2' => 'value2',
		));
		
		$q->update('table', 
					
					array(
						'field1' => 'value1',
						'field2' => 'value2',
					), 
					
					array(
						array(
							'and',
							'field1',
							'=',
							'b',
						)
					)
					
					);
		
		
		*/
		
		
		$pdoInfo = facula::core('pdo')->getConnection(array('Table' => 'settings', 'Operation' => 'Read'));
		$pdoInfo2 = facula::core('pdo')->getConnection(array('Table' => 'settings', 'Operation' => 'Write'));
		/*
		if ($result = $pdoInfo->query("SELECT * FROM `settings`")) {
			print_r($result->fetchAll(PDO::FETCH_ASSOC));
		}
		*/
		// facula::core('response')->setCookie('COOKIENEW2', 'asdasdsadsad');
		
		// echo facula::core('request')->getCookie('COOKIENEW2');
		
		// facula::core('response')->send();
		
		// facula::core('response')->setContent($message);
		
		// facula::core('response')->send();
	}
	
	public function post(&$request) {
		echo "post";
	}
}

?>