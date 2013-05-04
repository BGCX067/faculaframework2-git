<?php

error_reporting(E_ALL | E_STRICT);

function getMicrotime() {
	return microtime(true);
}

$timer['Start'] = getMicrotime();

////////////////////////////////////////////////////////

if(!defined('IN_FACULA')) {
	define('IN_FACULA', true);
}

include('../facula/facula.php');
include('config.php');

$newobj = facula::init($cfg);

$newobj2 = facula::run('Index');

endCount();



////////////////////////////////////////////////////////

function endCount() {
	global $timer;
	
	$errors = array();
	$timer['Finished'] = getMicrotime();
	$timer['Total'] = $timer['Finished'] - $timer['Start'];
	
	print_r(sprintf('<br />Total Run time: %s (%s ms), mem: %s kb (peak: %s kb)', $timer['Total'], $timer['Total'] * 1000, memory_get_usage(true) / 1024, memory_get_peak_usage(true) / 1024));
}



?>