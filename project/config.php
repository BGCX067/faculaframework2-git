<?php 

$cfg = array(
	'AppName' => 'Facula Demo', 
	'AppVersion' => '1.0', 
	'Common' => array(
		'CookiePrefix' => '_demo_',
	),
	'core' => array(
		'Enables' => array(
						'pdo',
						),
		'Paths' => array(
			'PackageRoot' => '..' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'packages', 
			'AlternativeRoot' => '..' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'alternatives', 
			'Libraries' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'libraries', 
			'HandlerRoot' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'handlers'
		),
		'SystemCacheRoot' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'datas' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'sys', 
	),
	'debug' => array(
		// 'Core' => 'Debugger', // Set up your own new custom core
		'Debug' => true, 
		'LogRoot' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'datas' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'logs', 
	),
	'object' => array(
		'LibRoot' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'libraries', 
		'ObjectCacheRoot' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'datas' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'objects', 
	),	
	'response' => array(
		'UseGZIP' => true,
		'CookieExpireDefault' => 3600,
	),
	'pdo' => array(
		'DefaultTimeout' => 3,
		'SelectMethod' => 'Table+Operation', 
		/* SelectMethod: 
			Normal: All database is connectable
			Table: Connect to one of database which contains specified table
			Operation: Connect to one of database which allow specified operation
			Table+Operation: Connect to one of database which not just allow specified operation but also contains specified table
		*/
		'PriorMethod' => 'Balance', 
		/* PriorMethod: 
			Balance: App will randomly connect any server in databases array for traffic dividing. 
			Redundance: App will connect servers one by one from the top of the array until one server is connected.
		*/
		'DatabaseGroup' => array(
			array(
				'Driver' => 'mysql',
				'Connection' => 'host',
				'Host' => '127.0.0.1',
				'Prefix' => 'demo_',
				'Database' => 'facula2demo1',
				'Tables' => array('settings'),
				'Username' => 'facula2demo1',
				'Password' => 'facula2demo1',
				'Operates' => array('Write'),
				'Timeout' => 1
			),
			array(
				'Driver' => 'mysql',
				'Connection' => 'host',
				'Host' => '127.0.0.1',
				'Prefix' => 'demo_',
				'Database' => 'facula2demo2',
				'Tables' => array('settings'),
				'Username' => 'facula2demo2',
				'Password' => 'facula2demo2',
				'Operates' => array('Read'),
				'Timeout' => 1
			),
		),
	),
);

?>