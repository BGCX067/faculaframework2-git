<?php

namespace Facula\Tests\Framework\Core\Template;

use Facula\Base\Prototype\Core\Template as Target;

class Dummy extends Target
{
    /**
     * Override the constructor
     */
    public function __construct()
    {
        // Do nothing
    }

    public function setConfig($key, $val)
    {
        $this->configs[$key] = $val;
    }

    public function handleCacheExcludeAreaDelegate(&$compliedTemplate, $task, $removeTag)
    {
        return $this->handleCacheExcludeArea($compliedTemplate, $task, $removeTag);
    }
}
