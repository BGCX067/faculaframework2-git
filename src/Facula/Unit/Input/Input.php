<?php

/**
 * Input
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

namespace Facula\Unit\Input;

use Facula\Unit\Input\Base\Resulting;
use Facula\Unit\Input\Base\Source;
use Facula\Unit\Input\Base\Field;
use Facula\Unit\Input\Base\Error;
use Facula\Unit\Input\Base\Results;
use Facula\Unit\Input\Base\Exception\Input as Exception;

/**
 * Input
 */
class Input
{
    /** Instance if imported source */
    protected $source = null;

    /** Catched errors */
    protected $errors = array();

    /** Input fields */
    protected $fields = array();

    /** Inputed data */
    protected $inputed = array();

    /**
     * Create a new instance of input
     *
     * @param Source $source a import source instance
     *
     * @return A new instance of Input
     */
    public static function from($source)
    {
        return new static($source);
    }

    /**
     * Constructor of Input
     *
     * @param Source $source a import source instance
     *
     * @return void
     */
    protected function __construct(Source $source)
    {
        $sourceErrors = array();

        $this->source = $source;

        if ($this->source->errored()) {
            foreach ($this->source->errors() as $error) {
                $this->error($error);
            }

            $this->accepted = false;

            return;
        }

        if (!$this->source->accepted()) {
            $this->error(new Error('ERROR', 'UNACCEPTABLE'));
        }
    }

    /**
     * Set a new error
     *
     * @param Error $error A instance from Error
     *
     * @return bool always true
     */
    protected function error(Error $error)
    {
        $this->errors[] = $error;

        return true;
    }

    /**
     * Set a new field
     *
     * @param Field $field A instance from Field
     *
     * @return Object Current instance of Input instance
     */
    public function field(Field $field)
    {
        $errors = array();
        $fieldName = $field->field();
        $fieldData = $this->source->get($fieldName);

        if (isset($this->fields[$fieldName])) {
            throw new Exception\FieldAlreadyRegistered($fieldName);

            return false; // No needed
        }

        // Import the value into field instance
        if (!$field->import($fieldData)) {
            if ($errors = $field->errors()) {
                foreach ($errors as $error) {
                    $this->error(new Error(
                        $error->type(),
                        $error->code(),
                        $fieldName,
                        $error->data()
                    ));
                }
            } else {
                $this->error(new Error('ERROR', 'FIELD_UNKNOWN_ERROR', $fieldName));
            }
        }

        $this->fields[$fieldName] = $field;

        return $this;
    }

    /**
     * Set new fields
     *
     * @param Field $field Instances from Field
     *
     * @return Object Current instance of Input instance
     */
    public function fields()
    {
        foreach (func_get_args() as $arg) {
            $this->field($arg);
        }

        return $this;
    }

    /**
     * Get all errors to a reference
     *
     * @param array &$errors The array reference
     *
     * @return Object Current instance of Input instance
     */
    public function errors(array &$errors)
    {
        $errors = array_values($this->errors);

        return $this;
    }

    /**
     * Get all original input to a reference
     *
     * @param array &$inputed The array reference
     *
     * @return Object Current instance of Input instance
     */
    public function original(array &$inputed)
    {
        $inputed = $this->inputed;

        foreach ($this->fields as $fieldName => $field) {
            $inputed[$fieldName] = $field->original();
        }

        return $this;
    }

    /**
     * Prepare the input result
     *
     * @return Object New instance if Results
     */
    public function prepare()
    {
        $fieldData = array();

        if (empty($this->errors)) {
            foreach ($this->fields as $fieldName => $field) {
                $fieldData[$fieldName] = $field->result();

                if (!($fieldData[$fieldName] instanceof Resulting)) {
                    throw new Exception\ResultingObjectInvalid($fieldName);

                    break;
                }
            }
        }

        return new Results($fieldData);
    }
}
