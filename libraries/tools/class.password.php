<?php 

/*****************************************************************************
	Facula Framework Password Hasher
	
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

class Password {
	protected $cost = 12;
	protected $salt = '';
	
	protected $hasher = null;
	
	public function __construct($salt = '', $loop = 200, $cost = 10) {
		$tempSalt = '';
		$this->hasher = new Hash($salt, $loop);
		
		$this->cost = $cost > 1 ? $cost : 1;
		
		if (CRYPT_BLOWFISH == 1) {
			$this->salt = '$2a$04$' . $this->getRandomSalt(64) . '$';
		} elseif (CRYPT_SHA512 == 1) {
			$this->salt = '$6$rounds=1000$' . $this->getRandomSalt(16);
		} elseif (CRYPT_SHA256 == 1) {
			$this->salt = '$5$rounds=1000$' . $this->getRandomSalt(16);
		} elseif (CRYPT_MD5 == 1) {
			$this->salt = '$1$' . $this->getRandomSalt(12);
		} elseif (CRYPT_EXT_DES == 1) {
			$this->salt = '_J9..' . $this->getRandomSalt(4);
		} elseif (CRYPT_STD_DES == 1) {
			$this->salt = $this->getRandomSalt(2);
		} else {
			$this->salt = crypt($this->getRandomSalt(16));
		}
		
		return true;
	}
	
	public function hash($string, $salt = '') {
		$passwordSalt = $salt ? $salt : $this->salt;
		$passwordHash = $this->hasher->obscuredSafe($string);
		
		for ($i = 0; $i < $this->cost; $i++) {
			$passwordHash = crypt($passwordHash, $passwordSalt);
		}
		
		return base64_encode($passwordHash . chr(0) . $passwordSalt);
	}
	
	public function verify($string, $hashed) {
		$spiltedHash = explode(chr(0), base64_decode($hashed), 2);
		
		if ($this->hash($string, isset($spiltedHash[1]) ? $spiltedHash[1] : '') === $hashed) {
			return true;
		}
		
		return false;
	}
	
	protected function getRandomSalt($num) {
		$salt = '';
		$map = './abcdefghijklnmopqrstuvwxyzABCDEFGHIJKLNMOPQRSTUVWXYZ1234567890';
		
		for ($i = (int)$num; $i > 0; $i--) {
			$salt .= $map[mt_rand(0, 63)];
		}
		
		return $salt;
	}
}
