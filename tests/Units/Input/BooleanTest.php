<?php

namespace Facula\Tests\Unit\Input;

use Facula\Unit\Input;

/*
 * Testing the Input unit
 */
class BooleanTest extends \PHPUnit_Framework_TestCase
{
    /*
     * Manually set up some value for test environment
     */
    protected function setUp()
    {
        return true;
    }

    /*
     * Remove testing environment
     */
    protected function tearDown()
    {
        return true;
    }

    /*
     * Test the string Input from HTTP POST
     */
    public function testInputCorrectBooleanFromHttpPost()
    {
        global $_POST;

        $_POST['TestBooleans'] = 'hasSet';

        $errors = array();

        $input = Input\Input::from(
            Input\Source\HttpPost::import()
        )->fields(
            Input\Field\Booleans::bind('TestBooleans')->defaults(true),
            Input\Field\Booleans::bind('TestBooleansNotExisted')->defaults(false)
        )->errors($errors)->prepare(); // prepare returns a new object: Input\Result

        // The error should be 0
        $this->assertEquals(0, count($errors));

        // The field TestBooleans should be true
        $this->assertEquals(
            true,
            $input->get('TestBooleans')->value()
        );

        // The field TestBooleansNotExisted should be false
        $this->assertEquals(
            false,
            $input->get('TestBooleansNotExisted')->value()
        );
    }
}
