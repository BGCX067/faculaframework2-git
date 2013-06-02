<?php

class examplePlugin implements pluginInterface {
	static public $plate = array(
		'Author' => 'Rain Lee',
		'Reviser' => '',
		'Updated' => '2013',
		'Contact' => 'raincious@gmail.com',
		'Version' => __FACULAVERSION__,
	);
	
	static public function register() {
		// Return as 'HookName' => 'Processor'
		
		return array(
					'template_compile_index' => 'plugin',
					'template_compile_*' => 'plugin2',
					);
	}
	
	static public function plugin() {
		return facula::core('template')->inject('IndexNavs', '<li><a href="{% $RootURL %}/link/">Plug"i"n Link</a></li>');
	}
	
	static public function plugin2() {
		return facula::core('template')->inject('IndexNavs', '<li><a href="{% $RootURL %}/link/">Plugin Link</a></li>');
	}
}

?>