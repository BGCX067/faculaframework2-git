<?php

/**
 * Facula Framework Struct Manage Unit
 *
 * Facula Framework 2013 (C) Rain Lee
 *
 * Facula Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, version 3.
 *
 * Facula Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Facula Framework. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Rain Lee <raincious@gmail.com>
 * @copyright  2013 Rain Lee
 * @package    Facula
 * @version    2.2 prototype
 * @see        https://github.com/raincious/facula FYI
 */

namespace Facula\Unit;

class Hasher
{
    protected $salt = '';
    protected $saltLen = 0;

    private $loopPeriod = 1;

    public function __construct($salt = '', $loop = 1)
    {
        if ($salt) {
            $this->salt = $salt;
            $this->saltLen = strlen($salt);
        }

        $this->loopPeriod = $loop > 0 ? $loop : 1;

        return true;
    }

    protected function obscure($str)
    {
        $strlen = strlen($str);
        $strlenHalf = (int)($strlen / 2);
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
            $str[0]                = $salt[$saltMaxIdx];
            $str[$strlenHalf]    = $salt[$saltMaxIdx % $strlenHalf];
            $str[$strlenLast]    = $salt[0];

            return $str;
        }

        return false;
    }

    public function obscuredMD5($str)
    {
        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash('md5', $this->obscure(hash('md5', $str)));
        }

        return $str;
    }

    public function obscuredSHA1($str)
    {
        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash('sha1', $this->obscure(hash('sha1', $str)));
        }

        return $str;
    }

    public function obscuredSHA256($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash('sha256', $this->obscure(hash('sha256', $str . $this->obscure(hash('sha256', $str)))));
        }

        return $str;
    }

    public function obscuredSHA512($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash('sha512', $this->obscure(hash('sha512', $str . $this->obscure(hash('sha512', $str)))));
        }

        return $str;
    }

    public function obscuredRIPEMD160($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash('ripemd160', $this->obscure(hash('ripemd160', $str . $this->obscure(hash('ripemd160', $str)))));
        }

        return $str;
    }

    public function obscuredRIPEMD320($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash('ripemd320', $this->obscure(hash('ripemd320', $str . $this->obscure(hash('ripemd320', $str)))));
        }

        return $str;
    }

    public function obscuredWHIRLPOOL($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash('whirlpool', $this->obscure(hash('whirlpool', $str . $this->obscure(hash('whirlpool', $str)))));
        }

        return $str;
    }

    public function obscuredSALSA10($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash('salsa10', $this->obscure(hash('salsa10', $str . $this->obscure(hash('salsa10', $str)))));
        }

        return $str;
    }

    public function obscuredSALSA20($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash('salsa20', $this->obscure(hash('salsa20', $str . $this->obscure(hash('salsa20', $str)))));
        }

        return $str;
    }

    public function obscuredVerify($str)
    {
        return $this->obscuredMD5($str);
    }

    public function obscuredSafe($str)
    {
        return $this->obscuredSHA512($str);
    }
}
