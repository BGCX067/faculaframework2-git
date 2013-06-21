<?php

/*****************************************************************************
	Facula Framework View Base Unit
	
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

interface viewInterface {
	static public function assign($key, $val);
	static public function display($file);
}

abstract class View implements viewInterface {
	static private $assigned = array();
	
	static public function assign($key, $val) {
		return self::$assigned[$key] = $val;
	}
	
	static public function display($file) {
		$file = facula::core('object')->getFileByNamespace($file);
		$content = '';
		
		if ($content = self::render($file)) {
			facula::core('response')->setContent($content);
			facula::core('response')->send();
		}
		
		return false;
	}
	
	static private function render($targetTpl) {
		if (is_readable($targetTpl)) {
			$oldContent = ob_get_clean();
			
			ob_start();
			
			if (isset($oldContent[0])) {
				echo($oldContent);
			}
			
			extract(self::$assigned);
			
			facula::core('debug')->criticalSection(true);
			
			require($targetTpl);
			
			facula::core('debug')->criticalSection(false);
			
			return ob_get_clean();
		} else {
			facula::core('debug')->exception('ERROR_VIEW_TEMPLATE_FILENOTFOUND|' . $file, 'data', true);
		}
		
		return false;
	}
}

?>