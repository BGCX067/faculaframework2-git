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
	protected $lastForwardServerRCPT = array();
	
	public function __construct($server) {
		$this->server = $server;
		$this->socket = $this->getSocket($this->server['Host'], $this->server['Port'], $this->server['Timeout']);
		
		$this->socket->registerResponseParser(250, function($param) {
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
					
				case 'starttls':
					$this->serverInfo['STARTTLS'] = true;
					break;
				
				case 'auth':
					if (isset($params[1])) {
						$this->serverInfo['AuthMethods'] = explode(' ', strtolower($params[1]), 16);
					}
					break;
			}
			
			return 250;
		});
		
		$this->socket->registerResponseParser(551, function($param) {
			file_put_contents(PROJECT_ROOT . '\\smtp_info.txt', "Info: 551, Parsing {" . implode(',', $params) . "};\r\n", FILE_APPEND);
			
			$newAddrs = array();
			
			if (preg_match('/\<(.*)\>/ui', $param, $newAddrs)) {
				if (isset($newAddrs[1]) && Validator::check($newAddrs[1], 'email', 512, 1)) {
					$this->lastForwardServerRCPT[] = $newAddrs[1];
				}
			}
			
			return 551;
		});
		
		return true;
	}
	
	public function connect(&$error) {
		$errorMsg = '';
		$errorNo = 0;
		$response = '';
		
		if ($this->socket->open($errorNo, $errorMsg)) {
			// Server response us?
			if ($this->socket->get(true) != 220) {
				$error = 'ERROR_SMTP_SERVER_RESPONSE_INVALID';
				$this->disconnect();
				
				return false;
			}
			
			// First talk: Greeting
			// Server will return some info about itself.
			if ($this->socket->put('EHLO ' . $this->server['Host'], 'last') != 250) {
				$error = 'ERROR_SMTP_SERVER_RESPONSE_EHLO_FAILED';
				$this->disconnect();
				
				return false;
			}
			
			// Next should be AUTH, Read AUTH type from $this->serverInfo['AuthMethods']. 
			// We don't need to bother with TLS since this will be done for other type of server operator
			if (isset($this->serverInfo['AuthMethods']) && $this->server['Username']) {
				if (!$this->getAuth($this->serverInfo['AuthMethods'])->auth($this->server['Username'], $this->server['Password'], $errorMsg)) {
					$error = 'ERROR_SMTP_SERVER_RESPONSE_AUTH_FAILED' . ($errorMsg ? '_' . $errorMsg : '');
					$this->disconnect();
					
					return false;
				}
			}
			
			// Now, tell SMTP server which email address we want to use on sending email
			if ($this->socket->put('MAIL FROM: <' . $this->server['From'] . '>', 'one') != 250) {
				$error = 'ERROR_SMTP_SERVER_RESPONSE_SET_MAILFORM_FAILED';
				$this->disconnect();
				
				return false;
			}
			
			return true;
		} else {
			$error = $errorNo . ':' . $errorMsg;
		}
		
		return false;
	}
	
	public function send(array &$email) {
		$mailInfo = $this->lastForwardServerRCPT = array(); // Reset lastForwardServerRCPT array for retry sending
		
		foreach($email['Receivers'] AS $receiver) {
			switch($this->socket->put('RCPT TO: <' . $receiver . '>', 'one')) {
				case 250:
					// Good
					break;
					
				case 251:
					// Will, it will be send anyway
					break;
					
				case 551:
					// Yeah, we just try it once
					foreach($this->lastForwardServerRCPT AS $forwards) {
						$this->socket->put('RCPT TO: <' . $forwards . '>');
					}
					break;
			}
		}
		
		if ($this->socket->put('DATA', 'one') == 354) {
			file_put_contents(PROJECT_ROOT . '\\smtp_mail.txt', 'Sending new mail' . "\r\n", FILE_APPEND);
			
			$mailInfo = array(
				'Title' => $email['Title'],
				'Message' => $email['Message'],
				'Screenname' => $this->server['Screenname'],
				'From' => $this->server['From'],
				'ReplyTo' => $this->server['ReplyTo'],
				'ReturnTo' => $this->server['ReturnTo'],
				'ErrorTo' => $this->server['ErrorTo'],
				'Charset' => $this->server['Charset'],
				'SenderIP' => $this->server['SenderIP'],
			);
			
			file_put_contents(PROJECT_ROOT . '\\smtp_mail_flat.txt', 'email: ' . $this->getData($mailInfo)->get(), FILE_APPEND);
			
			foreach(explode("\n", $this->getData($mailInfo)->get()) AS $line) {
				file_put_contents(PROJECT_ROOT . '\\mail.txt', $line, FILE_APPEND);
				
				if ($line == '.') {
					$line = '. ';
				}
				
				$this->socket->put($line, false);
			}
			
			if ($this->socket->put("\r\n.\r\n", 'one') == 250) {
				return true;
			}
		}
		
		return false;
	}
	
	public function disconnect() {
		$this->socket->close();
	}

}

?>