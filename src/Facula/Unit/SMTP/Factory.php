<?php

/**
 * SMTP Factory
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

use Facula\Unit\SMTP\Exception as Exception;

/*
$cfg = array(
    'SelectMethod' => 'Normal', // Normal|Random
    'Servers' => array(
        array(
            'Host' => 'smtp.server.com',
            'Timeout' => 5,
            'Username' => 'username',
            'Password' => 'password',
            'From' => 'username@server.com',
            'Screenname' => 'Sender Name',
        )
    )
);
*/

/**
 * SMTP Operating Factory
 */
class Factory extends \Facula\Base\Factory\Operator
{
    /** Global configuration of the class */
    protected static $config = array();

    /** Container of email that waiting to be send */
    protected static $emails = array();

    /** Default operators */
    protected static $operators = array(
        'general' => '\Facula\Unit\SMTP\Operator\General',
    );

    /**
     * SMTP initializer
     *
     * @param array $cfg Configuration for initialize the class
     *
     * @return bool Return true when success, or false when fail
     */
    public static function init(array $cfg = array())
    {
        $version = array();
        $senderIP = '';

        if (empty(static::$config)) {
            $version = \Facula\Framework::getVersion();
            $senderIP = \Facula\Unit\IP::joinIP(
                \Facula\Framework::core('request')->getClientInfo('ipArray'),
                true
            );

            static::$config['Handler'] = $version['App'] . ' ' . $version['Ver'];

            if (isset($cfg['Servers']) && is_array($cfg['Servers'])) {
                if (isset($cfg['SelectMethod'])
                && $cfg['SelectMethod'] == 'Random') {
                    shuffle($cfg['Servers']);
                }

                foreach ($cfg['Servers'] as $key => $val) {
                    static::$config['Servers'][$key] = array(
                        'Host' => isset($val['Host']) ? $val['Host'] : 'localhost',
                        'Port' => isset($val['Port']) ? $val['Port'] : 25,
                        'Type' => isset($val['Type']) ? $val['Type'] : 'general',
                        'Timeout' => isset($val['Timeout']) ? $val['Timeout'] : 1,
                        'Username' => isset($val['Username']) ? $val['Username'] : '',
                        'Password' => isset($val['Password']) ? $val['Password'] : '',
                        'Handler' => static::$config['Handler'],
                        'SenderIP' => $senderIP,
                    );

                    // Set poster screen name, this will be display on the receiver's list
                    if (isset($val['Screenname'])) {
                        static::$config['Servers'][$key]['Screenname'] =
                            $val['Screenname'];
                    } else {
                        static::$config['Servers'][$key]['Screenname'] =
                            static::$config['Servers'][$key]['Username'];
                    }

                    // Set MAIL FROM, this one must be set for future use
                    if (isset($val['From'])) {
                        if (\Facula\Unit\Validator::check($val['From'], 'email')) {
                            static::$config['Servers'][$key]['From'] = $val['From'];
                        } else {
                            throw new Exception\ServerFromAddressInvaild(
                                $val['From']
                            );
                        }
                    } else {
                        static::$config['Servers'][$key]['From'] =
                            static::$config['Servers'][$key]['Username']
                            . '@'
                            . static::$config['Servers'][$key]['Host'];
                    }

                    // Set REPLY TO
                    if (isset($val['ReplyTo'])) {
                        if (\Facula\Unit\Validator::check($val['ReplyTo'], 'email')) {
                            static::$config['Servers'][$key]['ReplyTo'] = $val['ReplyTo'];
                        } else {
                            throw new Exception\ServerReplyToAddressInvaild(
                                $val['ReplyTo']
                            );
                        }
                    } else {
                        static::$config['Servers'][$key]['ReplyTo'] =
                            static::$config['Servers'][$key]['From'];
                    }

                    // Set RETURN TO
                    if (isset($val['ReturnTo'])) {
                        if (\Facula\Unit\Validator::check($val['ReturnTo'], 'email')) {
                            static::$config['Servers'][$key]['ReturnTo'] = $val['ReturnTo'];
                        } else {
                            throw new Exception\ServerReturnToAddressInvaild(
                                $val['ReplyTo']
                            );
                        }
                    } else {
                        static::$config['Servers'][$key]['ReturnTo'] =
                            static::$config['Servers'][$key]['From'];
                    }

                    // Set ERROR TO
                    if (isset($val['ErrorTo'])) {
                        if (\Facula\Unit\Validator::check($val['ErrorTo'], 'email')) {
                            static::$config['Servers'][$key]['ErrorTo'] = $val['ErrorTo'];
                        } else {
                            throw new Exception\ServerErrorToAddressInvaild(
                                $val['ErrorTo']
                            );
                        }
                    } else {
                        static::$config['Servers'][$key]['ErrorTo'] = static::$config['Servers'][$key]['From'];
                    }
                }

                \Facula\Framework::registerHook(
                    'response_finished',
                    'SMTP_Sending',
                    function () {
                        static::sendMail();
                    }
                );

                Auther::init();

                return true;
            } else {
                throw new Exception\NoServerSpecified();
            }
        }

        return false;
    }

    /**
     * Add a email into sending queue
     *
     * @param string $title Title of the email
     * @param string $message Mail content
     * @param array $receivers Where the email will be send to
     *
     * @return bool Return true when success, or false when fail
     */
    public static function newMail($title, $message, array $receivers)
    {
        static::$emails[] = array(
            'Receivers' => $receivers,
            'Title' => $title,
            'Message' => $message,
        );

        return true;
    }

    /**
     * Perform email sending
     *
     * @return bool Return true when success, or false when fail
     */
    public static function sendMail()
    {
        $operater = null;
        $operaterClassName = $error = '';
        $remainingMails = count(static::$emails);
        $retryLimit = 3;
        $currentServers = array();

        if (!empty(static::$config) && $remainingMails > 0) {
            $currentServers = static::$config['Servers'];

            \Facula\Framework::core('debug')->criticalSection(true);

            while (!empty($currentServers)
                && !empty(static::$emails) && $retryLimit > 0) {
                foreach ($currentServers as $serverkey => $server) {
                    $operaterClassName = static::getOperator($server['Type']);

                    if (class_exists($operaterClassName)) {
                        $operater = new $operaterClassName($server);

                        if ($operater instanceof Base) {
                            if ($operater->connect($error)) {

                                foreach (static::$emails as $mailkey => $email) {
                                    if ($operater->send($email)) {
                                        unset(static::$emails[$mailkey]);
                                    } else {
                                        $retryLimit--;
                                        break;
                                        // There is no point to continue try this connection
                                        // to send another email after fail.
                                    }
                                }

                                $operater->disconnect();
                            } else {
                                $error .= ' on server: ' . $server['Host'];

                                unset($currentServers[$serverkey]);
                            }
                        } else {
                            throw new Exception\OperatorExtendsInvalid(
                                $operaterClassName
                            );
                        }
                    } else {
                        throw new Exception\OperatorClassNotFound(
                            $operaterClassName,
                            $server['Type']
                        );
                    }
                }
            }

            \Facula\Framework::core('debug')->criticalSection(false);

            if (!$error) {
                return true;
            }
        }

        return false;
    }
}
