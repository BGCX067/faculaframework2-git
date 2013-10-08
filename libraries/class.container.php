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
	
	static private function getCallerClass() {
		$bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT + DEBUG_BACKTRACE_IGNORE_ARGS, 3);
		
		if (isset($bt[2]['class'])) {
			return $bt[2]['class'];
		}
		
		return null;
	}
	
	static public function register($name, Closure $processor, $accesser = false) {
		$accessers = array();
		
		switch(gettype($accesser)) {
			case 'array':
				$accessers = $accesser;
				break;
				
			case 'string':
				$accessers = $accesser ? array($accesser) : array();
				break;
				
			default:
				if ($accesser) {
					$accessers = array('!PUBLIC!');
				} elseif (($accesser = get_called_class() != __CLASS__) || ($accesser = self::getCallerClass())) {
					$accessers = array($accesser);
				}
				break;
		}
		
		if (!isset(self::$contains[$name])) {
			self::$contains[$name] = array(
				'Processor' => $processor,
				'Accessers' => array_flip($accessers)
			);
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_CONTAINER_NAME_ALREADY_EXISTED|' . $name, 'container', true);
		}
		
		return false;
	}
	
	static public function resolve($name, $args = array(), $default = null) {
		$accesser = '';
		
		if (isset(self::$contains[$name])) {
			if (isset(self::$contains[$name]['Accessers']['!PUBLIC!']) || ((($accesser = get_called_class() != __CLASS__) || ($accesser = self::getCallerClass())) && isset(self::$contains[$name]['Accessers'][$accesser]))) {
				$selectedContain = self::$contains[$name]['Processor'];
				
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
				facula::core('debug')->exception('ERROR_CONTAINER_ACCESS_DENIED|' . $accesser . ' -> ' . $name, 'container', true);
			}
			
		} elseif ($default && is_callable($default)) {
			return $default();
		}
		
		return false;
	}
}

?>