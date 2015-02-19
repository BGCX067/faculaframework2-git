<?php

namespace Facula\Tests\Framework\Tool\PHP\Ini;

use PHPUnit_Framework_TestCase;

class ByteValueTest extends PHPUnit_Framework_TestCase
{
    public function testGigaBytes()
    {
        Dummy::setTestData(array(
            'Test-1' => '1g',
            'Test-2' => '1G',
            'Test-3' => '1073741824',

            'Test-4' => '1.8g',
            'Test-5' => '1.8G',
            'Test-6' => '1932735283.2',
        ));

        $this->assertSame(1073741824.0, Dummy::getBytes('Test-1'));
        $this->assertSame(1073741824.0, Dummy::getBytes('Test-2'));
        $this->assertSame(1073741824.0, Dummy::getBytes('Test-3'));

        $this->assertSame(1932735283.2, Dummy::getBytes('Test-4'));
        $this->assertSame(1932735283.2, Dummy::getBytes('Test-5'));
        $this->assertSame(1932735283.2, Dummy::getBytes('Test-6'));
    }

    public function testMegaBytes()
    {
        Dummy::setTestData(array(
            'Test-1' => '1m',
            'Test-2' => '1M',
            'Test-3' => '2.2m',
            'Test-4' => '2.3M',
        ));

        $this->assertSame(1048576.0, Dummy::getBytes('Test-1'));
        $this->assertSame(1048576.0, Dummy::getBytes('Test-2'));
        $this->assertSame(2306867.2, Dummy::getBytes('Test-3'));
        $this->assertSame(2411724.8, Dummy::getBytes('Test-4'));
    }

    public function testKiloBytes()
    {
        Dummy::setTestData(array(
            'Test-1' => '1k',
            'Test-2' => '1K',
            'Test-3' => '55k',
            'Test-4' => '5.5k',
        ));

        $this->assertSame(1024.0, Dummy::getBytes('Test-1'));
        $this->assertSame(1024.0, Dummy::getBytes('Test-2'));
        $this->assertSame(56320.0, Dummy::getBytes('Test-3'));
        $this->assertSame(5632.0, Dummy::getBytes('Test-4'));
    }

    /**
     * @expectedException Facula\Base\Exception\Tool\PHP\Ini\InvalidBytesString
     */
    public function testInvalidString()
    {
        Dummy::setTestData(array(
            'Test-1' => 'not101abytes',
        ));

        Dummy::getBytes('Test-1');
    }

    /**
     * @expectedException Facula\Base\Exception\Tool\PHP\Ini\InvalidBytesUnit
     */
    public function testInvalidUnit()
    {
        Dummy::setTestData(array(
            'Test-2' => '10000x',
        ));

        Dummy::getBytes('Test-2');
    }
}
