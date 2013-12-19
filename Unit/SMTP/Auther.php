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

namespace Facula\Unit\SMTP;

class Auther
{
    private $socket = null;
    private $auths = array();
    private static $authers = array();
    
    private static $inited = false;

    public function __construct($socket, array &$auths)
    {
        $this->socket = $socket;
        $this->auths = $auths;

        return true;
    }

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

        $error = 'NOTSUPPORTED|' . implode(', ', $this->auths) . ' for ' . implode(', ', array_keys(self::$authers));

        return false;
    }

    public static function register($type, \Closure $auther)
    {
        if (!isset(self::$authers[$type])) {
            self::$authers[$type] = $auther;

            return true;
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_SMTP_AUTHER_EXISTED', 'smtp', true);
        }

        return false;
    }

    public static function init()
    {
        if (self::$inited) {
            return true;
        } else {
            self::$inited = true;
        }
        
        static::register('plain', function ($socket, $username, $password, &$error) {
            $null = "\0";
            $plainAuthStr = rtrim(base64_encode($username . $null . $username . $null . $password), '=');

            if ($socket->put('AUTH PLAIN', 'one') != 334) {
                $error = 'UNKOWN_RESPONSE';
                return false;
            }

            switch ($socket->put($plainAuthStr, 'one')) { // Give the username and check return
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

            if ($socket->registerResponseParser(
                334,
                function ($param) {
                    $resp = strtolower(base64_decode($param)); // I have no idea why they decided to base64 this

                    switch ($resp) {
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
                }
            )) {
                switch ($response = $socket->put('AUTH LOGIN', 'one')) {
                    case 'Username':
                        // Response for user name
                        switch ($socket->put($b64Username, 'one')) { // Give the username and check return
                            case 'Password': // Want password, give password
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
                        switch ($socket->put($b64Password, 'one')) { // Give the password and check return
                            case 'Username': // Want username? give username
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

        return true;
    }
}


