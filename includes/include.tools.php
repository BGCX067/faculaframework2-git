<?php

/*****************************************************************************
	Facula Framework Tools

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

abstract class tools {
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
	
	static public function getUserIP($ipstr = '', $outasstring = false) {
		global $_SERVER;
		$ip = '';
		$ips = array();
		
		if (!$ipstr) {
			if(isset($_SERVER['HTTP_CLIENT_IP'][0])){
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'][0])) {
				$ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'], 16);
				$ip = trim($ips[count($ips) - 1]);
			} elseif (isset($_SERVER['REMOTE_ADDR'][0])) {
				$ip = $_SERVER['REMOTE_ADDR'];
			}
			
			return $outasstring ? $ip : self::splitIP($ip);
		} else {
			return $outasstring ? $ipstr : self::splitIP($ipstr);
		}
		
		return false;
	}
	
	static public function splitIP($ip) {
		return explode(':', str_replace('.', ':', $ip), 7); // Max is 7 for a IP addr
	}
	
	static public function joinIP($ip, $mask = false) {
		$input = array();
		$ips = '';
		
		if (!is_array($ip)) return false;
		
		foreach($ip AS $k => $v) {
			if($ip[$k]) {
				$input[$k] = $v; 
			} else {
				$input[$k] = '0'; 
			}
		}
		
		$iplen = count($input);
		
		if ($mask && $iplen > 2) {
			$input[$iplen - 2] = $input[$iplen - 1] = '*';
		}
		
		if ($input[0] != '0' && $input[3] != '0' && $input[4] == '0' && $input[5] == '0' && $input[6] == '0' && $input[7] == '0') {
			$ips = implode('.', array($input[0], $input[1], $input[2], $input[3]));
		} else {
			$ips = implode(':', $input);
		}
		
		return $ips;
	}
}

?>