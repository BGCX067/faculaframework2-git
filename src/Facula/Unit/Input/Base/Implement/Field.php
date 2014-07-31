<?php

namespace Facula\Unit\Input\Base\Implement;

use Facula\Unit\Input\Base\Limit;

/**
 * Field
 */
interface Field
{
    /** Create the instance of Field for specified field name */
    public static function bind($fieldName);

    /** Set the default value */
    public function defaults($default);

    /** Set this field as required */
    public function required($required);

    /** Get field name */
    public function field();

    /** Set a limit group (multi condition must be matched to pass) */
    public function limits();

    /** Set a limit */
    public function limit(Limit $limit, $limitGroupID = null);

    /** Get the value of the field */
    public function value();

    /** Import the source data into this field instance */
    public function import($input);

    /** Get original input of this field */
    public function original();

    /** Get all errors from current field instance */
    public function errors();

    /** Get field result object */
    public function result();
}
