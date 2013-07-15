<?php

/*****************************************************************************
	Facula Framework Router
	
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

/*
	VALID ROUTE FORMAT:

	$routes = array(
		'/level1.1/level1.run1/level1.run1.sub1/?/?/' => array(
			'\controllers\SomeController',
			array()
		)
	);
*/

interface routeInterface {
	static public function setup($paths);
	static public function run();

	static public function setDefaultHandler(Closure $handler);
	static public function setErrorHandler(Closure $handler);
	
	static public function setPath($path);
}

abstract class Route implements routeInterface {
	static private $routeSplit = '/';
	static private $routeMap = array();
	static private $currentPath = '';
	static private $defaultHandler = null;
	static private $errorHandler = null;

	static public function setup($paths) {
		$tempLastRef = $tempLastUsedRef = null;
		
		foreach($paths AS $path => $operator) {
			$tempLastRef = &self::$routeMap;
			
			foreach(explode(self::$routeSplit, trim($path, self::$routeSplit)) AS $key => $val) {
				$val = $val ? $val : '?';
				
				$tempLastUsedRef = &$tempLastRef[$val];
				
				if (isset($tempLastRef[$val])) {
					$tempLastRef = &$tempLastRef[$val]['Subs'];
				} else {
					$tempLastRef[$val] = array('Subs' => array());
					$tempLastRef = &$tempLastRef[$val]['Subs'];
				}
			}
			
			$tempLastUsedRef['Operator'] = $operator;
		}
		
		return true;
	}
	
	static public function run() {
		$usedParams = $operatorParams = array();
		$pathParams = explode(self::$routeSplit, trim(self::$currentPath, self::$routeSplit));
		$lastPathOperator = null;
		$lastPathRef = &self::$routeMap;
		
		if (isset($pathParams[0]) && $pathParams[0]) {
			foreach ($pathParams as $param) {
				if (isset($lastPathRef[$param])) {
					$lastPathRef = &$lastPathRef[$param];
				} elseif (isset($lastPathRef['?'])) {
					$lastPathRef = &$lastPathRef['?'];
					$usedParams[] = $param;
				} else {
					self::execErrorHandler('PATH_NOT_FOUND');

					return false;
					break;
				}

				if (isset($lastPathRef['Operator'])) {
					$lastPathOperator = &$lastPathRef['Operator'];
				}

				$lastPathRef = &$lastPathRef['Subs'];
			}

			if ($lastPathOperator) {
				if (isset($lastPathOperator[0])) {
					if (isset($lastPathOperator[1])) {
						foreach ($lastPathOperator[1] as $paramIndex) {
							if (isset($usedParams[$paramIndex])) {
								$operatorParams[] = $usedParams[$paramIndex];
							} else {
								$operatorParams[] = null;
							}
						}
					}

					return facula::run($lastPathOperator[0], $operatorParams, true);
				} else {
					return self::execErrorHandler('PATH_NO_OPERATOR_SPECIFIED');
				}
			} else {
				self::execErrorHandler('PATH_NO_OPERATOR');
			}
		} else {
			self::execDefaultHandler();
		}
	
		return false;
	}

	static public function setDefaultHandler(Closure $handler) {
		if (!self::$defaultHandler) {
			self::$defaultHandler = $handler;

			return true;
		} else {
			facula::core('debug')->exception('ERROR_ROUTER_DEFAULT_HANDLER_EXISTED', 'router', true);
		}

		return false;
	}

	static public function execDefaultHandler() {
		$handler = null;

		if (is_callable(self::$defaultHandler)) {
			$handler = self::$defaultHandler;

			return $handler();
		} else {
			facula::core('debug')->exception('ERROR_ROUTER_DEFAULT_HANDLER_UNCALLABLE', 'router', true);
			return false;
		}

		return false;
	}

	static public function setErrorHandler(Closure $handler) {
		if (!self::$errorHandler) {
			self::$errorHandler = $handler;
			
			return true;
		} else {
			facula::core('debug')->exception('ERROR_ROUTER_ERROR_HANDLER_EXISTED', 'router', true);
		}

		return false;
	}

	static private function execErrorHandler($type) {
		$handler = null;

		if (is_callable(self::$errorHandler)) {
			$handler = self::$errorHandler;

			return $handler($type);
		} else {
			facula::core('debug')->exception('ERROR_ROUTER_ERROR_HANDLER_UNCALLABLE', 'router', true);
			return false;
		}

		return false;
	}
	
	static public function setPath($path) {
		if (self::$currentPath = $path) {
			return true;
		}
		
		return false;
	}
}

?>