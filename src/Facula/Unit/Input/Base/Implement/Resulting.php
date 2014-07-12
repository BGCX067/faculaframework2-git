<?php

namespace Facula\Unit\Input\Base\Implement;

/**
 * Source Interface
 */
interface Resulting
{
    public function __construct($value);

    public function value();
    public function original();
}
