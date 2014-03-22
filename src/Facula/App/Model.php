<?php

/**
 * Base Model
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

namespace Facula\App;

/**
 * Base model in abstract
 */
abstract class Model
{
    /**
     * Abstract method: Insert the data
     *
     * @param array $data The data with key => val pair
     *
     * @return mixed Return the value of primary key when succeed, false otherwise
     */
    abstract public function create(array $data);

    /**
     * Abstract method: Update the data
     *
     * @param mixed $primaryKey Primary key of the data
     * @param array $data The data with key => val pair
     *
     * @return mixed Return the value of primary key when succeed, false otherwise
     */
    abstract public function update($primaryKey, array $data);

    /**
     * Abstract method: Get data
     *
     * @param mixed $primaryKey Primary key of the data
     *
     * @return mixed return the data when succeed, false otherwise
     */
    abstract public function read($primaryKey);

    /**
     * Abstract method: Get data
     *
     * @param mixed $primaryKey Primary key of the data
     *
     * @return bool Return true when succeed, false otherwise
     */
    abstract public function delete($primaryKey);
}
