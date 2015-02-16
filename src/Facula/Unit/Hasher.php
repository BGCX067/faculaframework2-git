<?php

/**
 * String Hasher
 *
 * Facula Framework 2015 (C) Rain Lee
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
 * @copyright  2015 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Unit;

/**
 * String Hasher
 */
class Hasher
{
    /** Salt that will be use to obscure the string */
    protected $salt = '';

    /** Length of salt string */
    protected $saltLen = 0;

    /** Times of hashing */
    private $loopPeriod = 1;

    /**
     * Constructor
     *
     * @param string $salt Salt used for hashing
     * @param integer $loop Repeat times for string hashing
     *
     * @return void
     */
    public function __construct($salt = '', $loop = 1)
    {
        if ($salt) {
            $this->salt = $salt;
            $this->saltLen = strlen($salt);
        }

        $this->loopPeriod = $loop > 0 ? $loop : 1;
    }

    /**
     * Obscure the hashed value
     *
     * @param string $str Hashed value
     *
     * @return string Obscured string
     */
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

    /**
     * Output obscured MD5
     *
     * @param string $str String that will be hashed
     *
     * @return string Obscured string
     */
    public function obscuredMD5($str)
    {
        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash(
                'md5',
                $this->obscure(hash('md5', $str))
            );
        }

        return $str;
    }

    /**
     * Output obscured SHA1
     *
     * @param string $str String that will be hashed
     *
     * @return string Obscured string
     */
    public function obscuredSHA1($str)
    {
        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash(
                'sha1',
                $this->obscure(hash('sha1', $str))
            );
        }

        return $str;
    }

    /**
     * Output obscured SHA256
     *
     * @param string $str String that will be hashed
     *
     * @return string Obscured string
     */
    public function obscuredSHA256($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash(
                'sha256',
                $this->obscure(hash('sha256', $str . $this->obscure(hash('sha256', $str))))
            );
        }

        return $str;
    }

    /**
     * Output obscured SHA512
     *
     * @param string $str String that will be hashed
     *
     * @return string Obscured string
     */
    public function obscuredSHA512($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash(
                'sha512',
                $this->obscure(hash('sha512', $str . $this->obscure(hash('sha512', $str))))
            );
        }

        return $str;
    }

    /**
     * Output obscured RIPEMD160
     *
     * @param string $str String that will be hashed
     *
     * @return string Obscured string
     */
    public function obscuredRIPEMD160($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash(
                'ripemd160',
                $this->obscure(
                    hash('ripemd160', $str . $this->obscure(hash('ripemd160', $str)))
                )
            );
        }

        return $str;
    }

    /**
     * Output obscured RIPEMD320
     *
     * @param string $str String that will be hashed
     *
     * @return string Obscured string
     */
    public function obscuredRIPEMD320($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash(
                'ripemd320',
                $this->obscure(
                    hash('ripemd320', $str . $this->obscure(hash('ripemd320', $str)))
                )
            );
        }

        return $str;
    }

    /**
     * Output obscured WHIRLPOOL
     *
     * @param string $str String that will be hashed
     *
     * @return string Obscured string
     */
    public function obscuredWHIRLPOOL($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash(
                'whirlpool',
                $this->obscure(
                    hash('whirlpool', $str . $this->obscure(hash('whirlpool', $str)))
                )
            );
        }

        return $str;
    }

    /**
     * Output obscured SALSA10
     *
     * @param string $str String that will be hashed
     *
     * @return string Obscured string
     */
    public function obscuredSALSA10($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash(
                'salsa10',
                $this->obscure(
                    hash('salsa10', $str . $this->obscure(hash('salsa10', $str)))
                )
            );
        }

        return $str;
    }

    /**
     * Output obscured SALSA20
     *
     * @param string $str String that will be hashed
     *
     * @return string Obscured string
     */
    public function obscuredSALSA20($str)
    {
        $str = $this->salt . $str;

        for ($i = 0; $i < $this->loopPeriod; $i++) {
            $str = hash(
                'salsa20',
                $this->obscure(
                    hash('salsa20', $str . $this->obscure(hash('salsa20', $str)))
                )
            );
        }

        return $str;
    }

    /**
     * Short cut for self::obscuredMD5
     *
     * @param string $str String that will be hashed
     *
     * @return string Obscured string
     */
    public function obscuredVerify($str)
    {
        return $this->obscuredMD5($str);
    }

    /**
     * Short cut for self::obscuredSHA512
     *
     * @param string $str String that will be hashed
     *
     * @return string Obscured string
     */
    public function obscuredSafe($str)
    {
        return $this->obscuredSHA512($str);
    }
}
