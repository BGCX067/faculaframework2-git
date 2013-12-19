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

namespace Facula\Unit\SimpleORM;

interface Implement
{
    public function __set($key, $val);
    public function __get($key);
    public function __isset($key);

    public function getPrimaryValue();
    public function getFields();
    public function getData();

    public function get(array $param, $returnType = 'CLASS', $whereOperator = '=');
    public function fetch(array $param, $offset = 0, $dist = 0, $returnType = 'CLASS', $whereOperator = '=');

    public function finds(array $param, $offset = 0, $dist = 0, $returnType = 'CLASS');

    public function getInKey($keyField, $value, $param = array(), $returnType = 'CLASS', $whereOperator = '=');
    public function fetchInKeys($keyField, array $values, array $param = array(), $offset = 0, $dist = 0, $returnType = 'CLASS', $whereOperator = '=');

    public function getByPK($key, $returnType = 'CLASS');
    public function fetchByPKs($keys, array $param = array(), $offset = 0, $dist = 0, $returnType = 'CLASS', $whereOperator = '=');

    public function getWith(array $joinModels, array $whereParams, $whereOperator = '=');
    public function fetchWith(array $joinModels, array $currentParams, $offset = 0, $dist = 0, $whereOperator = '=');

    public function save();
    public function insert();
    public function delete();
}
