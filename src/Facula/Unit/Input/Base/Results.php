<?php

/**
 * Input Resulting Group Object
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

namespace Facula\Unit\Input\Base;

use Facula\Unit\Input\Base\Exception\Results as Exception;

/**
 * Input Resulting Group Object
 */
class Results
{
    /** Resulting group data */
    protected $fieldResults = array();

    /**
     * Constructor
     *
     * @param array $fieldResults Array of resulting group data
     *
     * @return void
     */
    public function __construct(array $fieldResults)
    {
        $this->fieldResults = $fieldResults;
    }

    /**
     * Get the element from the resulting group
     *
     * @param string $key The key (field) name of the result element
     *
     * @return Resulting The resulting object
     */
    public function get($key)
    {
        if (!isset($this->fieldResults[$key])) {
            throw new Exception\FieldNotFound($key);

            return false;
        }

        return $this->fieldResults[$key];
    }

    /**
     * Export the resulting group data
     *
     * @return array The resulting group
     */
    public function export()
    {
        return $this->fieldResults;
    }

    /**
     * Export the original data from every each resulting object
     *
     * @return array Array of original data
     */
    public function exportOriginals()
    {
        $originals = array();

        foreach ($this->fieldResults as $fieldName => $result) {
            $originals[$fieldName] = $result->original();
        }

        return $originals;
    }

    /**
     * Export the value data from every each resulting object
     *
     * @return array Array of original data
     */
    public function exportValues()
    {
        $values = array();

        foreach ($this->fieldResults as $fieldName => $result) {
            $values[$fieldName] = $result->value();
        }

        return $values;
    }
}
