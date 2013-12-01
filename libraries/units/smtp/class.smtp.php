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

class SMTP {
	static private $config = array();
	static private $emails = array();
	
	static public function init($cfg = array()) {
		$version = array();
		$senderIP = '';
		
		if (empty(self::$config)) {
			$version = facula::getVersion();
			$senderIP = IP::joinIP(facula::core('request')->getClientInfo('ipArray'), true);
			
			self::$config['Handler'] = $version['App'] . ' ' . $version['Ver'];
			
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
						'Username' => isset($val['Username']) ? $val['Username'] : '',
						'Password' => isset($val['Password']) ? $val['Password'] : '',
						'Handler' => self::$config['Handler'],
						'SenderIP' => $senderIP,
					);
					
					// Set poster screen name, this will be display on the receiver's list
					if (isset($val['Screenname'])) {
						self::$config['Servers'][$key]['Screenname'] = $val['Screenname'];
					} else {
						self::$config['Servers'][$key]['Screenname'] = self::$config['Servers'][$key]['Username'];
					}
					
					// Set MAIL FROM, this one must be set for future use
					if (isset($val['From'])) {
						if (Validator::check($val['From'], 'email')) {
							self::$config['Servers'][$key]['From'] = $val['From'];
						} else {
							facula::core('debug')->exception('ERROR_SMTP_ADDRESS_FORM_INVALID|' . $val['From'], 'smtp', true);
						}
					} else {
						self::$config['Servers'][$key]['From'] = self::$config['Servers'][$key]['Username'] . '@' . self::$config['Servers'][$key]['Host'];
					}
					
					// Set REPLY TO
					if (isset($val['ReplyTo'])) {
						if (Validator::check($val['ReplyTo'], 'email')) {
							self::$config['Servers'][$key]['ReplyTo'] = $val['ReplyTo'];
						} else {
							facula::core('debug')->exception('ERROR_SMTP_ADDRESS_REPLYTO_INVALID|' . $val['ReplyTo'], 'smtp', true);
						}
					} else {
						self::$config['Servers'][$key]['ReplyTo'] = self::$config['Servers'][$key]['From'];
					}
					
					// Set RETURN TO
					if (isset($val['ReturnTo'])) {
						if (Validator::check($val['ReturnTo'], 'email')) {
							self::$config['Servers'][$key]['ReturnTo'] = $val['ReturnTo'];
						} else {
							facula::core('debug')->exception('ERROR_SMTP_ADDRESS_RETURNTO_INVALID|' . $val['ReturnTo'], 'smtp', true);
						}
					} else {
						self::$config['Servers'][$key]['ReturnTo'] = self::$config['Servers'][$key]['From'];
					}
					
					// Set ERROR TO
					if (isset($val['ErrorTo'])) {
						if (Validator::check($val['ErrorTo'], 'email')) {
							self::$config['Servers'][$key]['ErrorTo'] = $val['ErrorTo'];
						} else {
							facula::core('debug')->exception('ERROR_SMTP_ADDRESS_ERRORTO_INVALID|' . $val['ErrorTo'], 'smtp', true);
						}
					} else {
						self::$config['Servers'][$key]['ErrorTo'] = self::$config['Servers'][$key]['From'];
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
		}
		
		return false;
	}
	
	static public function newMail($title, $message, array $receivers) {
		self::$emails[] = array(
			'Receivers' => $receivers,
			'Title' => $title,
			'Message' => $message,
		);
		
		return true;
	}
	
	static public function sendMail() {
		$operater = null;
		$operaterClassName = $error = '';
		$result = false;
		$remainingMails = count(self::$emails);
		$retryLimit = 3;
		$currentServers = array();
		
		if (!empty(self::$config) && $remainingMails > 0) {
			$currentServers = self::$config['Servers'];
			
			facula::core('debug')->criticalSection(true);
			
			try {
				while(!empty($currentServers) && !empty(self::$emails) && $retryLimit > 0) {
					foreach($currentServers AS $serverkey => $server) {
						$operaterClassName = __CLASS__ . '_' . $server['Type'];
						
						if (class_exists($operaterClassName, true)) {
							$operater = new $operaterClassName($server);
							
							if (is_subclass_of($operater, 'SMTPBase')) {
								if ($operater->connect($error)) {
									
									foreach(self::$emails AS $mailkey => $email) {
										if ($operater->send($email)) {
											unset(self::$emails[$mailkey]);
										} else {
											$retryLimit--;
										}
									}
									
									$operater->disconnect();
								} else {
									$error .= ' on server: ' . $server['Host'];
									unset($currentServers[$serverkey]);
								}
							} else {
								facula::core('debug')->exception('ERROR_SMTP_OPERATOR_BASE_INVALID' . $operaterClassName, 'smtp', true);
							}
						} else {
							facula::core('debug')->exception('ERROR_SMTP_OPERATOR_NOTFOUND|' . $server['Type'], 'smtp', true);
						}
					}
				}
			} catch (Exception $e) {
				$error = $e->getMessage();
			}
			
			if ($error) {
				facula::core('debug')->exception('ERROR_SMTP_OPERATOR_ERROR|' . $error, 'smtp', false);
			}
			
			facula::core('debug')->criticalSection(false);
				
			return true;
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
	
	public function __destruct() {
		$this->close();
		
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
		if ($this->connection) {
			if (fputs($this->connection, $command . "\r\n")) {
				switch($getReturn) {
					case false:
						return true;
						
						break;
						
					case 'one':
						return $this->get(true);
						
						break;
						
					case 'last':
						return $this->getLast(true);
						
						break;
				}
			} else {
				return false;
			}
		}
	}
	
	public function get($parseResponse = false, &$hasNext = false) {
		$response = null;
		$dashPOS = $spacePOS = null;
		$hasNext = false; // Reassign this as we referred it.
		
		if ($this->connection) {
			if ($response = trim(fgets($this->connection, 512))) {
				if ((($dashPOS = strpos($response, '-')) !== false) && is_numeric(substr($response, 0, $dashPOS))) { // If response contain a '-'	and all char before the - is numberic (response code)				
					$hasNext = true;
				} elseif ((($spacePOS = strpos($response, ' ')) !== false) && is_numeric(substr($response, 0, $spacePOS))) { // Only when response contain a ' ' and all char before the ' ' is number (response code)
					$hasNext = false; // Means the response got only one line (Or this is the end of responses)
				} else {
					$hasNext = true;
				}
				
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
		$responseHasNext = false;
		
		while(($response = $this->get($parseResponse, $responseHasNext)) !== false) {
			$responseLast = $response;
			
			if (!$responseHasNext) {
				break;
			}
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
	
	public function registerResponseParser($responseCode, Closure $parser) {
		if (!isset($this->responseParsers[$responseCode])) {
			$this->responseParsers[$responseCode] = $parser;
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_SMTP_SOCKET_RESPONSE_PARSER_EXISTED', 'smtp', true);
		}
		
		return false;
	}
	
	private function parseResponse($response) {
		$responseParam = $parserName = $splitType = '';
		$responseCode = $responseCode = 0;
		$responseParams = array();
		$parser = null;
		
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

class SMTPAuther {
	private $socket = null;
	private $auths = array();
	static private $authers = array();
	
	public function __construct($socket, array &$auths) {
		$this->socket = $socket;
		$this->auths = $auths;
		
		return true;
	}
	
	public function auth($username, $password, &$error = '') {
		$auther = null;
		
		foreach($this->auths AS $method) {
			if (isset(self::$authers[$method])) {
				$auther = self::$authers[$method];
				
				if ($auther($this->socket, $username, $password, $error)) {
					return true;
				} else {
					$error = $error ? $error : 'UNKONWN_ERROR';
					
					return false;
				}
				
				break;
			}
		}
		
		$error = 'NOTSUPPORTED|' . implode(', ', $this->auths) . ' for ' . implode(', ', array_keys(self::$authers));
		
		return false;
	}
	
	static public function register($type, Closure $auther) {
		if (!isset(self::$authers[$type])) {
			self::$authers[$type] = $auther;
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_SMTP_AUTHER_EXISTED', 'smtp', true);
		}
		
		return false;
	}
}

SMTPAuther::register('plain', function($socket, $username, $password, &$error) {
	$null = "\0";
	$plainAuthStr = rtrim(base64_encode($username . $null . $username . $null . $password), '=');
	
	if ($socket->put('AUTH PLAIN', 'one') != 334) {
		$error = 'UNKOWN_RESPONSE';
		return false;
	}
	
	switch($socket->put($plainAuthStr, 'one')) { // Give the username and check return
		case 535:
			$error = 'AUTHENTICATION_FAILED'; 
			break;
			
		case 535:
			$error = 'FROM_INVALID'; 
			break;
			
		case 235:
			return true;
			break;
			
		default:
			$error = 'UNKOWN_RESPONSE';
			break;
	}
	
	return true;
});

SMTPAuther::register('login', function($socket, $username, $password, &$error) {
	$response = '';
	$b64Username = rtrim(base64_encode($username), '=');
	$b64Password = rtrim(base64_encode($password), '=');
	
	if ($socket->registerResponseParser(334, function($param) {
		$resp = strtolower(base64_decode($param)); // I have no idea why they decided to base64 this
		
		switch($resp) {
			case 'username:':
				return 'Username';
				break;
				
			case 'password:':
				return 'Password';
				break;
				
			default:
				return $param;
				break;
		}
	})) {
		switch($response = $socket->put('AUTH LOGIN', 'one')) {
			case 'Username':
				// Response for user name
				switch($socket->put($b64Username, 'one')) { // Give the username and check return
					case 'Password': // Want password, give password
						switch($socket->put($b64Password, 'one')) {
							case 535:
								$error = 'AUTHENTICATION_FAILED'; 
								break;
								
							case 535:
								$error = 'FROM_INVALID'; 
								break;
								
							case 235:
								return true;
								break;
								
							default:
								$error = 'UNKOWN_RESPONSE';
								break;
						}
						
						break;
				}
				break;
				
			case 'Password':
				// Response for password, it's odd. First case is normal case
				switch($socket->put($b64Password, 'one')) { // Give the password and check return
					case 'Username': // Want username? give username
						switch($socket->put($b64Password, 'one')) {
							case 535:
								$error = 'AUTHENTICATION_FAILED';
								break;
								
							case 235:
								return true;
								break;
								
							default:
								$error = 'UNKOWN_RESPONSE';
								break;
						}
						
						break;
				}
				
				break;
				
			default:
				$error = 'UNKOWN_RESPONSE';
				return false;
				break;
		}
	} else {
		$error = 'RESPONSE_PARSER_REGISTER_FAILED';
	}
	
	return false;
});

class SMTPDatar {
	private $mail = array();
	private $mailContent = array();
	private $parsedMail = array();
	
	public function __construct(array $mail) {
		global $_SERVER;
		$senderHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
		
		$appInfo = facula::getVersion();
		
		$checkContent = '';
		
		$mailContent = array(
			'Title' => isset($mail['Title']) ? $mail['Title'] : null,
			'Message' => isset($mail['Message']) ? $mail['Message'] : null,
		);
		
		// Parse mail body
		$mail['Subject'] = '=?UTF-8?B?' . rtrim(base64_encode($mailContent['Title'] ? $mailContent['Title'] : 'Untitled'), '=') . '?=';
		$mail['Body'] = chunk_split(base64_encode($mailContent['Message']) . '?=', 76, "\n");
		$mail['AltBody'] = chunk_split(base64_encode(strip_tags(str_replace('</', "\r\n</", $mailContent['Message']))) . '?=', 76, "\n");
		
		// Make mail header
		$this->addLine('MIME-Version', '1.0');
		$this->addLine('X-Priority', '3');
		$this->addLine('X-MSMail-Priority', 'Normal');
		
		$this->addLine('X-Mailer', $appInfo['App'] . ' ' . $appInfo['Ver'] . ' (' . $appInfo['Base'] . ')');
		$this->addLine('X-MimeOLE', $appInfo['Base'] . ' Mailer OLE');
		
		$this->addLine('X-AntiAbuse', 'This header was added to track abuse, please include it with any abuse report');
		$this->addLine('X-AntiAbuse', 'Primary Hostname - ' .$senderHost);
		$this->addLine('X-AntiAbuse', 'Original Domain - ' . $senderHost);
		$this->addLine('X-AntiAbuse', 'Originator/Caller UID/GID - [' . $senderHost . ' ' . $mail['SenderIP'] . '] / [' . $senderHost . ' ' . $mail['SenderIP'] . ']');
		$this->addLine('X-AntiAbuse', 'Sender Address Domain - ' . $senderHost);
		
		// Mail title
		$this->addLine('Subject', $mail['Subject']);
		
		// Addresses
		$this->addLine('From', '=?UTF-8?B?' . base64_encode($mail['Screenname']) . '?= <' . $mail['From'] . '>');
		$this->addLine('To', 'undisclosed-recipients:;');
		$this->addLine('Return-Path', '<' . $mail['ReturnTo'] . '>');
		$this->addLine('Reply-To', '<' . $mail['ReplyTo'] . '>');
		$this->addLine('Errors-To', '<' . $mail['ErrorTo'] . '>');
		
		$this->addLine('Date', date('D, d M y H:i:s O', FACULA_TIME));
		
		$this->addLine('Message-ID', $this->getFactor() . '@' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'));
		
		// Ready content for boundary check. Combine all content strings to one line, then check if the boundary existed.
		$checkContent = $mail['Subject'] . $mail['Body'] . $mail['AltBody'];
		
		while(true) {
			$this->mail['Boundary'] = '#' . $this->getFactor();
			$this->mail['BoundarySpliter'] = '--' . $this->mail['Boundary'];
			$this->mail['BoundarySpliterEnd'] = $this->mail['BoundarySpliter'] . '--';
			
			if (strpos($checkContent, $this->mail['Boundary']) === false) {
				break;
			}
		}
		
		$this->addLine('Content-Type', 'multipart/alternative; boundary="' . $this->mail['Boundary'] . '"');
		
		// Make mail body
		$this->addRaw(null); // Make space
		$this->addRaw('This MIME email produced by ' . $appInfo['Base'] . ' Mailer for ' . $senderHost . '.');
		$this->addRaw('If you have any problem reading this email, please contact ' . $mail['ReturnTo'] . ' for help.');
		$this->addRaw(null);
		
		// Primary content
		$this->addRaw($this->mail['BoundarySpliter']);
		$this->addLine('Content-Type', 'text/plain; charset=utf-8');
		$this->addLine('Content-Transfer-Encoding', 'base64');
		$this->addRaw(null);
		$this->addRaw($mail['AltBody']);
		$this->addRaw(null);
		
		$this->addRaw($this->mail['BoundarySpliter']);
		$this->addLine('Content-Type', 'text/html; charset=utf-8');
		$this->addLine('Content-Transfer-Encoding', 'base64');
		$this->addRaw(null);
		$this->addRaw($mail['Body']);
		$this->addRaw(null);
		
		$this->addRaw($this->mail['BoundarySpliterEnd']);
		
		return true;
	}
	
	public function get() {
		return implode("\n", $this->mailContent);
	}
	
	private function addLine($head, $content) {
		$this->mailContent[] = $head . ': ' . $content;
		
		return true;
	}
	
	private function addRaw($content) {
		$this->mailContent[] = $content;
	}
	
	private function getFactor() {
		return mt_rand(0, 65535) . mt_rand(0, 65535);
	}
}

abstract class SMTPBase {
	private $socket = null;
	
	abstract public function connect(&$error);
	abstract public function send(array &$email);
	abstract public function disconnect();
	
	final protected function getSocket($host, $port, $timeout) {
		if (!$this->socket) {
			$this->socket = new SMTPSocket($host, $port, $timeout);
		}
		
		return $this->socket;
	}
	
	final protected function getAuth(array &$auths) {
		if ($this->socket) {
			return new SMTPAuther($this->socket, $auths);
		}
		
		return false;
	}
	
	final protected function getData(array $mail) {
		return new SMTPDatar($mail);
	}
}

?>