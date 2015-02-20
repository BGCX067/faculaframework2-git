<?php

namespace Facula\Tests\Framework\Tool\File\ModuleScanner;

use Facula\Base\Tool\File\ModuleScanner as Target;
use PHPUnit_Framework_TestCase;

class ModuleScannerTest extends PHPUnit_Framework_TestCase
{
    protected $testDir = '';
    protected $expectingScanResult = array();

    protected static function arrayEquals(array &$expecting, array &$compare)
    {
        foreach ($expecting as $key => $val) {
            if (!isset($compare[$key])) {
                return false;
            }

            if ($compare[$key] !== $val) {
                return false;
            }
        }

        return true;
    }

    public function setUp()
    {
        $this->testDir = realpath(dirname(__FILE__));

        $this->expectingScanResult = array(
            'testone' => array(
                'Prefix' => 'class',
                'Name' => 'testone',
                'Ext' => 'php',

                'Path' => $this->testDir
                        . DIRECTORY_SEPARATOR
                        .'Assets'
                        . DIRECTORY_SEPARATOR
                        . 'class.testone.php',

                'Dir' => $this->testDir
                        . DIRECTORY_SEPARATOR
                        .'Assets',
            ),

            'testtwo' => array(
                'Prefix' => 'plugin',
                'Name' => 'testtwo',
                'Ext' => 'php',

                'Path' => $this->testDir
                        . DIRECTORY_SEPARATOR
                        .'Assets'
                        . DIRECTORY_SEPARATOR
                        . 'plugin.testtwo.php',

                'Dir' => $this->testDir
                        . DIRECTORY_SEPARATOR
                        .'Assets',
            ),

            'testthree' => array(
                'Prefix' => '',
                'Name' => 'testthree',
                'Ext' => 'php',

                'Path' => $this->testDir
                        . DIRECTORY_SEPARATOR
                        .'Assets'
                        . DIRECTORY_SEPARATOR
                        . 'testthree.php',

                'Dir' => $this->testDir
                        . DIRECTORY_SEPARATOR
                        .'Assets',
            ),

            'test.four' => array(
                'Prefix' => 'template',
                'Name' => 'test.four',
                'Ext' => 'htm',

                'Path' => $this->testDir
                        . DIRECTORY_SEPARATOR
                        .'Assets'
                        . DIRECTORY_SEPARATOR
                        . 'template.test.four.htm',

                'Dir' => $this->testDir
                        . DIRECTORY_SEPARATOR
                        .'Assets',
            ),
        );
    }

    /**
     * @expectedException Facula\Base\Exception\Tool\File\ModuleScanner\NotDirectory
     */
    public function testNotDir()
    {
        $scanner = new Target($this->testDir . DIRECTORY_SEPARATOR . 'notfound');
    }

    /**
     * @expectedException Facula\Base\Exception\Tool\File\ModuleScanner\NotDirectory
     */
    public function testIsFile()
    {
        $scanner = new Target(
            $this->testDir
            . DIRECTORY_SEPARATOR
            . 'Assets'
            . DIRECTORY_SEPARATOR
            . 'class.testone.php'
        );
    }

    /**
     * @expectedException Facula\Base\Exception\Tool\File\ModuleScanner\EmptyResult
     */
    public function testIsEmpty()
    {
        $scanner = new Target(
            $this->testDir
            . DIRECTORY_SEPARATOR
            . 'Assets'
            . DIRECTORY_SEPARATOR
            . 'Empty'
        );

        $scanner->scan();
    }

    public function testScan()
    {
        $scanner = new Target(
            $this->testDir
            . DIRECTORY_SEPARATOR
            . 'Assets'
        );

        foreach ($scanner->scan() as $result) {
            if (!isset($this->expectingScanResult[$result['Name']])) {
                $this->fail('Incomplete scan result');

                return;
            }

            $this->assertTrue(static::arrayEquals(
                $this->expectingScanResult[$result['Name']],
                $result
            ), 'testing: ' . $result['Name']);
        }
    }
}
