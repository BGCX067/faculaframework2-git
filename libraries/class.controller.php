<?php 

/*****************************************************************************
	Facula Framework Controller Base Unit

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
	
	protected function display($tplName, $cacheExpired = 0, $cacheExpiredCallback = null) {
		if (isset($this->template)) {
			if ($this->response->setContent($this->template->render($tplName, $cacheExpired, $cacheExpiredCallback))) {
				return $this->response->send();
			}
		} else {
			$this->debug->exception('ERROR_CONTROLLER_CORE_INACTIVE_TEMPLATE', 'controller', true);
		}
		
		return false;
	}
}

?>