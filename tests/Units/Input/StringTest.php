<?php

namespace Facula\Tests\Unit\Input;

use Facula\Unit\Input;

/*
 * Testing the Input unit
 */
class StringTest extends \PHPUnit_Framework_TestCase
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
    public function testInputCorrectStringFromHttpPost()
    {
        global $_POST;

        $_POST['TestString'] = 'Test';

        $errors = array();

        $input = Input\Input::from(
            Input\Source\HttpPost::import()
        )->fields(
            Input\Field\Strings::bind('TestString')
                ->defaults('Default Test')->limits(
                    Input\Limit\Validate::create()
                        ->format('standard')
                        ->maxlen(64)
                        ->minlen(1)
                ),
            Input\Field\Strings::bind('TestStringNotExisted')
                ->defaults('Default test value for not existed')->limits(
                    Input\Limit\Validate::create()
                        ->format('standard')
                        ->maxlen(64)
                        ->minlen(1)
                )
        )->errors($errors)->prepare(); // prepare returns a new object: Input\Result

        // The error should be 0
        $this->assertEquals(0, count($errors));

        // The field TestString should be inputed value
        $this->assertEquals(
            'Test',
            $input->get('TestString')->value()
        );

        // The field TestStringNotExisted should be default value
        $this->assertEquals(
            'Default test value for not existed',
            $input->get('TestStringNotExisted')->value()
        );

        // Get sub string from the result
        $this->assertEquals(
            'Default',
            $input->get('TestStringNotExisted')->cut(0, 7)
        );
    }

    /*
     * Test the string Input from HTTP POST
     */
    public function testInputIncorrectStringFromHttpPost()
    {
        global $_POST;

        $_POST['TestString'] = '@Test'; // Invalid format
        $_POST['TestNotAStr'] = array('Array here'); // Another invalid format

        $errors = array();
        $exceptionCatched = false;

        $input = Input\Input::from(
            Input\Source\HttpPost::import()
        )->fields(
            Input\Field\Strings::bind('TestString')
                ->defaults('Default Test')->limits(
                    Input\Limit\Validate::create()
                        ->format('alphanumber')
                        ->maxlen(64)
                        ->minlen(1)
                ),
            Input\Field\Strings::bind('TestNotAStr')
                ->defaults('Default Test')->limits(
                    Input\Limit\Validate::create()
                        ->format('standard')
                        ->maxlen(64)
                        ->minlen(1)
                )
        )->errors($errors)->prepare(); // prepare returns a new object: Input\Result

        $this->assertEquals(2, count($errors));

        // Error type should be INVALID
        $this->assertEquals('INVALID', $errors[0]->type());
        $this->assertEquals('INVALID', $errors[1]->type());

        // Error code should be FORMAT
        $this->assertEquals('FORMAT', $errors[0]->code());

        // And this should be DATA_TYPE
        $this->assertEquals('DATATYPE', $errors[1]->code());

        // Error code should be TESTSTRING_FORMAT
        $this->assertEquals('TESTSTRING_FORMAT', $errors[0]->errorCode());

        // And this should be TESTNOTASTR_DATA_TYPE
        $this->assertEquals('TESTNOTASTR_DATATYPE', $errors[1]->errorCode());

        try {
            $input->get('TestString')->value();
        } catch (Input\Base\Exception\Results\FieldNotFound $e) {
            $exceptionCatched = true;
        }

        // An exception should be throw out
        $this->assertTrue($exceptionCatched);
    }
}
