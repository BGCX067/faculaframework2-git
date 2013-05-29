<?php

namespace controllers;

class IndexController extends \Controller {
	private $settings = array();
	
	// Read setting and essentials
	public function __construct() {
		
	}
	
	// do some wakeup init
	public function _inited() {
		//echo 'inited';
	}
	
	public function get(&$request) {
		
		$message = 'hello word';
		
		$this->assign('message', $message);
		
		$this->display('index');
		
		
		/*
		$q = \query::from('settings')->select(array('setting', 'value'))->get();
		
		print_r('1:');
		print_r($q);
		
		$datatoinsert = array(
			array('setting' => ['keyname', 'STR'], 'value' => ['this\'s a valu :1 e', 'STR']),
		);
		
		$q2 = \query::from('settings')->insert($datatoinsert)->save();
		
		print_r('2:');
		print_r($q2);
		
		
		$datatoupdate = array(
			'value' => ['for keyname2', 'STR'],
		);
		
		$q3 = \query::from('settings')->update($datatoupdate)->where('setting', '=', ['keyname', 'STR'])->save();
		
		print_r('3:');
		print_r($q3);
		
		$q4 = \query::from('settings')->delete()->where('setting', '=', ['keyname', 'STR'])->save();
		
		print_r('4:');
		print_r($q4);
		
		
		
		$q = new query();
		
		$q->select(array('field1', 'field2', 'field3', 'field4', 'field5'), 'tables');
		
		*/
		
		/*
		$pdoInfo = facula::core('pdo')->getConnection(array('Table' => 'settings', 'Operation' => 'Read'));
		$pdoInfo2 = facula::core('pdo')->getConnection(array('Table' => 'settings', 'Operation' => 'Write'));
		
		if ($result = $pdoInfo->query("SELECT * FROM `settings`")) {
			print_r($result->fetchAll(PDO::FETCH_ASSOC));
		}
		*/
		// facula::core('response')->setCookie('COOKIENEW2', 'asdasdsadsad');
		
		// echo facula::core('request')->getCookie('COOKIENEW2');
		
		// facula::core('response')->send();
		
		// facula::core('response')->setContent($message);
		
		// facula::core('response')->send();
	}
	
	public function post(&$request) {
		echo "post";
	}
}

?>