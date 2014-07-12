<?php

/**
 * Input Field
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

namespace Facula\Unit\Input\Base;

use Facula\Unit\Input\Base\Implement\Field as Impl;
use Facula\Unit\Input\Base\Field\Error as FieldError;
use Facula\Unit\Input\Base\Limit;

/**
 * Input Field
 */
abstract class Field implements Impl
{
    /** Field name */
    protected $fieldName = '';

    /** Limit group container */
    protected $limits = array();

    /** Limit current group index */
    protected $limitGroup = 0;

    /** The value of this field */
    protected $value = null;

    /** The default value of this field */
    protected $defaults = null;

    /** Mark of field if required or not */
    protected $required = false;

    /** Error */
    protected $errors = array();

    /** The resulting object class */
    protected static $resulting = '';

    /**
     * Create a new field instance
     *
     * @param string $fieldName The name of the binded input field
     *
     * @return object New instance of Input field
     */
    final public static function bind($fieldName)
    {
        return new static($fieldName);
    }

    /**
     * Constructor
     *
     * @param string $fieldName The name of the binded input field
     *
     * @return void
     */
    final public function __construct($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * Set if this field is required
     *
     * @param bool $required The field is needed or not
     *
     * @return object Current instance of input field
     */
    final public function required($required = false)
    {
        if ($required) {
            $this->required = true;
        } else {
            $this->required = false;
        }

        return $this;
    }

    /**
     * Set the default value
     *
     * @param mixed $default The default value
     *
     * @return object Current instance of input field
     */
    final public function defaults($default)
    {
        // If $this->value == $this->defaults, means the value not been set yet (both null)
        if ($this->value == $this->defaults) {
            $this->value = $this->defaults = $default;
        } else {
            $this->defaults = $default;
        }

        return $this;
    }

    /**
     * Get the current field name
     *
     * @return string Current field name
     */
    final public function field()
    {
        return $this->fieldName;
    }

    /**
     * Get the current value name
     *
     * @return mixed Current value
     */
    final public function value()
    {
        return $this->value;
    }

    /**
     * Set a error
     *
     * @param Error $error The error instance of Facula\Unit\Input\Base\Field\Error
     *
     * @return bool Always true
     */
    final protected function error(FieldError $error)
    {
        $this->errors[] = $error;

        return true;
    }

    /**
     * Get all errors
     *
     * @return array Errors in array
     */
    final public function errors()
    {
        return $this->errors;
    }

    /**
     * Set a field limit
     *
     * @param Limit $limit The instance of Limit object
     * @param integer $limitGroupID The ID of the limit group
     *
     * @return object Instance of current field
     */
    final public function limit(Limit $limit, $limitGroupID = null)
    {
        if (is_null($limitGroupID)) {
            $limitGroupID = $this->limitGroup++;
        } else {
            $limitGroupID = (int)$limitGroupID;
        }

        $this->limits[$limitGroupID][] = $limit;

        return $this;
    }

    /**
     * Set field limits
     *
     * @param Limit $limits The instance of Limit objects
     *
     * @return object Instance of current field
     */
    final public function limits()
    {
        $limitGroupID = $this->limitGroup++;

        foreach (func_get_args() as $limit) {
            $this->limit($limit, $limitGroupID);
        }

        return $this;
    }

    /**
     * Check the value according to the Limit group
     *
     * @param mixed $value The value for the field
     * @param Error $value The value for the field
     *
     * @return bool Return true when succeed, false otherwise
     */
    final protected function checkLimit($value, &$error)
    {
        $groupPassed = true;
        $checkPassed = false;

        if (!empty($this->limits)) {
            foreach ($this->limits as $limitGroup) {
                // Test value with a test group, If any condition failed, test group failed
                $groupPassed = true;

                foreach ($limitGroup as $limit) {
                    if (!$limit->qualified($value, $error)) {
                        $groupPassed = false;

                        break;
                    }
                }

                // If no test group has succeed, check failed.
                if ($groupPassed) {
                    $checkPassed = true;

                    break;
                }
            }
        } else {
            $checkPassed = true;
        }

        return $checkPassed;
    }

    /**
     * Import the field value
     *
     * @param mixed $value The value that will be imported
     *
     * @return bool Return true when succeed, false otherwise
     */
    final public function import($value)
    {
        $error = null;

        if (is_null($value)) { // We use none as unsetted value
            if ($this->required) {
                $this->error(new FieldError('ERROR', 'REQUIRED'));

                return false;
            }

            if (is_null($this->defaults)) {
                $this->error(new FieldError('ERROR', 'DEFAULT_NOTSET'));

                return true;
            }

            $value = $this->defaults;
        }

        if (!$this->checkLimit($value, $error)) {
            $this->error($error);

            return false;
        }

        $this->value = $value;

        return true;
    }

    /**
     * Get field resulting object according to static::$resulting
     *
     * @return object Instance of the resulting object
     */
    final public function result()
    {
        return new static::$resulting($this->value());
    }
}
