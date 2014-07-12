<?php

namespace Facula\Tests\Unit\Input;

use Facula\Unit\Input;

/*
 * Testing the Input unit
 */
class IntegerTest extends \PHPUnit_Framework_TestCase
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
    public function testInputCorrectIntegerFromHttpPost()
    {
        global $_POST;

        $_POST['TestInteger'] = 10;

        $errors = array();

        $input = Input\Input::from(
            Input\Source\HttpPost::create()
        )->fields(
            Input\Field\Integers::bind('TestInteger')
                ->defaults(64)->limits(
                    Input\Limit\Maxmin::create()
                        ->max(64)
                        ->min(1)
                )
        )->errors($errors)->prepare(); // prepare returns a new object: Input\Result

        // The error should be 0
        $this->assertEquals(0, count($errors));

        // The field TestString should be inputed value
        $this->assertEquals(
            10,
            $input->get('TestInteger')->value()
        );

        $this->assertEquals(
            9,
            $input->get('TestInteger')->maxTo(9)
        );
    }

    /*
     * Test the string Input from HTTP POST
     */
    public function testInputIncorrectIntegerFromHttpPost()
    {
        global $_POST;

        $_POST['TestIntegerLarger'] = 100;
        $_POST['TestIntegerLarger2'] = 1;

        $errors = array();

        $input = Input\Input::from(
            Input\Source\HttpPost::create()
        )->fields(
            Input\Field\Integers::bind('TestIntegerLarger')
                ->defaults(64)->limits(
                    Input\Limit\Maxmin::create()
                        ->max(64)
                        ->min(10)
                ),
            Input\Field\Integers::bind('TestIntegerLarger2')
                ->defaults(64)->limits(
                    Input\Limit\Maxmin::create()
                        ->max(64)
                        ->min(10)
                )
        )->errors($errors)->prepare(); // prepare returns a new object: Input\Result

        // The error should be 1
        $this->assertEquals(2, count($errors));

        // The Error 1 should for TestIntegerLarger and it's too large
        $this->assertEquals(
            'INVALID',
            $errors[0]->type()
        );

        $this->assertEquals(
            'TOO_LARGE',
            $errors[0]->code()
        );

        // The Error 2 should for TestIntegerLarger2 and it's too small
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
