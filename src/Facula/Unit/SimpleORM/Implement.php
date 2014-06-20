<?php

/**
 * Interface of SimpleORM
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

namespace Facula\Unit\SimpleORM;

/**
 * SimpleORM Interface
 */
interface Implement
{
    public function __set($key, $val);
    public function &__get($key);
    public function __isset($key);
    public function __unset($key);

    public function offsetSet($offset, $value);
    public function &offsetGet($offset);
    public function offsetExists($offset);
    public function offsetUnset($offset);

    public function isChanged();

    public function getPrimaryValue();
    public function getFields();
    public function getData();

    public static function get(
        array $param,
        $returnType = 'CLASS',
        $whereOperator = '='
    );
    public static function fetch(
        array $param,
        $offset = 0,
        $dist = 0,
        $returnType = 'CLASS',
        $whereOperator = '='
    );
    public static function finds(
        array $param,
        $offset = 0,
        $dist = 0,
        $returnType = 'CLASS'
    );

    public static function getInKey(
        $keyField,
        $value,
        $param = array(),
        $returnType = 'CLASS',
        $whereOperator = '='
    );
    public static function fetchInKeys(
        $keyField,
        array $values,
        array $param = array(),
        $offset = 0,
        $dist = 0,
        $returnType = 'CLASS',
        $whereOperator = '='
    );

    public static function getBy($field, $value, $returnType = 'CLASS');
    public static function getByPK($value, $returnType = 'CLASS');
    public static function fetchByPKs(
        array $values,
        array $param = array(),
        $offset = 0,
        $dist = 0,
        $returnType = 'CLASS',
        $whereOperator = '='
    );

    public static function getWith(
        array $joinModels,
        array $whereParams,
        $whereOperator = '='
    );
    public static function fetchWith(
        array $joinModels,
        array $currentParams,
        $offset = 0,
        $dist = 0,
        $whereOperator = '='
    );

    public function save();
    public function insert();
    public function delete();
}
