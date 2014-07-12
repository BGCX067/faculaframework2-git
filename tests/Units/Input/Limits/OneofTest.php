<?php

namespace Facula\Tests\Unit\Input;

use Facula\Unit\Input;

/*
 * Testing the Input limit unit Oneof
 */
class OneofTest extends \PHPUnit_Framework_TestCase
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
     * Test the Limit
     */
    public function testOneof()
    {
        // Create a limit that only allows ValueA and ValueB
        $oneOf = Input\Limit\Oneof::create()->add('ValueA')->add('ValueB');

        $error = null;

        // Should be true
        $this->assertTrue($oneOf->qualified('ValueA', $error));
        $this->assertTrue($oneOf->qualified('ValueB', $error));
        $this->assertFalse($oneOf->qualified('ValueC', $error));

        // Error should be CASE_UNKNOWN
        $this->assertEquals('INVALID', $error->type());
        $this->assertEquals('CASE_UNKNOWN', $error->code());

        // Another test with non-string data
        $oneOf2 = Input\Limit\Oneof::create()->add(1)->add(2);

        $this->assertTrue($oneOf2->qualified(1, $error));
        $this->assertTrue($oneOf2->qualified(2, $error));
        $this->assertFalse($oneOf2->qualified(3, $error));

        $this->assertEquals('INVALID', $error->type());
        $this->assertEquals('CASE_UNKNOWN', $error->code());
    }

    /*
     * Test the Limit with input
     */
    public function testWithInput()
    {
        global $_POST;

        $_POST['testOneof'] = 'ValueA';
        $_POST['testOneof2'] = 'ValueB';

        $errors = array();

        $input = Input\Input::from(
            Input\Source\HttpPost::import()
        )->fields(
            Input\Field\Strings::bind('testOneof')->limits(
                Input\Limit\Oneof::create()->add('ValueA')
            )->limits(
                Input\Limit\Oneof::create()->add('ValueC')
            ),
            Input\Field\Strings::bind('testOneof2')->limits(
                Input\Limit\Oneof::create()->add('ValueA')
            )->limits(
                Input\Limit\Oneof::create()->add('ValueC')
            )->defaults('ValueA')
        )->errors($errors)->prepare();

        // There should be one error
        $this->assertEquals(1, count($errors));

        // Error should be CASE_UNKNOWN
        $this->assertEquals('INVALID', $errors[0]->type());
        $this->assertEquals('CASE_UNKNOWN', $errors[0]->code());
    }
}
