<?php

/**
 * PHP Ini Helper
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

namespace Facula\Base\Tool\PHP;

use Facula\Base\Exception\Tool\PHP\Ini as Exception;

/**
 * Function lib for processing PHP INI file
 */
abstract class Ini
{
    /** Temp container of the data in PHP INI file */
    protected static $phpIniData = array();

    /**
     * Restore ini setting.
     *
     * @return bool Always return true
     */
    public static function restore()
    {
        static::initPHPIniData();

        foreach (static::$phpIniData as $key => $val) {
            static::set($key, $val['global_value']);
        }

        return true;
    }

    /**
     * Set a new value to INI for this session.
     *
     * @param string $key The setting key name
     * @param string $val The value that will be set.
     *
     * @return mixed Return true when succeed, false otherwise.
     */
    public static function set($key, $val)
    {
        static::initPHPIniData();

        if (!isset(static::$phpIniData[$key])) {
            throw new Exception\SettingKeyNotFound(
                $key
            );

            return false;
        }

        if (ini_set($key, $val) === false) {
            throw new Exception\SetFailed(
                $key,
                $val,
                static::$phpIniData[$key]['access']
            );

            return false;
        }

        // Make sure it has succeed
        static::$phpIniData[$key]['local_value'] = $val;

        return true;
    }

    /**
     * Load all PHP ini Data
     *
     * @param bool Always true as it can't be fail.
     */
    protected static function initPHPIniData()
    {
        if (!empty(static::$phpIniData)) {
            return true;
        }

        static::$phpIniData = ini_get_all();

        return true;
    }

    /**
     * Load specified ini data with key
     *
     * @param string $key The setting value
     *
     * @return string Return the setting value
     */
    protected static function getIniData($key)
    {
        static::initPHPIniData();

        // Why the static::set has been dismissed?
        // Because you can't set a random setting using ini_set anyway.
        // No way to make it work like this.
        if (!isset(static::$phpIniData[$key])) {
            throw new Exception\SettingKeyNotFound(
                $key
            );

            return false;
        }

        return static::$phpIniData[$key]['local_value'];
    }

    /**
     * Get string data from PHP ini
     *
     * @param string $key The setting key name
     *
     * @return mixed Return a string of the setting
     */
    public static function getStr($key)
    {
        // Notice that, the convert may failed on array type.
        // But never mind, 'cause you will handle that.
        // AND, who will build a array in the INI file? That will be insane LOL.
        // Tell me how to do it BTW.
        return (string)static::getIniData($key);
    }

    /**
     * Get integer data from PHP ini
     *
     * @param string $key The setting key name
     *
     * @return mixed Return a integer of the setting
     */
    public static function getInt($key)
    {
        return (integer)static::getIniData($key);
    }

    /**
     * Get float data from PHP ini
     *
     * @param string $key The setting key name
     *
     * @return mixed Return a integer of the setting
     */
    public static function getFloat($key)
    {
        return (float)static::getIniData($key);
    }

    /**
     * Get Bytes from Integer
     *
     * @param string $key The setting key name
     * @param mixed $default Default value in string or integer.
     *
     * @return float Return a float in bytes
     */
    public static function getBytes($key)
    {
        return (float)static::bytesStrToBytes(
            static::getIniData($key)
        );
    }

    /**
     * Get bool data from PHP ini
     *
     * @param string $key The setting key name
     * @param bool $default Default value in boolean
     *
     * @return bool Return the boolean value
     */
    public static function getBool($key)
    {
        $setting = strtolower(
            static::getStr($key)
        );

        switch ($setting) {
            case 'yes':
            case 'on':
            case 'true':
                return true;
                break;

            case 'no':
            case 'off':
            case 'false':
                return false;
                break;

            default:
                // For 0, null, false, ''
                if (!$setting) {
                    return false;
                }

                if (!is_numeric($setting)) {
                    return false;
                }

                if ((int)$setting > 0) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * Convert ini data size in to bytes that PHP uses
     *
     * @param string $str The setting value
     *
     * @return float The converted bytes in float.
     */
    protected static function bytesStrToBytes($str)
    {
        $strLen = 0;
        $lastChar = '';

        if (is_numeric($str)) {
            return (float)$str;
        } else {
            $strLen = strlen($str);

            if ($lastChar = $str[$strLen - 1]) {
                $strSelected = substr($str, 0, $strLen - 1);

                if (!is_numeric($strSelected)) {
                    throw new Exception\InvalidBytesString($strSelected);

                    return false;
                }

                switch (strtolower($lastChar)) {
                    case 'k':
                        return (float)($strSelected) * 1024;
                        break;

                    case 'm':
                        return (float)($strSelected) * 1048576;
                        break;

                    case 'g':
                        return (float)($strSelected) * 1073741824;
                        break;

                    default:
                        throw new Exception\InvalidBytesUnit($str, $lastChar);
                        break;
                }
            }
        }

        return 0.0;
    }
}
