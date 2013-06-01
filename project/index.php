<?php

function getMicrotime() {
	return microtime(true);
}

$timer['Start'] = getMicrotime();

////////////////////////////////////////////////////////

if(!defined('IN_FACULA')) {
	define('IN_FACULA', true);
}

date_default_timezone_set('UTC');

require('../facula/facula.php');
require('config.php');

$newobj = facula::init($cfg);

$newobj2 = facula::run('\controllers\IndexController', true);

// facula::clearCoreCache();




endCount();


////////////////////////////////////////////////////////

function endCount() {
	global $timer;
	
	$errors = array();
	$timer['Finished'] = getMicrotime();
	$timer['Total'] = $timer['Finished'] - $timer['Start'];
	
	$format = sprintf('Total Run time: %s (%s ms), mem: %s kb (peak: %s kb)', $timer['Total'], $timer['Total'] * 1000, memory_get_usage(true) / 1024, memory_get_peak_usage(true) / 1024);
	
	print_r('<br />' . $format);
	file_put_contents('timer.log', $format);
}



?>