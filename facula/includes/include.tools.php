<?php

// I'm so like to package stuff. Name space cannot stop me pack thing in to class Humm hahahaa..

abstract class tools {
	static function convertIniUnit($str) {
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