<?php

/*****************************************************************************
	Facula Framework Session Interface
	
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

class Session {
	static private $inited = false;
	
	static private $defaults = array(
		'Setting' => array(
			'CookieKey' => '!',
			'SessionExpire' => 3600,
			'Salt' => '',
		),
	);
	
	static private $currentSessionKeys = array();
	
	static private $sessions = array();
	
	static private $cores = array();
	
	/* Method for init */
	static public function init($setting = array()) {
		if (!self::$inited) {
			self::$cores = facula::getAllCores();
			
			self::$defaults = array(
				'Setting' => array(
					'CookieKey' => isset($setting['CookieKey']) ? $setting['CookieKey'] : '!',
					'Expire' => isset($setting['Expire']) ? intval($setting['Expire']) : 3600,
					'Salt' => isset($setting['Salt']) ? $setting['Salt'] : '',
				),
			);
			
			self::$inited = true;
			
			register_shutdown_function(function() {
				self::update();
			});
			
			return true;
		}
		
		return false;
	}
	
	static public function setup($setting = array(), $type = 'General') {
		if (!isset(self::$sessions[$type]) && self::$inited) {
			self::$sessions[$type] = array(
				'Setting' => array(
					'CookieKey' => isset($setting['CookieKey']) ? $setting['CookieKey']: self::$defaults['Setting']['CookieKey'],
					'Expire' => isset($setting['Expire']) ? intval($setting['Expire']): self::$defaults['Setting']['Expire'],
					'Salt' => isset($setting['Salt']) ? $setting['Salt'] : self::$defaults['Setting']['Salt'],
				),
				'Sessions' => array(),
				'Handlers' => array(),
			);
			
			return true;
		}
		
		return false;
	}
	
	static private function update() {
		$updateHandler = $garbagerHandler = null;
		$garbageExpiredTime = FACULA_TIME - $sessions['Setting']['Expire'];
		
		foreach(self::$sessions AS $type => $sessions) {
			if (isset($sessions['Handlers']['Update'])) {
				$updateHandler = $sessions['Handlers']['Update'];
				
				foreach($sessions['Sessions'] AS $session) {
					$updateHandler($session);
				}
			}
			
			if (isset($sessions['Handlers']['Garbage'])) {
				if (!self::$cores['cache']->load('session-lock-' . $type, $sessions['Setting']['Expire'])) {
					$garbagerHandler = $sessions['Handlers']['Garbage'];
					
					$garbagerHandler($garbageExpiredTime);
					
					self::$cores['cache']->save('session-lock-' . $type, true);
				}
			}
		}
		
		return true;
	}
	
	/* Set Handlers */
	static public function setReader(Closure $handler, $for = 'General') {
		if (isset(self::$sessions[$for])) {
			if (!isset(self::$sessions[$for]['Handlers']['Read'])) {
				self::$sessions[$for]['Handlers']['Read'] = $handler;
				
				return true;
			}
		} else {
			self::$cores['debug']->exception('ERROR_SESSION_SETREADER_NOT_INITED|' . $for, 'session', true);
		}
		
		return false;
	}
	
	static public function setUpdater(Closure $handler, $for = 'General') {
		if (isset(self::$sessions[$for])) {
			if (!isset(self::$sessions[$for]['Handlers']['Update'])) {
				self::$sessions[$for]['Handlers']['Update'] = $handler;
				
				return true;
			}
		} else {
			self::$cores['debug']->exception('ERROR_SESSION_SETWRITER_NOT_INITED|' . $for, 'session', true);
		}
		
		return false;
	}
	
	static public function setGarbager(Closure $handler, $for = 'General') {
		if (isset(self::$sessions[$for])) {
			if (!isset(self::$sessions[$for]['Handlers']['Garbage'])) {
				self::$sessions[$for]['Handlers']['Garbage'] = $handler;
				
				return true;
			}
		} else {
			self::$cores['debug']->exception('ERROR_SESSION_SETGARBAGER_NOT_INITED|' . $for, 'session', true);
		}
		
		return false;
	}
	
	/* Get session in two way */
	static public function get($sessionKey, $for = 'General') {
		$handler = null;
		
		if (isset(self::$sessions[$for]['Sessions'][$sessionKey])) {
			return self::$sessions[$for]['Sessions'][$sessionKey];
		} elseif (isset(self::$sessions[$for]['Handlers']['Read'])) {
			$handler = self::$sessions[$for]['Handlers']['Read'];
			
			return (self::$sessions[$for]['Session'][$sessionKey] = $handler($sessionKey));
		}
		
		return false;
	}
	
	static public function getCurrent($for = 'General') {
		$sessionKeyInfo = array();
		
		if ($sessionKeyInfo = self::getCurrentKey($for)) {
			if (isset(self::$sessions[$for]['Sessions'][$sessionKeyInfo['Key']])) {
				return self::$sessions[$for]['Sessions'][$sessionKeyInfo['Key']];
			} elseif (isset(self::$sessions[$for]['Handlers']['Read'])) {
				$handler = self::$sessions[$for]['Handlers']['Read'];
				
				return (self::$sessions[$for]['Sessions'][$sessionKeyInfo['Key']] = $handler($sessionKeyInfo['Key'], $sessionKeyInfo['Safe']));
			}
		}
		
		return false;
	}
	
	// Following method for calc current sessionKey() 
	static private function getCurrentKey($for = 'General') {
		$sessionKey = $sessionRawKey = '';
		$sessionKeyInfo = array();
		$safeSession = false;
		$networkID = self::$cores['request']->getClientInfo('ip');
		
		if (isset(self::$sessions[$for]['Setting'])) {
			if (!isset(self::$currentSessionKeys[$for])) {
				// Check if this user already has the session key in it's cookie
				if (($sessionRawKey = self::$cores['request']->getCookie(self::$sessions[$for]['Setting']['CookieKey'])) && ($sessionKeyInfo = self::verifyKey($sessionRawKey, $networkID, $for))) {
					$sessionKey = $sessionKeyInfo['Verify'];
					$safeSession = true;
					
					if ($sessionKeyInfo['Expire'] - (self::$sessions[$for]['Setting']['Expire'] / 2) < FACULA_TIME) {
						$sessionKeyInfo['Expire'] = FACULA_TIME + self::$sessions[$for]['Setting']['Expire'];
						
						self::$cores['response']->setCookie(self::$sessions[$for]['Setting']['CookieKey'], implode("\t", $sessionKeyInfo), self::$sessions[$for]['Setting']['Expire'], self::$cores['request']->getClientInfo('https'), true);
					}
				} elseif ($sessionKeyInfo = self::generateKey($networkID, $for)) { // If not, generate one from it's ip address.
					// Set a stable key for this temp session.
					$sessionKey = hash('md5', $networkID);
					
					// And try set the cookie key for next reading
					self::$cores['response']->setCookie(self::$sessions[$for]['Setting']['CookieKey'], implode("\t", $sessionKeyInfo), self::$sessions[$for]['Setting']['Expire'], self::$cores['request']->getClientInfo('https'), true);
				}
				
				return (self::$currentSessionKeys[$for] = array(
					'Safe' => $safeSession,
					'Key' => $sessionKey,
				));
			} else {
				return self::$currentSessionKeys[$for];
			}
		}
		
		return $sessionKeyInfo;
	}
	
	static private function generateKey($networkID, $for = 'General') {
		global $_SERVER;
		$key = $rawKey = array();
		
		$hasher = new Hash(self::$sessions[$for]['Setting']['Salt'], 1);
		
		$rawKey = array(
			'ClientID' => $networkID . mt_rand(0, 65535) . mt_rand(0, 65535) . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown'),
			'NetID' => $networkID,
		);
		
		$key['Client'] = $hasher->obscuredVerify($rawKey['ClientID']);
		$key['Verify'] = $hasher->obscuredVerify($key['Client'] . $rawKey['NetID']);
		$key['Expire'] = self::$sessions[$for]['Setting']['Expire'] + FACULA_TIME;
		
		return $key;
	}
	
	static private function verifyKey($sessionRawKey, $networkID, $for = 'General') {
		$key = $rawKey = array();
	
		$hasher = new Hash(self::$sessions[$for]['Setting']['Salt'], 1);
		$inputKey = explode("\t", $sessionRawKey, 3);
		
		if (isset($inputKey[0], $inputKey[1], $inputKey[2])) {
			$key = array(
				'Client' => $inputKey[0],
				'Verify' => $inputKey[1],
				'Expire' => intval($inputKey[2]),
			);
			
			if ($key['Verify'] === $hasher->obscuredVerify($key['Client'] . $networkID)) {
				return $key;
			}
		}
		
		return $key;
	}
}

?>