<?php

/*****************************************************************************
	Facula Framework INI File Tools
	
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

abstract class PHPIni {
	static public function convertIniUnit($str) {
		$strLen = 0;
		$lastChar = '';
		
		if (is_numeric($lastChar)) {
			return (int)$str;
		} else {
			$strLen = strlen($str);
			
			if ($lastChar = $str[$strLen - 1]) {
				$strSelected = substr($str, 0, $strLen - 1);
				
				switch(strtolower($lastChar)) {
					case 'k':
						return (int)$strSelected * 1024;
						break;
						
					case 'm':
						return (int)$strSelected * 1048576;
						break;
						
					case 'g':
						return (int)$strSelected * 1073741824;
						break;
				}
			}
		}
		
		return 0;
	}
}

?>