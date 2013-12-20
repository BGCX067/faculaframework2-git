<?php

/**
 * Setting Container
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

namespace Facula\App;

/**
 * Setting Container
 */
abstract class Setting
{
    /** Registered setting methods or datas */
    private static $registered = array();

    /**
     * Get who is calling
     *
     * @return mixed Return caller name when succeeded, null otherwise.
     */
    private static function getCallerClass()
    {
        $debug = array();
        $btResult = debug_backtrace(
            DEBUG_BACKTRACE_PROVIDE_OBJECT + DEBUG_BACKTRACE_IGNORE_ARGS
        );

        $debug[] = array_shift($btResult); // Shift the back trace of this method
        $debug[] = array_shift($btResult); // Shift next, which must be the call to this method

        foreach ($btResult as $bt) {
            if (isset($bt['function'])
                && strpos(strtolower($bt['function']), '{closure') !== false) {
                continue;
            }

            if (isset($bt['class'])) {
                return $bt['class'];
                break;
            }
        }

        return null;
    }

    /**
     * Register a new setting
     *
     * @param string $settingName Name of the setting
     * @param mixed $operator Setting data or operator, can be any data type. But it will be call when callable.
     * @param mixed $accesser Permitted accesser(s)
     *
     * @return bool Return true when succeeded
     */
    public static function registerSetting($settingName, $operator, $accesser = false)
    {
        $accessers = array();

        if (is_array($accesser)) {
            $accessers = $accesser;
        } elseif (is_string($accesser)) {
            $accessers = $accesser ? array($accesser) : array();
        } elseif (is_bool($accesser)) {
            if ($accesser) {
                $accessers = array('!PUBLIC!');
            } elseif ((($accesser = get_called_class()) != __CLASS__)
                    || ($accesser = self::getCallerClass())) {
                $accessers = array($accesser);
            }
        }

        if (!isset(self::$registered[$settingName])) {
            if (is_callable($operator)) {
                self::$registered[$settingName] = array(
                    'Operator' => $operator,
                    'Type' => 'Operator',
                    'Accesser' => array_flip($accessers)
                );
            } else {
                self::$registered[$settingName] = array(
                    'Result' => $operator,
                    'Type' => 'Data',
                    'Accesser' => array_flip($accessers)
                );
            }

            return true;
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_SETTING_NAME_ALREADY_EXISTED|' . $settingName,
                'setting',
                true
            );
        }

        return false;
    }

    /**
     * Get a setting
     *
     * @param string $settingName Name of the setting
     * @param mixed $operator Setting data or operator, can be any data type. But it will be call when callable.
     * @param mixed $accesser Permitted accesser(s)
     *
     * @return bool Return true when succeeded
     */
    public static function getSetting($settingName)
    {
        $accesser = '';

        if (isset(self::$registered[$settingName])) {
            if (isset(self::$registered[$settingName]['Accesser']['!PUBLIC!'])
                || (
                        (
                            (($accesser = get_called_class()) != __CLASS__)
                            || ($accesser = self::getCallerClass())
                        )
                        && isset(self::$registered[$settingName]['Accesser'][$accesser])
                    )
                ) {

                switch (self::$registered[$settingName]['Type']) {
                    case 'Operator':
                        if (!isset(self::$registered[$settingName]['Result'])) {
                            $operator = self::$registered[$settingName]['Operator'];

                            self::$registered[$settingName]['Result'] = $operator();
                        }

                        return self::$registered[$settingName]['Result'];
                        break;

                    case 'Data':
                        return self::$registered[$settingName]['Result'];
                        break;

                    default:
                        break;
                }

            } else {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_SETTING_ACCESS_DENIED|' . $accesser . ' -> ' . $settingName,
                    'setting',
                    true
                );
            }
        }

        return null;
    }
}
