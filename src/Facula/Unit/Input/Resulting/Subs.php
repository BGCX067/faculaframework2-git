<?php

/**
 * Sub Result
 *
 * Facula Framework 2015 (C) Rain Lee
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
 * @copyright  2015 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Unit\Input\Resulting;

use Facula\Unit\Input\Base\Exception\Resulting as Exception;
use Facula\Unit\Input\Base\Resulting;

/**
 * Sub Result
 */
class Subs extends Resulting
{
    /** The data type of current result */
    protected static $dataType = 'Group';

    /**
     * Get all group item from array
     *
     * @return array Return the group items
     */
    public function getAll()
    {
        $results = array();

        foreach ($this->value as $fieldKey => $field) {
            $results[$fieldKey] = $field->value();
        }

        return $results;
    }

    /**
     * Get all group item from array
     *
     * @param mixed Index of the group item
     *
     * @return array Return the group items
     */
    public function get($index)
    {
        if (isset($this->value[$index])) {
            return $this->value[$index]->value();
        }

        throw new Exception\GroupItemNotFound(
            $index,
            implode(', ', array_keys($this->value))
        );

        return null;
    }
}
