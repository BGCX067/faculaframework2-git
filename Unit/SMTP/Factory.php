<?php

/**
 * SMTP Factory
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
    private static $config = array();

    /** Container of email that waiting to be send */
    private static $emails = array();

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

        if (empty(self::$config)) {
            $version = \Facula\Framework::getVersion();
            $senderIP = \Facula\Unit\IP::joinIP(
                \Facula\Framework::core('request')->getClientInfo('ipArray'),
                true
            );

            self::$config['Handler'] = $version['App'] . ' ' . $version['Ver'];

            if (isset($cfg['Servers']) && is_array($cfg['Servers'])) {
                if (isset($cfg['SelectMethod'])
                    && $cfg['SelectMethod'] == 'Random') {
                    shuffle($cfg['Servers']);
                }

                foreach ($cfg['Servers'] as $key => $val) {
                    self::$config['Servers'][$key] = array(
                        'Host' => isset($val['Host']) ? $val['Host'] : 'localhost',
                        'Port' => isset($val['Port']) ? $val['Port'] : 25,
                        'Type' => isset($val['Type']) ? $val['Type'] : 'general',
                        'Timeout' => isset($val['Timeout']) ? $val['Timeout'] : 1,
                        'Username' => isset($val['Username']) ? $val['Username'] : '',
                        'Password' => isset($val['Password']) ? $val['Password'] : '',
                        'Handler' => self::$config['Handler'],
                        'SenderIP' => $senderIP,
                    );

                    // Set poster screen name, this will be display on the receiver's list
                    if (isset($val['Screenname'])) {
                        self::$config['Servers'][$key]['Screenname'] =
                            $val['Screenname'];
                    } else {
                        self::$config['Servers'][$key]['Screenname'] =
                            self::$config['Servers'][$key]['Username'];
                    }

                    // Set MAIL FROM, this one must be set for future use
                    if (isset($val['From'])) {
                        if (\Facula\Unit\Validator::check($val['From'], 'email')) {
                            self::$config['Servers'][$key]['From'] = $val['From'];
                        } else {
                            \Facula\Framework::core('debug')->exception(
                                'ERROR_SMTP_ADDRESS_FORM_INVALID|' . $val['From'],
                                'smtp',
                                true
                            );
                        }
                    } else {
                        self::$config['Servers'][$key]['From'] =
                            self::$config['Servers'][$key]['Username']
                            . '@'
                            . self::$config['Servers'][$key]['Host'];
                    }

                    // Set REPLY TO
                    if (isset($val['ReplyTo'])) {
                        if (\Facula\Unit\Validator::check($val['ReplyTo'], 'email')) {
                            self::$config['Servers'][$key]['ReplyTo'] = $val['ReplyTo'];
                        } else {
                            \Facula\Framework::core('debug')->exception(
                                'ERROR_SMTP_ADDRESS_REPLYTO_INVALID|' . $val['ReplyTo'],
                                'smtp',
                                true
                            );
                        }
                    } else {
                        self::$config['Servers'][$key]['ReplyTo'] =
                            self::$config['Servers'][$key]['From'];
                    }

                    // Set RETURN TO
                    if (isset($val['ReturnTo'])) {
                        if (\Facula\Unit\Validator::check($val['ReturnTo'], 'email')) {
                            self::$config['Servers'][$key]['ReturnTo'] = $val['ReturnTo'];
                        } else {
                            \Facula\Framework::core('debug')->exception(
                                'ERROR_SMTP_ADDRESS_RETURNTO_INVALID|' . $val['ReturnTo'],
                                'smtp',
                                true
                            );
                        }
                    } else {
                        self::$config['Servers'][$key]['ReturnTo'] =
                            self::$config['Servers'][$key]['From'];
                    }

                    // Set ERROR TO
                    if (isset($val['ErrorTo'])) {
                        if (\Facula\Unit\Validator::check($val['ErrorTo'], 'email')) {
                            self::$config['Servers'][$key]['ErrorTo'] = $val['ErrorTo'];
                        } else {
                            \Facula\Framework::core('debug')->exception(
                                'ERROR_SMTP_ADDRESS_ERRORTO_INVALID|' . $val['ErrorTo'],
                                'smtp',
                                true
                            );
                        }
                    } else {
                        self::$config['Servers'][$key]['ErrorTo'] = self::$config['Servers'][$key]['From'];
                    }
                }

                \Facula\Framework::registerHook(
                    'response_finished',
                    'SMTP_Sending',
                    function () {
                        self::sendMail();
                    }
                );

                Auther::init();

                return true;
            } else {
                \Facula\Framework::core('debug')->exception('ERROR_SMTP_NOSERVER', 'smtp', true);
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
        self::$emails[] = array(
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
        $remainingMails = count(self::$emails);
        $retryLimit = 3;
        $currentServers = array();

        if (!empty(self::$config) && $remainingMails > 0) {
            $currentServers = self::$config['Servers'];

            \Facula\Framework::core('debug')->criticalSection(true);

            try {
                while (!empty($currentServers)
                    && !empty(self::$emails) && $retryLimit > 0) {
                    foreach ($currentServers as $serverkey => $server) {
                        $operaterClassName = static::getOperator($server['Type']);

                        if (class_exists($operaterClassName, true)) {
                            $operater = new $operaterClassName($server);

                            if ($operater instanceof Base) {
                                if ($operater->connect($error)) {

                                    foreach (self::$emails as $mailkey => $email) {
                                        if ($operater->send($email)) {
                                            unset(self::$emails[$mailkey]);
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
                                \Facula\Framework::core('debug')->exception(
                                    'ERROR_SMTP_OPERATOR_BASE_INVALID|' . $operaterClassName,
                                    'smtp',
                                    true
                                );
                            }
                        } else {
                            \Facula\Framework::core('debug')->exception(
                                'ERROR_SMTP_OPERATOR_NOTFOUND|' . $server['Type'],
                                'smtp',
                                true
                            );
                        }
                    }
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }

            if ($error) {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_SMTP_OPERATOR_ERROR|' . $error,
                    'smtp',
                    false
                );
            }

            \Facula\Framework::core('debug')->criticalSection(false);

            return true;
        }

        return false;
    }
}
