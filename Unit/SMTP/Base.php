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
 * @package    FaculaFramework
 * @version    2.2 prototype
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Unit\SMTP;

abstract class Base
{
    private $socket = null;

    abstract public function connect(&$error);
    abstract public function send(array &$email);
    abstract public function disconnect();

    final protected function getSocket($host, $port, $timeout)
    {
        if (!$this->socket) {
            $this->socket = new Socket($host, $port, $timeout);
        }

        return $this->socket;
    }

    final protected function getAuth(array &$auths)
    {
        if ($this->socket) {
            return new Auther($this->socket, $auths);
        }

        return false;
    }

    final protected function getData(array $mail)
    {
        return new Datar($mail);
    }
}
