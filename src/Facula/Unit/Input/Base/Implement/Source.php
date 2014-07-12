<?php

namespace Facula\Unit\Input\Base\Implement;

/**
 * Source Interface
 */
interface Source
{
    /** Create a new instance of the Source object */
    public static function create();

    /** Get data from source */
    public function get($key);

    /** Check if there is any error during the progress */
    public function errored();

    /** Get all errors */
    public function errors();

    /** Get status of acceptation of submitted datas */
    public function accepted();
}
