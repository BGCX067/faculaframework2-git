<?php

namespace Facula\Tests\Framework\Tool\PHP\Ini;

use PHPUnit_Framework_TestCase;

class BoolValueTest extends PHPUnit_Framework_TestCase
{
    public function testOnOff()
    {
        Dummy::setTestData(array(
            'Setting-Value-1' => 'On',
            'Setting-Value-2' => 'Off',

            'Setting-Value-3' => 'ON',
            'Setting-Value-4' => 'OFF',

            'Setting-Value-5' => 'oN',
            'Setting-Value-6' => 'oFF',
        ));

        $this->assertSame(true, Dummy::getBool('Setting-Value-1'));
        $this->assertSame(false, Dummy::getBool('Setting-Value-2'));

        $this->assertSame(true, Dummy::getBool('Setting-Value-3'));
        $this->assertSame(false, Dummy::getBool('Setting-Value-4'));

        $this->assertSame(true, Dummy::getBool('Setting-Value-5'));
        $this->assertSame(false, Dummy::getBool('Setting-Value-6'));
    }

    public function testYesNo()
    {
        Dummy::setTestData(array(
            'Setting-Value-1' => 'yes',
            'Setting-Value-2' => 'no',

            'Setting-Value-3' => 'Yes',
            'Setting-Value-4' => 'No',

            'Setting-Value-5' => 'YEs',
            'Setting-Value-6' => 'nO',
        ));

        $this->assertSame(true, Dummy::getBool('Setting-Value-1'));
        $this->assertSame(false, Dummy::getBool('Setting-Value-2'));

        $this->assertSame(true, Dummy::getBool('Setting-Value-3'));
        $this->assertSame(false, Dummy::getBool('Setting-Value-4'));

        $this->assertSame(true, Dummy::getBool('Setting-Value-5'));
        $this->assertSame(false, Dummy::getBool('Setting-Value-6'));
    }

    public function testTrueFalse()
    {
        Dummy::setTestData(array(
            'Setting-Value-1' => 'True',
            'Setting-Value-2' => 'False',

            'Setting-Value-3' => 'TRUE',
            'Setting-Value-4' => 'FALSE',

            'Setting-Value-5' => 'trUe',
            'Setting-Value-6' => 'faLse',
        ));

        $this->assertSame(true, Dummy::getBool('Setting-Value-1'));
        $this->assertSame(false, Dummy::getBool('Setting-Value-2'));

        $this->assertSame(true, Dummy::getBool('Setting-Value-3'));
        $this->assertSame(false, Dummy::getBool('Setting-Value-4'));

        $this->assertSame(true, Dummy::getBool('Setting-Value-5'));
        $this->assertSame(false, Dummy::getBool('Setting-Value-6'));
    }

    public function testEmptyValue()
    {
        Dummy::setTestData(array(
            'Setting-Value-1' => '',
        ));

        $this->assertSame(false, Dummy::getBool('Setting-Value-1'));
    }

    public function testMiscValue()
    {
        Dummy::setTestData(array(
            'Setting-Value-1' => false,
            'Setting-Value-2' => true,
            'Setting-Value-3' => 3,
            'Setting-Value-4' => 0,
            'Setting-Value-5' => -1,
        ));

        $this->assertSame(false, Dummy::getBool('Setting-Value-1'));
        $this->assertSame(true, Dummy::getBool('Setting-Value-2'));
        $this->assertSame(true, Dummy::getBool('Setting-Value-3'));
        $this->assertSame(false, Dummy::getBool('Setting-Value-4'));
        $this->assertSame(false, Dummy::getBool('Setting-Value-5'));
    }

    public function testInvalidValue()
    {
        Dummy::setTestData(array(
            'Setting-Value-1' => 'PHP actually treat this as false.',
        ));

        $this->assertSame(false, Dummy::getBool('Setting-Value-1'));
    }
}
