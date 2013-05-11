<?php

// I'm so like to package stuff. Name space cannot stop me pack anything in to class Humm hahahaa..

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
		$ipv4 = array(); $ipv6 = array();
		
		$ipv4 = explode('.', $ip, 10);
		if (!isset($ipv4[1])) {
			$ipv6 = explode(':', $ip, 10);
			if (isset($ipv6[1]) && !isset($ipv6[8])) {
				return $ipv6;
			}
		} elseif (!isset($ipv4[4])) {
			return $ipv4;
		}
		
		return array(0, 0, 0, 0);
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