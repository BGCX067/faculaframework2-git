<?php 

$cfg = array(
	'AppName' => 'Facula Demo', 
	'AppVersion' => '1.0', 
	'Common' => array(
		'CookiePrefix' => '_demo',
	),
	'core' => array(
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
	)
);

?>