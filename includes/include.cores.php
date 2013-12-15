<?php

/*****************************************************************************
	Facula Framework Core Interfaces
	
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

interface coreInterface {
	static public function getInstance(&$cfg, $commonCfg, facula $parent);
	static public function checkInstance($instance);
}

// Yes, now i just need extends below core factory and everything will be just fine. 
// And you can still replace faculaCore with your own core
// I even starting wondering why i have so many cores in different 'core' that only for extends this class for running like a factory
abstract class faculaCoreFactory implements coreInterface { 
	static private $instances = array();
	
	final static public function getInstance(&$cfg, $commonCfg, facula $parent) {
		$caller = get_called_class();
		// If $cfg['Core'] has beed set, means user wants to use their own core instead of default one
		$class = $caller . (isset($cfg['Core'][0]) ? $cfg['Core'] : 'Default');
		
		if (!isset(self::$instances[$class])) {
			if (!class_exists($class)) {
				throw new Exception('Facula core ' . $class . ' is not loadable. Please make sure object file has been included before preform this task.');
			}
			
			// Create and check new instance
			if ($caller::checkInstance(self::$instances[$class] = new $class($cfg, $commonCfg, $parent))) {
				return self::$instances[$class];
			} else {
				throw new Exception('An error happened when facula creating core ' . $class . '.');
			}
		}
		
		return self::$instances[$class];
	}
}
