<?php 

/*****************************************************************************
	Facula Framework Stringer
	
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

abstract class Strings {
	static public function substr($string, $start, $len, $apostrophe = false) {
		if ($len > mb_strlen($string)) {
			return $string;
		} else {
			if ($apostrophe && $len > 3) {
				return mb_substr($string, $start, $len - 3) . '...';
			} else {
				return mb_substr($string, $start, $len);
			}
			
		}

		return false;
	}

	static public function strlen($string) {
		return mb_strlen($string);
	}

	static public function strpos($haystack, $needle, $offset = 0) {
		return mb_strpos($haystack, $needle, $offset);
	}
}
