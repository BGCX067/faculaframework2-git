<?php  

/*****************************************************************************
	Facula Framework Configure Container for NON-core Components
	
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

// Yeah, this is anit pattern called: Global variable styled object
abstract class Setting {
	static private $registered = array();
	
	static public function registerSetting($settingName, $operator, $accesser = false) {
		$accessers = array();
		
		switch(gettype($accesser)) {
			case 'array':
				// If $accessers is an array, means we need to specify who can access this setting manually.
				$accessers = $accesser;
				break;
				
			case 'string':
				// If $accessers is a string, You means can set one accesser manually .
				$accessers = $accesser ? array($accesser) : array();
				break;
				
			default:
				// If $accessers is a bool or other type, when it set to true, means setting can be access from public, other wise, only caller class can access
				$accessers = $accesser ? array('!PUBLIC!') : array(get_called_class());
				break;
		}
		
		if (!isset(self::$registered[$settingName])) {
			if (is_callable($operator)) {
				self::$registered[$settingName] = array(
					'Operator' => $operator,
					'Type' => 'Operator',
					'Accesser' => array_flip($accessers)
				);
			} else {
				self::$registered[$settingName] = array(
					'Result' => $operator,
					'Type' => 'Data',
					'Accesser' => array_flip($accessers)
				);
			}
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_SETTING_NAME_ALREADY_EXISTED|' . $settingName, 'setting', true);
		}
		
		return false;
	}
	
	static private function getCallerClass() {
		$bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT + DEBUG_BACKTRACE_IGNORE_ARGS, 3);
		
		if (isset($bt[2]['class'])) {
			return $bt[2]['class'];
		}
	}
	
	static public function getSetting($settingName) {
		$accesser = '';
		
		if (isset(self::$registered[$settingName])) {
			if (isset(self::$registered[$settingName]['Accesser']['!PUBLIC!']) || ((($accesser = get_called_class() != __CLASS__) || ($accesser = self::getCallerClass())) && isset(self::$registered[$settingName]['Accesser'][$accesser]))) {
				
				switch(self::$registered[$settingName]['Type']) {
					case 'Operator':
						if (!isset(self::$registered[$settingName]['Result'])) {
							$operator = self::$registered[$settingName]['Operator'];
							
							self::$registered[$settingName]['Result'] = $operator();
						}
						
						return self::$registered[$settingName]['Result'];
						break;
						
					case 'Data':
						return self::$registered[$settingName]['Result'];
						break;
						
					default:
						break;
				}
				
			} else {
				facula::core('debug')->exception('ERROR_SETTING_ACCESS_DENIED|' . $settingName . ' -> ' . $accesser, 'setting', true);
			}
		}

		
		return null;
	}
}

?>