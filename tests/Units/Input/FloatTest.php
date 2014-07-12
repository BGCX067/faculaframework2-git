<?php

namespace Facula\Tests\Unit\Input;

use Facula\Unit\Input;

/*
 * Testing the Input unit
 */
class FloatTest extends \PHPUnit_Framework_TestCase
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
    public function testInputCorrectFloatFromHttpPost()
    {
        global $_POST;

        $_POST['TestFloat'] = 1000.00;

        $errors = array();

        $input = Input\Input::from(
            Input\Source\HttpPost::import()
        )->fields(
            Input\Field\Floats::bind('TestFloat')
                ->defaults(64)->limits(
                    Input\Limit\Maxmin::create()
                        ->max(1000)
                        ->min(1)
                )
        )->errors($errors)->prepare(); // prepare returns a new object: Input\Result

        // The error should be 0
        $this->assertEquals(0, count($errors));

        // The field TestFloat should be inputed value
        $this->assertEquals(
            1000.00,
            $input->get('TestFloat')->value()
        );

        $this->assertEquals(
            9.00,
            $input->get('TestFloat')->maxTo(9)
        );

        $this->assertEquals(
            '9+',
            $input->get('TestFloat')->max(9)
        );

        $this->assertEquals(
            '1,000.00',
            $input->get('TestFloat')->notation(9)
        );
    }

    /*
     * Test the string Input from HTTP POST
     */
    public function testInputIncorrectFloatFromHttpPost()
    {
        global $_POST;

        $_POST['TestFloatLarger'] = 100.00;
        $_POST['TestFloatLarger2'] = 1.00;

        $errors = array();

        $input = Input\Input::from(
            Input\Source\HttpPost::import()
        )->fields(
            Input\Field\Integers::bind('TestFloatLarger')
                ->defaults(64)->limits(
                    Input\Limit\Maxmin::create()
                        ->max(64)
                        ->min(10)
                ),
            Input\Field\Integers::bind('TestFloatLarger2')
                ->defaults(64)->limits(
                    Input\Limit\Maxmin::create()
                        ->max(64)
                        ->min(10)
                )
        )->errors($errors)->prepare(); // prepare returns a new object: Input\Result

        // The error should be 1
        $this->assertEquals(2, count($errors));

        // The Error 1 should for TestFloatLarger and it's too large
        $this->assertEquals(
            'INVALID',
            $errors[0]->type()
        );

        $this->assertEquals(
            'TOO_LARGE',
            $errors[0]->code()
        );

        // The Error 2 should for TestFloatLarger2 and it's too small
        $this->assertEquals(
            'INVALID',
            $errors[1]->type()
        );

        $this->assertEquals(
            'TOO_SMALL',
            $errors[1]->code()
        );
    }
}
