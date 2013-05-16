<?php 

interface handlerInterface {

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
		$method = $request->getClientInfo('method');
		
		if (method_exists($this, $method)) {
			return $this->$method($request);
		} else {
			facula::core('response')->setHeader('HTTP/1.0 405 Method Not Allowed');
			facula::core('response')->send();
			return false;
		}
	}
}

?>