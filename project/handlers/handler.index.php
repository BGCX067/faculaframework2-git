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
		
		facula::core('response')->setContent($message);
		
		facula::core('response')->send();
	}
	
	public function post(&$request) {
		echo "post";
	}
}

?>