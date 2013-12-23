<?php

/**
 * SMTP Comm Base
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

/**
 * SMTP Base
 */
abstract class Base
{
    /** Connection socket */
    private $socket = null;

    /**
     * Connect to a SMTP server
     *
     * @param array $error String reference to get error detail
     *
     * @return bool Return true when connected, false for otherwise
     */
    abstract public function connect(&$error);

    /**
     * Send email
     *
     * @param array $email Email in array
     *
     * @return bool Return true when sent, false for otherwise
     */
    abstract public function send(array &$email);

    /**
     * Disconnect from SMTP server
     *
     * @return bool Return true when disconnected, false otherwise
     */
    abstract public function disconnect();

    /**
     * Get connection socket
     *
     * @param string $host Host name
     * @param integer $port Host port
     * @param integer $timeout Timeout
     *
     * @return object Return the instance of socket
     */
    final protected function getSocket($host, $port, $timeout)
    {
        if (!$this->socket) {
            $this->socket = new Socket($host, $port, $timeout);
        }

        return $this->socket;
    }

    /**
     * Get authenticator
     *
     * @param array $auths Server's authenticate method
     *
     * @return object Return the authenticator instance
     */
    final protected function getAuth(array &$auths)
    {
        if ($this->socket) {
            return new Auther($this->socket, $auths);
        }

        return false;
    }

    /**
     * Get Data MIME builder
     *
     * @param array $mail Mail content info
     *
     * @return object Return the MIME builder instance
     */
    final protected function getData(array $mail)
    {
        return new Datar($mail);
    }
}
