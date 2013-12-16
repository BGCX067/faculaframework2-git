<?php

/*****************************************************************************
    Facula Framework Session Interface

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

class Session
{
    private static $inited = false;

    private static $defaults = array();

    private static $currentSessionKeys = array();

    private static $sessions = array();

    private static $cores = array();

    private static $hashCharMap = 'abcdefghijklnmopqistuvwxyzABCDEFGHIJKLNMOPQRSTUVWXYZ1234567890`~!@#$%^&*()[]\{}|;\':",./<>?';

    /* Method for init */
    public static function init($setting = array())
    {
        if (!self::$inited) {
            self::$cores = Facula::getAllCores();

            self::$defaults = array(
                'Setting' => array(
                    'CookieKey' => isset($setting['CookieKey']) ? $setting['CookieKey'] : '!',
                    'Expire' => isset($setting['Expire']) ? (int)($setting['Expire']) : 3600,
                    'Salt' => isset($setting['Salt']) ? $setting['Salt'] : '',
                ),
            );

            self::$inited = true;

            register_shutdown_function(function () {
                self::update();
            });

            return true;
        }

        return false;
    }

    public static function setup($setting = array(), $type = 'General')
    {
        if (!isset(self::$sessions[$type]) && self::$inited) {
            self::$sessions[$type] = array(
                'Setting' => array(
                    'CookieKey' => isset($setting['CookieKey']) ? $setting['CookieKey']: self::$defaults['Setting']['CookieKey'],
                    'Expire' => isset($setting['Expire']) ? (int)($setting['Expire']): self::$defaults['Setting']['Expire'],
                    'Salt' => isset($setting['Salt']) ? $setting['Salt'] : self::$defaults['Setting']['Salt'],
                ),
                'Sessions' => array(),
                'Handlers' => array(),
            );

            return true;
        }

        return false;
    }

    private static function update()
    {
        $updateHandler = $garbagerHandler = null;
        $garbageExpiredTime = 0;

        foreach (self::$sessions as $type => $sessions) {
            $garbageExpiredTime = FACULA_TIME - $sessions['Setting']['Expire'];

            if (isset($sessions['Handlers']['Update'])) {
                $updateHandler = $sessions['Handlers']['Update'];

                foreach ($sessions['Sessions'] as $session) {
                    $updateHandler($session);
                }
            }

            if (isset($sessions['Handlers']['Garbage'])) {
                if (!self::$cores['cache']->load('session-lock-' . $type, $sessions['Setting']['Expire'])) {
                    $garbagerHandler = $sessions['Handlers']['Garbage'];

                    $garbagerHandler($garbageExpiredTime);

                    self::$cores['cache']->save('session-lock-' . $type, true);
                }
            }
        }

        return true;
    }

    /* Set Handlers */
    public static function setReader(Closure $handler, $for = 'General')
    {
        if (isset(self::$sessions[$for])) {
            if (!isset(self::$sessions[$for]['Handlers']['Read'])) {
                self::$sessions[$for]['Handlers']['Read'] = $handler;

                return true;
            } else {
                self::$cores['debug']->exception('ERROR_SESSION_SETREADER_ALREADY_EXISTED|' . $for, 'session', true);
            }
        } elseif (self::$inited) {
            self::$cores['debug']->exception('ERROR_SESSION_SETREADER_NOT_INITED|' . $for, 'session', true);
        }

        return false;
    }

    public static function setUpdater(Closure $handler, $for = 'General')
    {
        if (isset(self::$sessions[$for])) {
            if (!isset(self::$sessions[$for]['Handlers']['Update'])) {
                self::$sessions[$for]['Handlers']['Update'] = $handler;

                return true;
            } else {
                self::$cores['debug']->exception('ERROR_SESSION_SETWRITER_ALREADY_EXISTED|' . $for, 'session', true);
            }
        } elseif (self::$inited) {
            self::$cores['debug']->exception('ERROR_SESSION_SETWRITER_NOT_INITED|' . $for, 'session', true);
        }

        return false;
    }

    public static function setGarbager(Closure $handler, $for = 'General')
    {
        if (isset(self::$sessions[$for])) {
            if (!isset(self::$sessions[$for]['Handlers']['Garbage'])) {
                self::$sessions[$for]['Handlers']['Garbage'] = $handler;

                return true;
            } else {
                self::$cores['debug']->exception('ERROR_SESSION_SETGARBAGER_ALREADY_EXISTED|' . $for, 'session', true);
            }
        } elseif (self::$inited) {
            self::$cores['debug']->exception('ERROR_SESSION_SETGARBAGER_NOT_INITED|' . $for, 'session', true);
        }

        return false;
    }

    /* Get session in two way */
    public static function get($sessionKey, $for = 'General')
    {
        $handler = null;

        if (isset(self::$sessions[$for]['Sessions'][$sessionKey])) {
            return self::$sessions[$for]['Sessions'][$sessionKey];
        } elseif (isset(self::$sessions[$for]['Handlers']['Read'])) {
            $handler = self::$sessions[$for]['Handlers']['Read'];

            return (self::$sessions[$for]['Session'][$sessionKey] = $handler($sessionKey));
        }

        return false;
    }

    public static function getCurrent($for = 'General')
    {
        $sessionKeyInfo = array();

        if (($sessionKeyInfo = self::getCurrentKey($for)) && isset($sessionKeyInfo['Key'][0])) {
            if (isset(self::$sessions[$for]['Sessions'][$sessionKeyInfo['Key']])) {
                return self::$sessions[$for]['Sessions'][$sessionKeyInfo['Key']];
            } elseif (isset(self::$sessions[$for]['Handlers']['Read'])) {
                $handler = self::$sessions[$for]['Handlers']['Read'];

                return (self::$sessions[$for]['Sessions'][$sessionKeyInfo['Key']] = $handler($sessionKeyInfo['Key'], $sessionKeyInfo['Safe']));
            }
        }

        return false;
    }

    // Following method for calc current sessionKey()
    private static function getCurrentKey($for = 'General')
    {
        $sessionKey = $sessionRawKey = '';
        $sessionKeyInfo = array();
        $safeSession = false;
        $networkID = self::$cores['request']->getClientInfo('ip');

        if (isset(self::$sessions[$for]['Setting'])) {
            if (!isset(self::$currentSessionKeys[$for])) {
                // Check if this user already has the session key in it's cookie
                if (($sessionRawKey = self::$cores['request']->getCookie(self::$sessions[$for]['Setting']['CookieKey'])) && ($sessionKeyInfo = self::verifyKey($sessionRawKey, $networkID, $for))) {
                    $sessionKey = $sessionKeyInfo['Verify'];
                    $safeSession = true;

                    if ($sessionKeyInfo['Expire'] - (self::$sessions[$for]['Setting']['Expire'] / 2) < FACULA_TIME) {
                        $sessionKeyInfo['Expire'] = FACULA_TIME + self::$sessions[$for]['Setting']['Expire'];

                        self::$cores['response']->setCookie(self::$sessions[$for]['Setting']['CookieKey'], implode("\t", $sessionKeyInfo), self::$sessions[$for]['Setting']['Expire'], self::$cores['request']->getClientInfo('rootURL'), '', self::$cores['request']->getClientInfo('https'), true);
                    }
                } elseif ($sessionKeyInfo = self::generateKey($networkID, $for)) { // If not, generate one from it's ip address.
                    // Set a stable key for this temp session.
                    $sessionKey = hash('md5', $networkID);

                    // And try set the cookie key for next reading
                    self::$cores['response']->setCookie(self::$sessions[$for]['Setting']['CookieKey'], implode("\t", $sessionKeyInfo), self::$sessions[$for]['Setting']['Expire'], self::$cores['request']->getClientInfo('rootURL'), '', self::$cores['request']->getClientInfo('https'), true);
                } else {
                    self::$cores['debug']->exception('ERROR_SESSION_GET_CURRENT_KEY_FAILED|' . $for, 'session', true);
                }

                return (self::$currentSessionKeys[$for] = array(
                    'Safe' => $safeSession,
                    'Key' => $sessionKey,
                ));
            } else {
                return self::$currentSessionKeys[$for];
            }
        }

        return array();
    }

    private static function generateKey($networkID, $for = 'General')
    {
        global $_SERVER;
        $key = $rawKey = array();

        $hasher = new Hash(self::$sessions[$for]['Setting']['Salt'], 1);

        $rawKey = array(
            'ClientID' => self::$hashCharMap[mt_rand(0, 89) % 90] . FACULA_TIME . $networkID . mt_rand(0, 65535) . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown') . self::$hashCharMap[mt_rand(0, 89) % 90],
            'NetID' => $networkID,
        );

        $key['Client'] = $hasher->obscuredVerify($rawKey['ClientID']);
        $key['Verify'] = $hasher->obscuredVerify($key['Client'] . $rawKey['NetID']);
        $key['Expire'] = self::$sessions[$for]['Setting']['Expire'] + FACULA_TIME;

        return $key;
    }

    private static function verifyKey($sessionRawKey, $networkID, $for = 'General')
    {
        $key = $rawKey = array();

        $hasher = new Hash(self::$sessions[$for]['Setting']['Salt'], 1);
        $inputKey = explode("\t", $sessionRawKey, 3);

        if (isset($inputKey[0], $inputKey[1], $inputKey[2])) {
            $key = array(
                'Client' => $inputKey[0],
                'Verify' => $inputKey[1],
                'Expire' => (int)($inputKey[2]),
            );

            if ($key['Verify'] === $hasher->obscuredVerify($key['Client'] . $networkID)) {
                return $key;
            }
        }

        return $key;
    }
}
