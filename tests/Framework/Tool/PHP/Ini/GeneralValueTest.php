<?php

namespace Facula\Tests\Framework\Tool\PHP\Ini;

use PHPUnit_Framework_TestCase;

class GeneralValueTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Dummy::setTestData(array(
            // getStr: Notice all setting value is actually a string
            'Test-1' => 'string value',
            'Test-2' => '0',
            'Test-3' => '0.0',

            // getInt
            'Test-4' => '12',
            'Test-5' => '-12',

            // getFloat
            'Test-6' => '32.1',
            'Test-7' => '-32.22',
        ));
    }

    public function testString()
    {
        $this->assertSame('string value', Dummy::getStr('Test-1'));
        $this->assertSame('0', Dummy::getStr('Test-2'));
        $this->assertSame('0.0', Dummy::getStr('Test-3'));
    }

    public function testInteger()
    {
        $this->assertSame(12, Dummy::getInt('Test-4'));
        $this->assertSame(-12, Dummy::getInt('Test-5'));
    }

    public function testFloat()
    {
        $this->assertSame(32.1, Dummy::getFloat('Test-6'));
        $this->assertSame(-32.22, Dummy::getFloat('Test-7'));
    }
}
