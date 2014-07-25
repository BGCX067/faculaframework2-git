<?php

namespace Facula\Unit\Input\Base\Implement;

use Facula\Unit\Input\Base\Validator;

/**
 * Field
 */
interface Limit
{
    public static function create();
    public function qualified(&$value, &$error);
}
