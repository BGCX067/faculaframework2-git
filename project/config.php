<?php 


$cfg = array(
	'AppName' => 'Facula Demo', 
	'AppVersion' => '1.0', 
	'core' => array(
		'Paths' => array(
			'PackageRoot' => '..' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'packages', 
			'AlternativeRoot' => '..' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'alternatives', 
			'Libraries' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'libraries', 
			'HandlerRoot' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'handlers'
		),
		'SystemCacheRoot' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'datas' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'sys', 
	),
	'object' => array(
		'ObjectCacheRoot' => PROJECT_ROOT . DIRECTORY_SEPARATOR . 'datas' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'objects', 
	)
);



?>