<?php 

interface handlerInterface {
	public function get(&$request);
	public function post(&$request);
}

abstract class Controller implements handlerInterface {
	protected function core($name) {
		return facula::core($name);
	}
	
	protected function getRequest($key) {
		return facula::core('request')->request($key);
	}
	
	public function _run() {
		$request = facula::core('request');
		
		if ($request->method == 'POST') {
			return $this->post($request);
		} else {
			return $this->get($request);
		}
		
		return false;
	}
}

?>