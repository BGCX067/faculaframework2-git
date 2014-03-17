<?php

/**
 * Session Manager
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
 * @copyright  2014 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Unit;

/**
 * Session Manager
 */
class Session
{
    /** Bool for anti re-initing */
    private static $inited = false;

    /** Default settings */
    private static $defaults = array();

    /** Current session keys */
    private static $currentSessionKeys = array();

    /** Loaded sessions */
    private static $sessions = array();

    /** Cores from facula */
    private static $cores = array();

    /**
     * Initializer
     *
     * @param array $setting Configure array
     *
     * @return bool Return true when initialized, false when fail.
     *              Remember re-init cause fail too
     */
    public static function init(array $setting = array())
    {
        if (!self::$inited) {
            self::$cores = \Facula\Framework::getAllCores();

            self::$defaults = array(
                'Setting' => array(
                    'CookieKey' => isset($setting['CookieKey'])
                                    ? $setting['CookieKey'] : '!',

                    'Expire' => isset($setting['Expire'])
                                    ? (int)($setting['Expire']) : 3600,

                    'Salt' => isset($setting['Salt'])
                                    ? $setting['Salt'] : '',

                    'RandomKeyLen' => isset($setting['RandomKeyLen'])
                                    ? (int)$setting['RandomKeyLen'] : 16,
                ),
            );

            self::$inited = true;

            register_shutdown_function(function () {
                return self::update();
            });

            return true;
        }

        return false;
    }

    /**
     * Setup the specified session
     *
     * @param array $setting Configure array of the array
     * @param string $type Configure array of the array
     *
     * @return bool Return true when setup success, false when fail
     */
    public static function setup(array $setting = array(), $type = 'General')
    {
        if (!isset(self::$sessions[$type]) && self::$inited) {
            self::$sessions[$type] = array(
                'Setting' => array(
                    'CookieKey' => isset($setting['CookieKey'])
                        ? $setting['CookieKey']: self::$defaults['Setting']['CookieKey'],

                    'Expire' => isset($setting['Expire'])
                        ? (int)($setting['Expire']): self::$defaults['Setting']['Expire'],

                    'Salt' => isset($setting['Salt'])
                        ? $setting['Salt'] : self::$defaults['Setting']['Salt'],

                    'RandomKeyLen' => isset($setting['RandomKeyLen'])
                        ? $setting['RandomKeyLen'] : self::$defaults['Setting']['RandomKeyLen'],
                ),
                'Sessions' => array(),
                'Handlers' => array(),
            );

            return true;
        }

        return false;
    }

    /**
     * Update the session with handlers
     *
     * @return array Array of results from update handlers
     */
    private static function update()
    {
        $updateHandler = $garbagerHandler = null;
        $garbageExpiredTime = 0;

        $result = array();

        foreach (self::$sessions as $type => $sessions) {
            $garbageExpiredTime = FACULA_TIME - $sessions['Setting']['Expire'];

            if (isset($sessions['Handlers']['Update'])) {
                $updateHandler = $sessions['Handlers']['Update'];

                foreach ($sessions['Sessions'] as $session) {
                    $result[] = $updateHandler($session);
                }
            }

            if (isset($sessions['Handlers']['Garbage'])) {
                if (!self::$cores['cache']->load(
                    'session-lock-' . $type
                )) {
                    $garbagerHandler = $sessions['Handlers']['Garbage'];

                    $result[] = $garbagerHandler($garbageExpiredTime);

                    self::$cores['cache']->save(
                        'session-lock-' . $type,
                        true,
                        $sessions['Setting']['Expire']
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Set a reader handler for specified session
     *
     * @param closure $handler The reader function in closure
     * @param string $for The session that this reader will be set for
     *
     * @return bool Return true when set, false otherwise
     */
    public static function setReader(\Closure $handler, $for = 'General')
    {
        if (isset(self::$sessions[$for])) {
            if (!isset(self::$sessions[$for]['Handlers']['Read'])) {
                self::$sessions[$for]['Handlers']['Read'] = $handler;

                return true;
            } else {
                self::$cores['debug']->exception(
                    'ERROR_SESSION_SETREADER_ALREADY_EXISTED|' . $for,
                    'session',
                    true
                );
            }
        } elseif (self::$inited) {
            self::$cores['debug']->exception(
                'ERROR_SESSION_SETREADER_NOT_INITED|' . $for,
                'session',
                true
            );
        }

        return false;
    }

    /**
     * Set a updater handler for specified session
     *
     * @param closure $handler The reader function in closure
     * @param string $for The session that this updater will be set for
     *
     * @return bool Return true when set, false otherwise
     */
    public static function setUpdater(\Closure $handler, $for = 'General')
    {
        if (isset(self::$sessions[$for])) {
            if (!isset(self::$sessions[$for]['Handlers']['Update'])) {
                self::$sessions[$for]['Handlers']['Update'] = $handler;

                return true;
            } else {
                self::$cores['debug']->exception(
                    'ERROR_SESSION_SETWRITER_ALREADY_EXISTED|' . $for,
                    'session',
                    true
                );
            }
        } elseif (self::$inited) {
            self::$cores['debug']->exception(
                'ERROR_SESSION_SETWRITER_NOT_INITED|' . $for,
                'session',
                true
            );
        }

        return false;
    }

    /**
     * Set a garbager handler for specified session
     *
     * @param closure $handler The reader function in closure
     * @param string $for The session that this garbager will be set for
     *
     * @return bool Return true when set, false otherwise
     */
    public static function setGarbager(\Closure $handler, $for = 'General')
    {
        if (isset(self::$sessions[$for])) {
            if (!isset(self::$sessions[$for]['Handlers']['Garbage'])) {
                self::$sessions[$for]['Handlers']['Garbage'] = $handler;

                return true;
            } else {
                self::$cores['debug']->exception(
                    'ERROR_SESSION_SETGARBAGER_ALREADY_EXISTED|' . $for,
                    'session',
                    true
                );
            }
        } elseif (self::$inited) {
            self::$cores['debug']->exception(
                'ERROR_SESSION_SETGARBAGER_NOT_INITED|' . $for,
                'session',
                true
            );
        }

        return false;
    }

    /**
     * Get a session with session key
     *
     * @param string $sessionKey The reader function in closure
     * @param string $for The type of the session
     *
     * @return bool Return the result of session reader handler when found, or false when fail
     */
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

    /**
     * Get current session
     *
     * @param string $for The type of the session
     *
     * @return bool Return the result of session reader handler when found, or false when fail
     */
    public static function getCurrent($for = 'General')
    {
        $sessionKeyInfo = array();

        if (($sessionKeyInfo = self::getCurrentKey($for)) && isset($sessionKeyInfo['Key'][0])) {
            if (isset(self::$sessions[$for]['Sessions'][$sessionKeyInfo['Key']])) {
                return self::$sessions[$for]['Sessions'][$sessionKeyInfo['Key']];
            } elseif (isset(self::$sessions[$for]['Handlers']['Read'])) {
                $handler = self::$sessions[$for]['Handlers']['Read'];

                return (self::$sessions[$for]['Sessions'][$sessionKeyInfo['Key']] = $handler(
                    $sessionKeyInfo['Key'],
                    $sessionKeyInfo['Safe'],
                    $sessionKeyInfo['Renew']
                ));
            }
        }

        return false;
    }

    /**
     * Get key for current session and send a new key to client if the key not existed
     *
     * @param string $for The type of the session
     *
     * @return array Array of session key
     */
    private static function getCurrentKey($for = 'General')
    {
        $sessionKey = $sessionRawKey = '';
        $sessionKeyInfo = array();
        $safeSession = $needRenew = false;
        $networkID = self::$cores['request']->getClientInfo('ip');

        if (isset(self::$sessions[$for]['Setting'])) {
            if (!isset(self::$currentSessionKeys[$for])) {
                // Check if this user already has the session key in it's cookie
                if (($sessionRawKey = self::$cores['request']->getCookie(
                    self::$sessions[$for]['Setting']['CookieKey']
                ))
                &&
                ($sessionKeyInfo = self::verifyKey(
                    $sessionRawKey,
                    $networkID,
                    $for
                ))) {
                    $sessionKey = $sessionKeyInfo['Verify'];
                    $safeSession = true;

                    if ($sessionKeyInfo['Expire']
                        - (self::$sessions[$for]['Setting']['Expire'] / 2)
                        < FACULA_TIME) {
                        $sessionKeyInfo['Expire'] =
                            FACULA_TIME + self::$sessions[$for]['Setting']['Expire'];

                        self::$cores['response']->setCookie(
                            self::$sessions[$for]['Setting']['CookieKey'],
                            implode("\t", $sessionKeyInfo),
                            self::$sessions[$for]['Setting']['Expire'],
                            self::$cores['request']->getClientInfo('rootURL') . '/',
                            '',
                            self::$cores['request']->getClientInfo('https'),
                            true
                        );

                        $needRenew = true;
                    }
                } elseif ($sessionKeyInfo = self::generateKey($networkID, $for)) {
                    // If not, generate one from it's ip address.
                    // Set a stable key for this temp session.
                    $sessionKey = hash('md5', $networkID);

                    // And try set the cookie key for next reading
                    self::$cores['response']->setCookie(
                        self::$sessions[$for]['Setting']['CookieKey'],
                        implode("\t", $sessionKeyInfo),
                        self::$sessions[$for]['Setting']['Expire'],
                        self::$cores['request']->getClientInfo('rootURL') . '/',
                        '',
                        self::$cores['request']->getClientInfo('https'),
                        true
                    );
                } else {
                    self::$cores['debug']->exception(
                        'ERROR_SESSION_GET_CURRENT_KEY_FAILED|' . $for,
                        'session',
                        true
                    );

                    return false;
                }

                return (self::$currentSessionKeys[$for] = array(
                    'Safe' => $safeSession,
                    'Key' => $sessionKey,
                    'Renew' => $needRenew,
                ));
            } else {
                return self::$currentSessionKeys[$for];
            }
        }

        return array();
    }

    /**
     * Generate a key for current session
     *
     * @param string $networkID The unique ID of session's network
     * @param string $for The type of the session
     *
     * @return array Array of session key
     */
    private static function generateKey($networkID, $for = 'General')
    {
        global $_SERVER;
        $key = $rawKey = array();
        $randomKey = '';

        $hasher = new Hasher(self::$sessions[$for]['Setting']['Salt'], 1);

        for ($i = 0; $i < static::$sessions[$for]['Setting']['RandomKeyLen']; $i++) {
            $randomKey .= chr(mt_rand(0, 255));
        }

        $rawKey = array(
            'ClientID' => $randomKey
                        . FACULA_TIME
                        . $networkID
                        . (isset($_SERVER['HTTP_USER_AGENT'])
                        ? $_SERVER['HTTP_USER_AGENT'] : 'unknown')
                        . $randomKey[0],
            'NetID' => $networkID,
        );

        $key['Client'] = $hasher->obscuredVerify($rawKey['ClientID']);
        $key['Verify'] = $hasher->obscuredVerify($key['Client'] . $rawKey['NetID']);
        $key['Expire'] = self::$sessions[$for]['Setting']['Expire'] + FACULA_TIME;

        return $key;
    }

    /**
     * Verify a key, check if it generated by the website
     *
     * @param string $sessionRawKey The key to check
     * @param string $networkID The unique ID of session's network
     * @param string $for The type of the session
     *
     * @return string The key
     */
    private static function verifyKey($sessionRawKey, $networkID, $for = 'General')
    {
        $key = $rawKey = array();

        $hasher = new Hasher(self::$sessions[$for]['Setting']['Salt'], 1);
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

        return false;
    }
}
