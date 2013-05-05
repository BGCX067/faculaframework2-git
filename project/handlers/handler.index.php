<?php 

class handlerIndex extends Controller implements Handler {
	private $settings = array();
	
	// Read setting and essentials
	public function __construct() {
		
	}
	
	// do some wakeup init
	public function _inited() {
		
	}
	
	public function get(faculaRequest &$request) {
		echo "sadsad";
	}
	
	public function post(faculaRequest &$request) {
		echo "post";
	}
}

?>