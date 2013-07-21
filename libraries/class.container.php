<?php 

/*****************************************************************************
	Facula Framework IoC Container
	
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

abstract class Container {
	static private $contains = array();
	
	static public function register($name, Closure $processor) {
		if (!isset(self::$contains[$name])) {
			self::$contains[$name] = $processor;
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_CONTAINER_NAME_ALREADY_EXISTED|' . $name, 'container', true);
		}
		
		return false;
	}
	
	static public function resolve($name, $args = array()) {
		if (isset(self::$contains[$name])) {
			$selectedContain = self::$contains[$name];
			
			switch(count($args)) {
				case 0:
					return $selectedContain();
					break;
					
				case 1:
					return $selectedContain($args[0]);
					break;
					
				case 2:
					return $selectedContain($args[0], $args[1]);
					break;
					
				case 3:
					return $selectedContain($args[0], $args[1], $args[2]);
					break;
					
				case 4:
					return $selectedContain($args[0], $args[1], $args[2], $args[3]);
					break;
					
				case 5:
					return $selectedContain($args[0], $args[1], $args[2], $args[3], $args[4]);
					break;
					
				case 6:
					return $selectedContain($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
					break;
					
				case 7:
					return $selectedContain($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
					break;
					
				case 8:
					return $selectedContain($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
					break;
					
				case 9:
					return $selectedContain($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8]);
					break;
					
				case 10:
					return $selectedContain($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9]);
					break;
					
				default:
					return call_user_func_array(self::$contains[$name], $args);
					break;
					
			}
		} else {
			facula::core('debug')->exception('ERROR_CONTAINER_NAME_NOTFOUND|' . $name, 'container', true);
		}
		
		return false;
	}
}

?>