<?php

/**
 * Request Core Interface
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

namespace Facula\Base\Implement\Core;

/**
 * Interface that must be implemented by any Request function core
 */
interface Request
{
    public function inited();
    public function get($type, $key, &$errored = false);
    public function gets($type, array $keys, array &$errors = array(), $failfalse = false);
    public function getCookie($key);
    public function getPost($key);
    public function getGet($key);
    public function getPosts(array $keys, array &$errors = array());
    public function getGets(array $keys, array &$errors = array());
    public function getClientInfo($key);
}
