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
	
	static public function registerSetting($settingName, $operator, $public = false) {
		$accesser = $public ? '//public//' : get_called_class();
		
		if (!isset(self::$registered[$settingName]) && is_callable($operator)) {
			self::$registered[$settingName]['Operator'] = $operator;
			self::$registered[$settingName]['Accesser'] = $accesser;
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_SETTING_NAME_ALREADY_EXISTED|' . $settingName, 'setting', true);
		}
		
		return false;
	}
	
	static public function getSetting($settingName) {
		$accesser = get_called_class();
		
		if (isset(self::$registered[$settingName]) && (self::$registered[$settingName]['Accesser'] == '//public//' || self::$registered[$settingName]['Accesser'] == $accesser)) {
			return self::getOperatorResult($settingName);
		} else {
			facula::core('debug')->exception('ERROR_SETTING_NAME_NOTFOUND|' . $settingName, 'setting', true);
		}
		
		return array();
	}
	
	static private function getOperatorResult($settingName) {
		$func = null;
		
		if (isset(self::$registered[$settingName]['Operator'])) {
			if (!isset(self::$registered[$settingName]['Result'])) {
				$func = self::$registered[$settingName]['Operator'];
				
				self::$registered[$settingName]['Result'] = $func();
			}
			
			return self::$registered[$settingName]['Result'];
		}
		
		return false;
	}
}

?>