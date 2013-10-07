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
	
	static public function registerSetting($settingName, $operator, $accessers = false) {
		switch(gettype($accessers)) {
			case 'array':
				// If $accessers is an array, means we need to specify who can access this setting manually.
				$accesser = $accessers;
				break;
				
			case 'string':
				// If $accessers is a string, You means can set one accesser manually .
				$accesser = array($accessers);
				break;
				
			default:
				// If $accessers is a bool or other type, when it set to true, means setting can be access from public, other wise, only caller class can access
				$accesser = $accessers ? array('!PUBLIC!') : array(get_called_class());
				break;
		}
		
		if (!isset(self::$registered[$settingName])) {
			if (is_callable($operator)) {
				self::$registered[$settingName]['Operator'] = $operator;
				self::$registered[$settingName]['Type'] = 'Operator';
			} else {
				self::$registered[$settingName]['Result'] = $operator;
				self::$registered[$settingName]['Type'] = 'Data';
			}
			
			self::$registered[$settingName]['Accesser'] = array_flip($accesser); // Flip the array so the val (Access tag or class name) will be the key
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_SETTING_NAME_ALREADY_EXISTED|' . $settingName, 'setting', true);
		}
		
		return false;
	}
	
	static public function getSetting($settingName) {
		$accesser = get_called_class();
		
		if (isset(self::$registered[$settingName]) && (isset(self::$registered[$settingName]['Accesser']['!PUBLIC!']) || isset(self::$registered[$settingName]['Accesser'][$accesser]))) {
			
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
			
		}
		
		return null;
	}
}

?>