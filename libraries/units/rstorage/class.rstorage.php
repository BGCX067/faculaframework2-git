<?php 

/*****************************************************************************
	Facula Framework Remote Storage Factory
	
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

Here's how to use

$setting = array {
	'SelectMethod' => 'Normal|Random',
	'Servers' => array(
		array(
			'Type' => 'ftp',
			'Option' => array(
				'Username' => 'test',
				'Password' => 'test',
				'Host' => '192.168.1.16',
				'Port' => 21,
				'Timeout' => 3,
				'SSL' => true,
				'Path' => '/ftp/test/',
				'Access' => 'http://files.localhost.local/',
			),
		),
		array(
			'Type' => 'skydrive',
			'Option' => array(
				'Username' => 'test',
				'Password' => 'test',
				'Host' => '192.168.1.16',
				'Port' => 21,
				'Timeout' => 3,
				'SSL' => true,
				'Path' => '/ftp/test/',
				'Access' => 'http://files.localhost.local/',
			),
		)
	)
}

rStorage::setup($setting);

rStorage::upload($file);

*/

interface remoteStorageInterface {
	public function __construct($setting);
	public function upload($localFile, &$error = '');
}

class rStorage {
	static private $handler = null;

	static private $servers = array();

	static public function setup($setting) {
		if (isset($setting['Servers'])) {
			if (isset($setting['SelectMethod'])) {
				switch ($setting['SelectMethod']) {
					case 'Random':
						shuffle($setting['Servers']);
						break;
					
					default:
						break;
				}
			}

			self::$servers = $setting['Servers'];

			return true;
		}

		return false;
	}

	static public function upload($localFile, &$error = '') {
		$handler = null;
		$handlerName = '';
		$result = '';

		if (is_readable($localFile)) {
			// Create handler instance
			if (!self::$handler) {
				foreach (self::$servers as $server) {
					if (isset($server['Type'][0])) {
						$handlerName = __CLASS__ . '_' . $server['Type'];

						if (class_exists($handlerName)) {
							$handler = new $handlerName(isset($server['Option']) ? $server['Option'] : array());

							if ($handler instanceof remoteStorageInterface) {
								if ($result = $handler->upload($localFile, $error)) {
									self::$handler = $handler;

									return $result;
								} else {
									unset($handler);
								}
							} else {
								facula::core('debug')->exception('ERROR_REMOTESTORAGE_INTERFACE_INVALID', 'remote storage', true);
							}
						} else {
							facula::core('debug')->exception('ERROR_REMOTESTORAGE_SERVER_TYPE_UNSUPPORTED', 'remote storage', true);
						}
					} else {
						facula::core('debug')->exception('ERROR_REMOTESTORAGE_SERVER_NOTYPE', 'remote storage', true);
					}
				}
			} else {
				return self::$handler->upload($localFile, $error);
			}
		} else {
			facula::core('debug')->exception('ERROR_REMOTESTORAGE_FILE_UNREADABLE|' . $localFile, 'remote storage', true);
		}

		return false;
	}
}

?>