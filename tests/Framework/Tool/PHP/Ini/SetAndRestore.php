<?php

namespace Facula\Tests\Framework\Tool\PHP\Ini;

use Facula\Base\Tool\PHP\Ini;
use PHPUnit_Framework_TestCase;

class SetAndRestore extends PHPUnit_Framework_TestCase
{
    protected $originalSettingValue = '';
    protected $originalSettingKey = '';

    /**
     * Try one of those setting key.
     *
     * If any of those value passes, the test will be valid.
     */
    protected static $trySettings = array(
        'user_agent'
    );

    public function setUp()
    {
        // Select the setting key from trySettings array
        foreach (static::$trySettings as $settingKey) {
            $this->originalSettingValue = ini_get($settingKey);

            // Notice the ini_get returns false when fail, and string when success
            if ($this->originalSettingValue !== false) {
                $this->originalSettingKey = $settingKey;

                break;
            }
        }

        if ($this->originalSettingValue === false) {
            $this->assertFalse(true);
        }
    }

    public function tearDown()
    {
        if ($this->originalSettingValue === false) {
            return;
        }

        // Well, hope it could succeed.
        ini_set($this->originalSettingKey, $this->originalSettingValue);
    }

    public function testSet()
    {
        Ini::set($this->originalSettingKey, 'The new test value');

        $this->assertSame(
            'The new test value',
            Ini::getStr($this->originalSettingKey)
        );
    }
}
