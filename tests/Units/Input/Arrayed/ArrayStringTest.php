<?php

namespace Facula\Tests\Unit\Input;

use Facula\Unit\Input;

/*
 * Testing the Input unit
 */
class ArrayStringTest extends \PHPUnit_Framework_TestCase
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
     * Test the string array Input from HTTP POST
     */
    public function testInputArrayedString()
    {
        global $_POST;

        $_POST['TestStringArray'] = array(
            'validUserName',
            'validUserName2'
        );

        $errors = array();

        $input = Input\Input::from(
            Input\Source\HttpPost::import()
        )->fields(
            Input\Field\Each\Strings::bind('TestStringArray')->limits(
                Input\Limit\Eachs::create()->limits(
                    Input\Limit\Validate::create()
                        ->format('standard')
                        ->maxlen(64)
                        ->minlen(1)
                )
            )
        )->errors($errors)->prepare();

        // The error should be 0
        $this->assertEquals(0, count($errors));

        // The field TestStringArray 0 should be inputed value
        $this->assertEquals(
            'validUserName',
            $input->get('TestStringArray')->get(0)->value()
        );

        // The field TestStringArray 0 should be inputed value
        $this->assertEquals(
            'validUserName2',
            $input->get('TestStringArray')->get(1)->value()
        );
    }

    /*
     * Test the string array Input from HTTP POST
     */
    public function testInputArrayedString2()
    {
        global $_POST;

        $_POST['TestStringArray3']['UserName'] = 'AAA';
        $_POST['TestStringArray3']['Password'] = 'BBB';

        $_POST['TestStringArray2'] = array(
            array(
                'UserName' => 'Username1',
                'Password' => '1234567890',
            ),
            array(
                'UserName' => 'Username2',
                'Password' => '1234567891',
            ),
        );

        $errors = array();

        $input = Input\Input::from(
            Input\Source\HttpPost::import()
        )->fields(
            Input\Field\Subs::bind('TestStringArray3')->subs(
                Input\Field\Strings::bind('UserName')->required(true)->limits(
                    Input\Limit\Validate::create()->format('username')
                ),
                Input\Field\Strings::bind('Password')
            ),
            Input\Field\Groups::bind('TestStringArray2')->adds(
                Input\Field\Strings::bind('UserName'),
                Input\Field\Strings::bind('Password')
            )
        )->errors($errors)->prepare();

        // The error should be 0
        $this->assertEquals(0, count($errors));

        $this->assertEquals(
            'AAA',
            $input->get('TestStringArray3')->get('UserName')->value()
        );

        $this->assertEquals(
            'BBB',
            $input->get('TestStringArray3')->get('Password')->value()
        );


        $user = array();

        // Select the first group
        $user = $input->get('TestStringArray2')->get(0);

        $this->assertEquals(
            'Username1',
            $user['UserName']->value()
        );

        $this->assertEquals(
            '1234567890',
            $user['Password']->value()
        );

        // Select the second group
        $user = $input->get('TestStringArray2')->get(1);

        $this->assertEquals(
            'Username2',
            $user['UserName']->value()
        );

        $this->assertEquals(
            '1234567891',
            $user['Password']->value()
        );
    }

    /*
     * Test the invalid string array Input from HTTP POST
     */
    public function testInputArrayedStringButInvalid2()
    {
        global $_POST;

        $_POST['TestStringArray3']['UserName'] = '!AAA';
        $_POST['TestStringArray3']['Password'] = '!BBB';

        $_POST['TestStringArray2'] = array(
            array(
                'UserName' => '!Username1',
                'Password' => '!1234567890',
            ),
            array(
                'UserName' => '!Username2',
                'Password' => '!1234567891',
            ),
        );

        $errors = array();

        $input = Input\Input::from(
            Input\Source\HttpPost::import()
        )->fields(
            Input\Field\Subs::bind('TestStringArray3')->subs(
                Input\Field\Strings::bind('UserName')->required(true)->limits(
                    Input\Limit\Validate::create()->format('username')
                ),
                Input\Field\Strings::bind('Password')->required(true)->limits(
                    Input\Limit\Validate::create()->format('username')
                )
            ),
            Input\Field\Groups::bind('TestStringArray2')->adds(
                Input\Field\Strings::bind('UserName')->limits(
                    Input\Limit\Validate::create()->format('username')
                ),
                Input\Field\Strings::bind('Password')
            )
        )->errors($errors)->prepare();

        // The error should be 0
        $this->assertEquals(2, count($errors));

        // Error on TestStringArray3
        $this->assertEquals(
            'INVALID',
            $errors[0]->type()
        );

        $this->assertEquals(
            'FORMAT',
            $errors[0]->code()
        );

        // Error on TestStringArray2
        $this->assertEquals(
            'INVALID',
            $errors[1]->type()
        );

        $this->assertEquals(
            'FORMAT',
            $errors[1]->code()
        );
    }
}
