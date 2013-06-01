<?php 
if (!defined('IN_FACULA')) {
	exit('Access Denied');
}

$cfg = array(
	'AppName' => 'Facula Demo', 
	'AppVersion' => '1.0', 
	'Common' => array(
		'CookiePrefix' => '_demo_',
		// 'SiteRootURL' => '',
	),
	'core' => array(
		'Enables' => array(
						'pdo',
						'template',
						),
		'Paths' => array(
			'UserClass' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'classes',
			'UserCores' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'cores',
			'UserIncludes' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'includes',
		),
		'SystemCacheRoot' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'datas' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'sys', 
	),
	'debug' => array(
		// 'Core' => 'Debugger', // Set up your own new custom core
		'Debug' => true, 
		'LogRoot' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'datas' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'logs', 
	),
	'object' => array(
		'LibrariesRoot' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'components', 
		'ObjectCacheRoot' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'datas' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'objects', 
	),	
	'response' => array(
		'UseGZIP' => true,
		'CookieExpireDefault' => 3600,
	),
	
	/* Belowing configure for alternative cores */
	'pdo' => array(
		'DefaultTimeout' => 3,
		'SelectMethod' => 'Table+Operation', 
		/* SelectMethod: 
			Normal: All database in database group contains all tables and capable for all operations
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
				'Connection' => 'host=127.0.0.1;port=3306;dbname=facula2demo1',
				'Prefix' => 'demo_',
				'Tables' => array('settings'),
				'Username' => 'facula2demo1',
				'Password' => 'facula2demo1',
				'Operates' => array('Write'),
				'Options' => array(
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
				),
				'Persistent' => false,
				'Timeout' => 1
			),
			array(
				'Driver' => 'mysql',
				'Connection' => 'host=127.0.0.1;port=3306;dbname=facula2demo2',
				'Host' => '127.0.0.1',
				'Port' => '3306',
				'Prefix' => 'demo_',
				'Tables' => array('settings'),
				'Username' => 'facula2demo2',
				'Password' => 'facula2demo2',
				'Operates' => array('Read'),
				'Options' => array(
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
				),
				'Persistent' => false,
				'Timeout' => 1
			),
		),
	),
	'template' => array(
		'TemplatePool' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'datas' . DIRECTORY_SEPARATOR . 'pool' . DIRECTORY_SEPARATOR . 'template',
		'CompiledTemplate' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'datas' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'template',
		'CacheTemplate' => true,
		'CompressOutput' => false,
		'ForceRenew' => false,
		'DisplayDebug' => true,
	),
);

?>