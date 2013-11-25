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

abstract class SMTPBase {
	protected $connection = null;
	
	abstract public function connect(&$error);
	abstract public function send(array $email);
	abstract public function disconnect();
	
	protected function socketOpen($host, $port, $timeout, &$error, &$errorstr) {
		if (function_exists('fsockopen')) {
			if ($this->connection = fsockopen($host, $port, $error, $errorstr, $timeout)) {
				stream_set_blocking($this->connection, true);
				
				return true;
			}
		} else {
			facula::core('debug')->exception('ERROR_SMTP_SOCKET_DISABED', 'smtp', true);
		}
		
		return false;
	}
	
	protected function socketPut($command, $getReturn = false) {
		$response = '';
		
		if ($this->connection) {
			if (fputs($this->connection, $command)) {
				if (!$getReturn) {
					return true;
				} else {
					return $this->socketGetLast(true);
				}
			} else {
				facula::core('debug')->exception('ERROR_SMTP_SOCKET_NORESPONSE', 'smtp', false);
				return false;
			}
		}
	}
	
	protected function socketGet($full = false) {
		$response = null;
		
		if ($this->connection) {
			$response = fgets($this->connection, 512);
			
			if (!$full) {
				$response = substr($response, 0, strpos($response, ' '));
			}
			
			return $response ? $response : null;
		}
	}
	
	protected function socketGetLast($full = false) {
		$response = '';
		
		while($response = $this->socketGet($full)) {
			continue;
		}
		
		return $response;
	}
	
	protected function makeBody() {
	
	}
}

?>