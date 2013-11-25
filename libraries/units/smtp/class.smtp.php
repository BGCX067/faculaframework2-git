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

class smtp {
	static private $config = array();
	static private $emails = array();
	
	static public function init($cfg = array()) {
		$version = facula::getVersion();
		
		self::$config['Handler'] = $version['App'] . ' ' . $version['Ver'];
		self::$config['Charset'] = isset($cfg['Charset']) ? $cfg['Charset'] : 'utf-8';
		
		if (isset($cfg['Servers']) && is_array($cfg['Servers'])) {
			if (isset($cfg['SelectMethod']) && $cfg['SelectMethod'] == 'Random') {
				shuffle($cfg['Servers']);
			}
		
			foreach($cfg['Servers'] AS $key => $val) {
				self::$config['Servers'][$key] = array(
					'Type' => isset($val['Type']) ? $val['Type'] : 'general',
					'Host' => isset($val['Host']) ? $val['Host'] : 'localhost',
					'Port' => isset($val['Port']) ? $val['Port'] : 25,
					'Timeout' => isset($val['Timeout']) ? $val['Timeout'] : 1,
					'Username' => isset($val['Username']) ? $val['Username'] : 'nobody',
					'Password' => isset($val['Password']) ? $val['Password'] : 'nobody',
					'Handler' => self::$config['Handler'],
					'Charset' => self::$config['Charset'],
					'SenderIP' => IP::joinIP(facula::core('request')->getClientInfo('ipArray'), true),
				);
				
				// Set MAIL FROM, this one must be set for future use
				if (isset($val['From'])) {
					self::$config['Servers'][$key]['From'] = $val['From'];
				} else {
					self::$config['Servers'][$key]['From'] = 'postmaster@localhost';
				}
				
				// Set REPLY TO
				if (isset($val['ReplyTo'])) {
					self::$config['Servers'][$key]['ReplyTo'] = $val['ReplyTo'];
				} else {
					self::$config['Servers'][$key]['ReplyTo'] = self::$config['Servers'][$key]['Form'];
				}
				
				// Set RETURN TO
				if (isset($val['ReplyTo'])) {
					self::$config['Servers'][$key]['ReturnTo'] = $val['ReturnTo'];
				} else {
					self::$config['Servers'][$key]['ReturnTo'] = self::$config['Servers'][$key]['Form'];
				}
				
				// Set ERROR TO
				if (isset($val['ErrorTo'])) {
					self::$config['Servers'][$key]['ErrorTo'] = $val['ErrorTo'];
				} else {
					self::$config['Servers'][$key]['ErrorTo'] = self::$config['Servers'][$key]['Form'];
				}
			}
			
			facula::core('object')->addHook('response_finished', 'smtpoperatingtask', function() {
				// Hey, i must mark this. I can call a private method with hook by this way. Nice!
				self::sendMail();
			});
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_SMTP_NOSERVER', 'smtp', true);
		}
		
		return false;
	}
	
	static public function addMail($title, $body, array $receivers) {
		self::$emails = array(
			'Receivers' => $receivers,
			'Title' => $title,
			'Body' => $body,
		);
	}
	
	static private function sendMail() {
		$operater = null;
		$operaterClassName = $error = '';
		
		foreach(self::$config['Servers'] AS $server) {
			$operaterClassName = __CLASS__ . '_' . $server['Type'];
			
			if (class_exists($operaterClassName, true)) {
				$operater = new $operaterClassName($server);
				
				if (is_subclass_of($operater, 'SMTPBase')) {
					if ($operater->connect($error)) {
						
						foreach(self::$emails AS $email) {
							if (!$operater->send($email)) {
								return false;
							}
						}
						
						$operater->disconnect();
						
						return true;
					}
				} else {
					facula::core('debug')->exception('ERROR_SMTP_OPERATOR_BASE_INVALID', 'smtp', true);
				}
			} else {
				facula::core('debug')->exception('ERROR_SMTP_OPERATOR_NOTFOUND|' . $server['Type'], 'smtp', true);
			}
		}
		
		if ($error) {
			facula::core('debug')->exception('ERROR_SMTP_OPERATOR_ERROR|' . $error, 'smtp', false);
		}
		
		return false;
	}
}

class SMTPSocket {
	private $connection = null;
	private $responseParsers = array();
	
	private $host = 'localhost';
	private $port = 0;
	private $timeout = 0;
	
	public function __construct($host, $port, $timeout) {
		$this->host = $host;
		$this->port = $port;
		$this->timeout = $timeout;
		
		return true;
	}
	
	public function open(&$error, &$errorstr) {
		if (function_exists('fsockopen')) {
			if ($this->connection = fsockopen($this->host, $this->port, $error, $errorstr, $this->timeout)) {
				stream_set_blocking($this->connection, true);
				stream_set_timeout($this->connection, $this->timeout);
				
				return true;
			}
		} else {
			facula::core('debug')->exception('ERROR_SMTP_SOCKET_DISABED', 'smtp', true);
		}
		
		return false;
	}
	
	public function put($command, $getReturn = false) {
		file_put_contents(PROJECT_ROOT . '\\smtp.txt', "Sending: {$command}\r\n", FILE_APPEND);
		
		if ($this->connection) {
			if (fputs($this->connection, $command . "\r\n")) {
				if (!$getReturn) {
					return true;
				} else {
					return $this->getLast(true);
				}
			} else {
				facula::core('debug')->exception('ERROR_SMTP_SOCKET_NORESPONSE', 'smtp', false);
				return false;
			}
		}
	}
	
	public function get($parseResponse = false) {
		$response = null;
		
		if ($this->connection) {
			if ($response = trim(fgets($this->connection, 512))) {
				file_put_contents(PROJECT_ROOT . '\\smtp.txt', "Response: {$response}\r\n", FILE_APPEND);
			
				if ($parseResponse) {
					$response = $this->parseResponse($response);
				}
				
				return $response ? $response : null;
			}
		}
		
		return false;
	}
	
	public function getLast($parseResponse = false) {
		$response = $responseLast = null;
		
		while($response = $this->get($parseResponse)) {
			$responseLast = $response;
		}
		
		return $responseLast;
	}
	
	public function close() {
		if ($this->connection) {
			if ($this->put('QUIT')) {
				$this->connection = null;
				
				return true;
			};
		}
		
		return false;
	}
	
	public function addResponseParser($responseCode, Closure $parser) {
		if (!isset($this->responseParsers[$responseCode])) {
			$this->responseParsers[$responseCode] = $parser;
		}
		
		return false;
	}
	
	private function parseResponse($response) {
		$responseParam = $parserName = $splitType = '';
		$responseCode = $responseCode = 0;
		$responseParams = array();
		$parser = null;
		
		file_put_contents(PROJECT_ROOT . '\\smtp_info.txt', "Parse: {$response};\r\n", FILE_APPEND);
		
		if ($responseContent = trim($response)) {
			// Position check seems the only stable way is do determine which we will use ('-' OR ' ').
			if (($fstSpacePos = strpos($responseContent, ' ')) === false) {
				$fstSpacePos = null;
			}
			
			if (($fstDashPos = strpos($responseContent, '-')) === false) {
				$fstDashPos = null;
			}
			
			if (is_null($fstDashPos) && $fstSpacePos) {
				$splitType = 'SPACE';
			} elseif (is_null($fstSpacePos) && $fstDashPos) {
				$splitType = 'DASH';
			} elseif ($fstDashPos && $fstDashPos < $fstSpacePos) {
				$splitType = 'DASH';
			} elseif ($fstSpacePos && $fstSpacePos < $fstDashPos) {
				$splitType = 'SPACE';
			} else {
				$splitType = 'UNKONWN';
			}
			
			// Use splitType to determine how to split response
			switch($splitType) {
				case 'UNKONWN':
					$responseParams[] = $responseContent;
					break;
					
				case 'DASH':
					$responseParams = explode('-', $responseContent, 2);
					break;
					
				case 'SPACE':
					$responseParams = explode(' ', $responseContent, 2);
					break;
			}
			
			file_put_contents(PROJECT_ROOT . '\\smtp_info.txt', "Parse: {$response}; Split: {$splitType}; DashPOS: {$fstDashPos}; SpacePOS: {$fstSpacePos}; Param: " . implode(',', $responseParams) . "\r\n", FILE_APPEND);
			
			if (isset($responseParams[0]) && $responseParams[0] && is_numeric($responseParams[0])) {
				$responseCode = intval($responseParams[0]);
			}
			
			if (isset($responseParams[1]) && $responseParams[1]) {
				$responseParam = $responseParams[1];
			}
			
			// Check if parser's existed.
			if (isset($this->responseParsers[$responseCode])) {
				$parser = $this->responseParsers[$responseCode];
				
				return $parser($responseParam);
			}
			
			return $responseCode;
		}
		
		return false;
	}
}

abstract class SMTPBase {
	abstract public function connect(&$error);
	abstract public function send(array $email);
	abstract public function disconnect();
	
	final protected function getSocket($host, $port, $timeout) {
		return new SMTPSocket($host, $port, $timeout);
	}
	
	protected function makeBody() {
	
	}
}

?>