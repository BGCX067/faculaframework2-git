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

namespace Facula\Unit\Query;

interface Implement
{
    public static function from($tableName, $autoParse = false);
    public static function addAutoParser($name, $type, \Closure $parser);
    public static function registerAdapter($driver, $adapterClass);

    public function select($fields);
    public function insert($fields);
    public function update($fields);
    public function delete($fields);

    public function where($logic, $fieldName, $operator, $value);
    public function having($logic, $fieldName, $operator, $value);

    public function group($fieldName);
    public function order($fieldName, $sort);

    public function value($value);
    public function set($values);

    public function limit($offset, $distance);

    public function get();
    public function fetch();
    public function save();
}