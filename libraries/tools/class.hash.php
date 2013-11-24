<?php 

/*****************************************************************************
	Facula Framework Hasher
	
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

class Hash {
	protected $salt = '';
	private $saltLen = 0;
	
	public function __construct($salt = '') {
		if ($salt) {
			$this->setSalt($salt);
		}
	}
	
	private function setSalt($salt) {
		if (!$this->salt) {
			$this->salt = $salt;
			$this->saltLen = strlen($salt);
			
			return true;
		}
		
		return false;
	}
	
	protected function obscure($str) {
		$strlen = strlen($str);
		$strlenHalf = intval($strlen / 2);
		$strlenLast = $strlen - 1;
		
		$saltMaxIdx = $saltlen = $factor = 0;
		$salt = '';
		
		if ($strlen > 1) {
			$factor = ord($str[0]) + ord($str[$strlenHalf]) + ord($str[$strlenLast]);
			
			if ($this->saltLen) {
				$salt = $this->salt;
				$saltlen = $this->saltLen;
			} else {
				$salt = $str;
				$saltlen = $strlen;
			}
			
			$saltMaxIdx = $saltlen - 1;
			
			for ($i = 0; $i < $strlen; $i++) {
				if (!(($factor + $i) % $strlenHalf)) {
					$str[$i] = $salt[($i % $saltMaxIdx)];
				}
			}
			
			// Hiding clue to prevent reverse the factor
			$str[0]				= $salt[$saltMaxIdx];
			$str[$strlenHalf]	= $salt[$saltMaxIdx % $strlenHalf];
			$str[$strlenLast]	= $salt[0];
			
			return $str;
		}
		
		return false;
	}
	
	public function obscuredMD5($str) {
		return hash('md5', $this->obscure(hash('md5', $str)));
	}
	
	public function obscuredSHA1($str) {
		return hash('sha1', $this->obscure(hash('sha1', $str)));
	}
	
	public function obscuredSHA256($str) {
		return hash('sha256', $this->obscure(hash('sha256', $this->salt . $str . $this->obscure(hash('sha256', $str)))));
	}
	
	public function obscuredSHA512($str) {
		return hash('sha512', $this->obscure(hash('sha512', $this->salt . $str . $this->obscure(hash('sha512', $str)))));
	}
	
	public function obscuredRIPEMD160($str) {
		return hash('ripemd160', $this->obscure(hash('ripemd160', $this->salt . $str . $this->obscure(hash('ripemd160', $str)))));
	}
	
	public function obscuredRIPEMD320($str) {
		return hash('ripemd320', $this->obscure(hash('ripemd320', $this->salt . $str . $this->obscure(hash('ripemd320', $str)))));
	}
	
	public function obscuredWHIRLPOOL($str) {
		return hash('whirlpool', $this->obscure(hash('whirlpool', $this->salt . $str . $this->obscure(hash('whirlpool', $str)))));
	}
	
	public function obscuredSALSA10($str) {
		return hash('salsa10', $this->obscure(hash('salsa10', $this->salt . $str . $this->obscure(hash('salsa10', $str)))));
	}
	
	public function obscuredSALSA20($str) {
		return hash('salsa20', $this->obscure(hash('salsa20', $this->salt . $str . $this->obscure(hash('salsa20', $str)))));
	}
	
	public function obscuredVerify($str) {
		return $this->obscuredMD5($str);
	}
	
	public function obscuredSafe($str) {
		return $this->obscuredSHA512($str);
	}
}

?>