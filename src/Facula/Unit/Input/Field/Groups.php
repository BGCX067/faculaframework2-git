<?php

/**
 * Group Field
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

namespace Facula\Unit\Input\Field;

use Facula\Unit\Input\Base\Field as Base;
use Facula\Unit\Input\Base\Exception\Field\Subs as Exception;
use Facula\Unit\Input\Base\Field\Error as Error;

/**
 * Group Fields
 */
class Groups extends Base
{
    /** Set the resulting class */
    protected static $resulting =
        'Facula\Unit\Input\Resulting\Wrapped';

    /** Set the resulting group class */
    protected static $resultingGroup =
        'Facula\Unit\Input\Resulting\Groups';

    /** Imported fields */
    protected $fields = array();

    /** Result objects */
    protected $results = array();

    /**
     * Convert import value into field
     *
     * @param mixed $value Inputing value
     * @param mixed $newValue Reference to a new input value used to replace the invalid one
     * @param mixed $error Reference to get error feedback
     *
     * @return bool Return false to truncate value input, true otherwise.
     */
    protected function parseImport($values, &$newValue, &$errorRef)
    {
        $errors = array();
        $results = array();

        foreach ($values as $vKey => $value) {
            foreach ($this->fields as $fieldName => $field) {
                $fieldData = isset($value[$fieldName]) ? $value[$fieldName] : null;

                // Import the value into field instance
                if (!$field->import($fieldData)) {
                    if ($errors = $field->errors()) {
                        foreach ($errors as $error) {
                            $errorRef = $error;

                            break;
                        }
                    } else {
                        $errorRef = new Error(
                            'ERROR',
                            'FIELD_UNKNOWN_ERROR',
                            array($fieldName)
                        );
                    }

                    return false;
                }

                $results[$vKey][$fieldName] = $field->result();
            }
        }

        $newValue = $results;

        return false;
    }

    /**
     * Add sub fields
     *
     * @return object Current instance
     */
    public function adds()
    {
        foreach (func_get_args() as $arg) {
            $this->add($arg);
        }

        return $this;
    }

    /**
     * Add a sub field
     *
     * @param Field Instance of Field object
     *
     * @return object Current instance
     */
    public function add(Base $field)
    {
        $fieldName = $field->field();

        if (isset($this->fields[$fieldName])) {
            throw new Exception\FieldAlreadyRegistered($fieldName, $this->field());

            return false; // No needed
        }

        $this->fields[$fieldName] = $field;

        return $this;
    }
}
