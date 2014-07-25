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

        $vA = 'ValueA';
        $vB = 'ValueB';
        $vC = 'ValueC';

        $one = 1;
        $two = 2;
        $three = 3;

        // Should be true
        $this->assertTrue($oneOf->qualified($vA, $error));
        $this->assertTrue($oneOf->qualified($vB, $error));
        $this->assertFalse($oneOf->qualified($vC, $error));

        // Error should be CASE_UNKNOWN
        $this->assertEquals('INVALID', $error->type());
        $this->assertEquals('CASEUNKNOWN', $error->code());

        // Another test with non-string data
        $oneOf2 = Input\Limit\Oneof::create()->add(1)->add(2);

        $this->assertTrue($oneOf2->qualified($one, $error));
        $this->assertTrue($oneOf2->qualified($two, $error));
        $this->assertFalse($oneOf2->qualified($three, $error));

        $this->assertEquals('INVALID', $error->type());
        $this->assertEquals('CASEUNKNOWN', $error->code());
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
        $this->assertEquals('CASEUNKNOWN', $errors[0]->code());
    }
}
