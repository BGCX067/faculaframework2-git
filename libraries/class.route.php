<?php

/*****************************************************************************
	Facula Framework Router
	
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

	VALID ROUTE FORMAT:
	
	URL http://localhost/index.php?/level1.1/level1.run1/level1.run1.sub1/
	
	$routes = array(
		'/level1.1/level1.run1/level1.run1.sub1/(*)/(*)/' => array(
			'\controllers\GoodsController2',
			array()
		)
	);
	
	$routesMap = array(
		'level1.1' => array(
			'Subs' => array(
				'level1.run1' => array(
					'Operator' => '\controllers\GoodsController',
					'Args' => array(
						1,2,3
					),
					
					'Subs' => array(
						'level1.run1.sub1' => array(
							'Operator' => '\controllers\GoodsController2',
							'Args' => array(
								1,2,3
							),
							
							'Subs' => array(
								'*' => array(
									'Sub' => array(
										
									),
								),
							)
						),
					)
				),
			),
		),
	);

*/

interface routeInterface {
	static public function run($routeMap);
	static public function set($current);
}

abstract class Route implements routeInterface {
	static private $rMap = array();
	
	static public function run($routeMap) {
		
	}
	
	static public function set($current) {
	
	}
}

?>