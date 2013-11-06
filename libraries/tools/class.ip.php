<?php

/*****************************************************************************
	Facula Framework IP Address Tools
	
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

abstract class IP {
	static private function splitIP($ip) {
		return explode(':', str_replace('.', ':', $ip), 8); // Max is 8 for a IP addr
	}
	
	static public function joinIP($ip, $mask = false) {
		$input = array();
		$ips = '';
		
		if (!is_array($ip)) return '0.0.0.0';
		
		foreach($ip AS $k => $v) {
			if($ip[$k]) {
				$input[$k] = $v; 
			} else {
				$input[$k] = '0'; 
			}
		}
		
		$iplen = count($input);
		
		if ($mask) {
			if ($iplen > 2) {
				$input[$iplen - 2] = $input[$iplen - 1] = '*';
			} elseif ($iplen > 1) {
				$input[$iplen - 1] = '*';
			}
		}
		
		switch($iplen) {
			case 4:
				return implode('.', array($input[0], $input[1], $input[2], $input[3]));
				break;
				
			default:
				return implode(':', $input);
				break;
		}
	}
}

?>