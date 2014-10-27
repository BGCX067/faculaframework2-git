<?php

namespace Facula\Tests\Framework\Core;

use Facula\Framework;
use PHPUnit_Framework_TestCase;

/*
 * Testing the namespace functions
 */
class NamespaceLoadTest extends PHPUnit_Framework_TestCase
{
    private static $testingDir = '';
    private static $testClassFileContent = array(
        '<?php
            namespace Facula\Temp;

            class Demo {

            }
        ',
        '<?php
            namespace Facula\Temp;

            class Demo2 {

            }
        ',
        '<?php
            namespace Facula\Temp;

            class Demo3 {

            }
        ',
    );

    /*
     * Build up a namespace testing environment
     */
    protected function setUp()
    {
        // Set path to the temp file
        static::$testingDir = __DIR__
            . DIRECTORY_SEPARATOR
            . 'Test';

        // Build the dir
        mkdir(
            static::$testingDir,
            0777,
            true
        );

        // Put class files into the dir
        file_put_contents(
            static::$testingDir
            . DIRECTORY_SEPARATOR
            . 'Demo.php',
            static::$testClassFileContent[0]
        );

        file_put_contents(
            static::$testingDir
            . DIRECTORY_SEPARATOR
            . 'Demo2.php',
            static::$testClassFileContent[1]
        );

        file_put_contents(
            static::$testingDir
            . DIRECTORY_SEPARATOR
            . 'Demo3.php',
            static::$testClassFileContent[2]
        );
    }

    /*
     * Remove testing environment
     */
    protected function tearDown()
    {
        // Remove class files
        unlink(
            static::$testingDir
            . DIRECTORY_SEPARATOR
            . 'Demo.php'
        );

        unlink(
            static::$testingDir
            . DIRECTORY_SEPARATOR
            . 'Demo2.php'
        );

        unlink(
            static::$testingDir
            . DIRECTORY_SEPARATOR
            . 'Demo3.php'
        );


        // Remove Dir
        rmdir(static::$testingDir);

        return true;
    }

    /*
     * Register a namespace, require the file,
     * and then, unregister the namespace before framework init
     */
    public function testRegisterNamespaceBeforeInit()
    {
        // Register the namespace before facula init
        $this->assertTrue(
            Framework::registerNamespace(
                'Facula\Temp',
                static::$testingDir
            )
        );

        // Init facula
        Framework::run();

        // Check if class is there, it should be
        $this->assertTrue(
            class_exists('Facula\Temp\Demo', true)
        );

        // Unregister the class
        $this->assertTrue(
            Framework::unregisterNamespace(
                'Facula\Temp',
                static::$testingDir
            )
        );

        // Check if the namespace still there, it shouldn't
        $this->assertFalse(
            class_exists('Facula\Temp\Demo2', true)
        );

        return true;
    }

    /*
     * Register a namespace, require the file,
     * and then, unregister the namespace after framework init
     */
    public function testRegisterNamespace()
    {
        // Init the framework if it not yet inited
        Framework::run();

        // Register a wrong namespace
        $this->assertTrue(
            Framework::registerNamespace(
                'Facula\Temp\SubNamespace',
                static::$testingDir
            )
        );

        // Check if the class exist, it should not
        $this->assertFalse(
            class_exists('Facula\Temp\SubNamespace\Demo2', true)
        );

        // Check if the file has been loaded,
        // Auto loader will load it, so it should be
        // If the file loaded, the class should be registered
        $this->assertTrue(
            class_exists('Facula\Temp\Demo2', true)
        );

        // Unregister this namespace
        $this->assertTrue(
            \Facula\Framework::unregisterNamespace(
                'Facula\Temp\SubNamespace',
                static::$testingDir
            )
        );

        // Check if the third class exists, it should not.
        $this->assertFalse(
            class_exists('Facula\Temp\SubNamespace\Demo3', true)
        );

        // Check if the Demo3 class file has loaded, it should not.
        $this->assertFalse(
            class_exists('Facula\Temp\Demo3', true)
        );

        // Register the correct namespace
        $this->assertTrue(
            Framework::registerNamespace(
                'Facula\Temp',
                static::$testingDir
            )
        );

        // Check if the class exist, it should be
        $this->assertTrue(
            class_exists('Facula\Temp\Demo3', true)
        );
    }
}
