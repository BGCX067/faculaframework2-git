<?php 

/*****************************************************************************
	Facula Framework Pager Offset Calculator
	
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
	but WITHOUT ANY WARRANTY; without even the implied warranty ofapp:ds:parameter
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Lesser General Public License for more details.
	
	You should have received a copy of the GNU Lesser General Public License
	along with Facula Framework. If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************/

abstract class Pager {
	static public function get($itemprepage, $current, $totalitems = 0, $maxpages = 6000) {
		$tp = $p = 0;
		$vip = $vc = $vti = 0;
		
		$vc = intval($current) - 1;
		$vip = intval($itemprepage);
		$vti = intval($totalitems);
		
		if ($vc < 0) {
			$vc = 0;
		} elseif ($vc > ($maxpages ? $maxpages : 5000)) {
			$vc = $maxpages;
		}
		
		if ($vti) {
			$tp = ceil($vti > $vip ? $vti / $vip : 1);
			$tp = $tp > $maxpages ? $maxpages : $tp;
			
			if ($vc >= $tp) {
				$vc = $tp - 1;
			}
		}
		
		$p = $vip * $vc;
		
		return array('Offset' => $p, 'Distance' => $vip, 'Current' => $vc ? $vc + 1 : 1, 'TotalPages' => $tp, 'MaxPagesDisplay' => $maxpages);
	}
}

?>