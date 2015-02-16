<?php

namespace Facula\Tests\Framework\Tool\PHP\Ini;

use Facula\Base\Tool\PHP\Ini as Target;

abstract class Dummy extends Target
{
    protected static $testData = array();

    /**
     * Shadow $phpIniData
     */
    protected static $phpIniData = array();

    public static function setTestData(array $newTestData)
    {
        static::$phpIniData = static::$testData = array();

        foreach ($newTestData as $key => $value) {
            static::$testData[$key] = array(
                'global_value' => $value,
                'local_value' => $value,
                'access' => 0,
            );
        }
    }

    protected static function initPHPIniData()
    {
        if (!empty(static::$phpIniData)) {
            return true;
        }

        static::$phpIniData = static::$testData;

        return true;
    }
}
