<?php

/**
 * SMTP Authenticator
 *
 * Facula Framework 2014 (C) Rain Lee
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

namespace Facula\Unit\SMTP;

use Facula\Unit\SMTP\Exception\Auther as Exception;

/**
 * The authentication operator for SMTP Socket
 */
class Auther
{
    /** Tag for Anti reinitializing */
    private static $inited = false;

    /** Socket instance */
    private $socket = null;

    /** Allowed authentication methods of current SMTP server */
    private $auths = array();

    /** Registered authentication operator */
    private static $authers = array();

    /**
     * Initialize the class for instantiation and operation
     *
     * @return bool Always return true
     */
    public static function init()
    {
        if (self::$inited) {
            return true;
        }

        static::register('plain', function ($socket, $username, $password, &$error) {
            $null = "\0";

            $plainAuthStr = rtrim(
                base64_encode($username . $null . $username . $null . $password),
                '='
            );

            if ($socket->put('AUTH PLAIN', 'one') != 334) {
                $error = 'UNKOWN_RESPONSE';
                return false;
            }

            // Give the username and check return
            switch ($socket->put($plainAuthStr, 'one')) {
                case 535:
                    $error = 'AUTHENTICATION_FAILED';
                    break;

                case 535:
                    $error = 'FROM_INVALID';
                    break;

                case 235:
                    return true;
                    break;

                default:
                    $error = 'UNKOWN_RESPONSE';
                    break;
            }

            return true;
        });

        static::register('login', function ($socket, $username, $password, &$error) {
            $response = '';
            $b64Username = rtrim(base64_encode($username), '=');
            $b64Password = rtrim(base64_encode($password), '=');
            $responseParser = function ($param) {
                switch (strtolower(base64_decode($param))) {
                    case 'username:':
                        return 'Username';
                        break;

                    case 'password:':
                        return 'Password';
                        break;

                    default:
                        return $param;
                        break;
                }
            };

            if ($socket->registerResponseParser(
                334,
                $responseParser
            )) {
                switch ($response = $socket->put('AUTH LOGIN', 'one')) {
                    case 'Username':
                        // Response for user name

                        // Give the username and check return
                        switch ($socket->put($b64Username, 'one')) {
                            case 'Password':
                                // Want password, give password
                                switch ($socket->put($b64Password, 'one')) {
                                    case 535:
                                        $error = 'AUTHENTICATION_FAILED';
                                        break;

                                    case 535:
                                        $error = 'FROM_INVALID';
                                        break;

                                    case 235:
                                        return true;
                                        break;

                                    default:
                                        $error = 'UNKOWN_RESPONSE';
                                        break;
                                }

                                break;
                        }
                        break;

                    case 'Password':
                        // Response for password, it's odd. First case is normal case
                        // Give the password and check return
                        switch ($socket->put($b64Password, 'one')) {
                            case 'Username':
                                // Want username? give username
                                switch ($socket->put($b64Password, 'one')) {
                                    case 535:
                                        $error = 'AUTHENTICATION_FAILED';
                                        break;

                                    case 235:
                                        return true;
                                        break;

                                    default:
                                        $error = 'UNKOWN_RESPONSE';
                                        break;
                                }

                                break;
                        }

                        break;

                    default:
                        $error = 'UNKOWN_RESPONSE';
                        return false;
                        break;
                }
            } else {
                $error = 'RESPONSE_PARSER_REGISTER_FAILED';
            }

            return false;
        });

        self::$inited = true;

        return true;
    }

    /**
     * Constructor of MIME builder
     *
     * @return void
     */
    public function __construct(Socket $socket, array &$auths)
    {
        $this->socket = $socket;
        $this->auths = $auths;
    }

    /**
     * Perform a authenticate operation with handler
     *
     * @param string $username User name to identification
     * @param string $password Password to identification
     * @param string $error A reference to get error feedback
     *
     * @return bool Return true when passed the identification, false when not
     */
    public function auth($username, $password, &$error = '')
    {
        $auther = null;

        foreach ($this->auths as $method) {
            if (isset(self::$authers[$method])) {
                $auther = self::$authers[$method];

                if ($auther($this->socket, $username, $password, $error)) {
                    return true;
                } else {
                    $error = $error ? $error : 'UNKONWN_ERROR';

                    return false;
                }

                break;
            }
        }

        $error = 'NOTSUPPORTED|'
                . implode(', ', $this->auths)
                . ' for '
                . implode(', ', array_keys(self::$authers));

        return false;
    }

    /**
     * Register
     *
     * @param string $type Type string of auther
     * @param closure $auther The auther itself.
     *
     * @return bool Return true when registered, false when fail
     */
    public static function register($type, \Closure $auther)
    {
        if (!isset(self::$authers[$type])) {
            self::$authers[$type] = $auther;

            return true;
        } else {
            throw new Exception\AutherAlreadyAssigned();
        }

        return false;
    }
}
