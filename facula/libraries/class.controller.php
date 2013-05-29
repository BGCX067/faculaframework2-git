<?php 

interface controllerInterface {
	public function _run();
}

abstract class Controller implements controllerInterface {
	public function _run() {
		foreach(facula::getAllCores() AS $coreName => $coreReference) {
			$this->$coreName = $coreReference;
		}
	
		$method = $this->request->getClientInfo('method');
		
		if (method_exists($this, $method)) {
			return $this->$method($this->request);
		} else {
			$this->response->setHeader('HTTP/1.1 405 Method Not Allowed');
			$this->response->send();
			return false;
		}
	}
	
	protected function getGet($key) {
		return $this->request->getGet($key);
	}
	
	protected function getPost($key) {
		return $this->request->getPost($key);
	}
	
	protected function getCookie($key) {
		return $this->request->getCookie($key);
	}
	
	protected function redirect($addr, $httpcode, $interior = true) {
		$rootUrl = $interior ? $this->request->getClientInfo('rootURL') . '/' : '';
		
		switch($httpcode) {
			case 301:
				$this->response->setHeader('HTTP/1.1 302 Moved Permanently');
				
			case 302:
				$this->response->setHeader('HTTP/1.1 302 Moved Temporarily');
				
			default:
				$this->response->setHeader('Location: ' . $rootUrl . $addr);
				break;
		}
		
		return $this->response->send();
	}
	
	protected function assign($key, $val) {
		if (isset($this->template)) {
			if ($this->template->assign($key, $val)) {
				return true;
			}
		} else {
			$this->debug->exception('ERROR_CONTROLLER_CORE_INACTIVE_TEMPLATE', 'controller', true);
		}
		
		return false;
	}
	
	protected function display($tplName) {
		if (isset($this->template)) {
			if ($this->response->setContent($this->template->render($tplName))) {
				return $this->response->send();
			}
		} else {
			$this->debug->exception('ERROR_CONTROLLER_CORE_INACTIVE_TEMPLATE', 'controller', true);
		}
		
		return false;
	}
}

?>