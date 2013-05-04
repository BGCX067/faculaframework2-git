<?php 

abstract class Controller {
	protected function core($name) {
		return facula::core($name);
	}
	
	protected function getRequest($key) {
		return facula::core('request')->request($key);
	}
}














?>