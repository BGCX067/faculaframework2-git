<?php 

/*****************************************************************************
	Facula Framework SMTP Operator
	
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

class smtp_general extends SMTPBase {
	protected $connection = '';
	protected $server = '';
	
	public function __construct($server) {
		$this->server = $server;
		
		return true;
	}
	
	public function connect(&$error) {
		$result = false;
		$errorMsg = '';
		$errorNo = 0;
		$response = '';
		
		//facula::core('debug')->criticalSection(true);
		
		if ($this->socketOpen($this->server['Host'], $this->server['Port'], $this->server['Timeout'], $errorNo, $errorMsg)) {
			$response = $this->socketGet();
			
			echo "connected";
		} else {
			$error = $errorNo . ':' . $errorMsg;
			echo "errored";
		}
		
		//facula::core('debug')->criticalSection(false);
		
		return $result;
	}
	
	public function send(array $email) {
		adasds
	}
	
	public function disconnect() {
	
	}

}

?>