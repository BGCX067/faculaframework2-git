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
	protected $server = '';
	
	protected $serverInfo = array();
	
	public function __construct($server) {
		$this->server = $server;
		$this->socket = $this->getSocket($this->server['Host'], $this->server['Port'], $this->server['Timeout']);
		
		$this->socket->addResponseParser(250, function($param) {
			$params = explode(' ', $param, 64);
			
			file_put_contents(PROJECT_ROOT . '\\smtp_info.txt', "Info: 250, Parsing {" . implode(',', $params) . "};\r\n", FILE_APPEND);
			
			switch(strtolower($params[0])) {
				case 'size':
					if (isset($params[1])) {
						$this->serverInfo['MailMaxSize'] = intval($params[1]);
					}
					break;
					
				case '8bitmime':
					$this->serverInfo['8BITMIME'] = true;
					break;
					
				case 'pipelining':
					$this->serverInfo['PIPELINING'] = true;
					break;
					
				case 'auth':
					if (isset($params[1])) {
						$this->serverInfo['AuthMethods'] = explode($params[1], 16);
					}
					break;
			}
			
			return 250;
		});
		
		return true;
	}
	
	public function connect(&$error) {
		$result = false;
		$errorMsg = '';
		$errorNo = 0;
		$response = '';
		
		//facula::core('debug')->criticalSection(true);
		
		if ($this->socket->open($errorNo, $errorMsg)) {
			// Server response us?
			if ($this->socket->getLast(true) != 220) {
				$error = 'ERROR_SMTP_SERVER_RESPONSE_INVALID';
				$this->disconnect();
				
				return false;
			}
			
			// First talk: Greeting
			// Server will return some info about itself.
			if ($this->socket->put('EHLO ' . $this->server['Host'], true) != 250) {
				$error = 'ERROR_SMTP_SERVER_RESPONSE_EHLO_FAILED';
				$this->disconnect();
				
				return false;
			}
			
			// Next should be AUTH, Read AUTH type from $this->serverInfo['AuthMethods']
			
			
			$this->disconnect();
			
			echo "connected";
			
		} else {
			$error = $errorNo . ':' . $errorMsg;
			echo "errored";
		}
		
		//facula::core('debug')->criticalSection(false);
		
		return $result;
	}
	
	public function send(array $email) {
		
	}
	
	public function disconnect() {
		$this->socket->close();
	}

}

?>