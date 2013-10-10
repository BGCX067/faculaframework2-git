<?php 

/*****************************************************************************
	Facula Framework Cacher
	
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

interface faculaCacheInterface {
	public function load($cacheName, $expire = 0);
	public function save($cacheName, $data);
}

class faculaCache extends faculaCoreFactory {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static public function checkInstance($instance) {
		if ($instance instanceof faculaCacheInterface) {
			return true;
		} else {
			throw new Exception('Facula core ' . get_class($instance) . ' needs to implements interface \'faculaCacheInterface\'');
		}
		
		return  false;
	}
}

class faculaCacheDefault implements faculaCacheInterface {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static private $setting = array(
		'CacheFileSafeCode' => array(
			'<?php if (!defined(\'IN_FACULA\')) {exit(\'Access Denied\');} ',
			' ?>',
		)
	);
	
	private $configs = array();
	
	public function __construct(&$cfg) {
		if (isset($cfg['CacheRoot'][0]) && is_dir($cfg['CacheRoot'])) {
			$this->configs['Root'] = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $cfg['CacheRoot']);
		} else {
			throw new Exception('Cache root must be set and existed.');
		}
		
		$cfg = null;
		unset($cfg);
		
		return true;
	}
	
	public function load($cacheName, $expire = 0) {
		$path = $file = '';
		$cache = array();
		
		if ($path = $this->getCacheFileByName($cacheName)) {
			$file = $this->configs['Root'] . DIRECTORY_SEPARATOR . $path['File'];
			
			if (is_readable($file)) {
				require($file);
				
				if (isset($cache['Data'])) {
					if ($expire && $cache['Time'] < FACULA_TIME - $expire) {
						unlink($file);
					}
					
					return $cache['Data'];
				}
			}
		}
		
		return false;
	}
	
	public function save($cacheName, $data) {
		$cacheData = array();
		
		if ($path = $this->getCacheFileByName($cacheName)) {
			$file = $this->configs['Root'] . DIRECTORY_SEPARATOR . $path['File'];
			
			if ($this->makeCacheDir($path['Path'])) {
				$cacheData = array(
					'Time' => FACULA_TIME,
					'Data' => $data,
				);
				
				return file_put_contents($file, self::$setting['CacheFileSafeCode'][0] . ' $cache = ' . var_export($cacheData, true) . '; ' . self::$setting['CacheFileSafeCode'][1]);
			}
		}
		
		return false;
	}
	
	private function getCacheFileByName($cacheName) {
		$pathArray = array();
		
		$result = array(
			'Path' => '',
			'File' => '',
		);
		
		$crc = abs(crc32($cacheName));
		
		while($crc > 0) {
			$pathArray[] = ($crc = intval($crc / 10240)) . '';
		}
		
		if ($pathArray[0][0]) {
			$result['Path'] = implode(DIRECTORY_SEPARATOR, array_reverse($pathArray));
			$result['File'] = $result['Path'] . DIRECTORY_SEPARATOR . 'CacheFile.'. $cacheName . '.php';
			
			return $result;
		} else {
			$result['File'] = $result['Path'] . DIRECTORY_SEPARATOR . 'CacheFile.'. $cacheName . '.php';
		
			return $result;
		}
	}
	
	private function makeCacheDir($dirName) {
		$fullPath = $this->configs['Root'] . DIRECTORY_SEPARATOR . $dirName;
		$currentPath = $this->configs['Root'] . DIRECTORY_SEPARATOR;
		
		if (!file_exists($fullPath)) {
			foreach(explode(DIRECTORY_SEPARATOR, $dirName) AS $path) {
				if (!file_exists($currentPath . $path) && mkdir($currentPath . $path, 0744)) {
					file_put_contents($currentPath . 'index.htm', 'Access Denied');
				}
				
				$currentPath .= $path . DIRECTORY_SEPARATOR;
			}
			
			return $currentPath;
		} else {
			return $fullPath;
		}
			
		return false;
	}
}

?>