<?php

namespace Facula\Tests\Framework\Core;

use Facula\Unit\Paging\Compiler\Parameters\Operator\ValueVariable as TestingTarget;

/*
 * Testing the ValueVariable functions
 */
class ValueVariableTest extends \PHPUnit_Framework_TestCase
{
    /*
     * normal variable
     */
    public function testVariableNameConvert()
    {
        $testName = 'testName';

        $newVal = new TestingTarget($testName);

        $this->assertEquals($newVal->result(), '$testName');
    }

    /*
     * array variable with escape
     */
    public function testArrayEscapeConvert()
    {
        $testArrayName = 'testName.SomeSubArray.some\.thing';

        $newVal = new TestingTarget($testArrayName);

        $this->assertEquals(
            $newVal->result(),
            '$testName[\'SomeSubArray\'][\'some.thing\']'
        );
    }

    /*
     * nested array
     */
    public function testNestedConvert()
    {
        $testArrayNested = 'testName.SomeSubArray.(another.array).val';

        $newVal = new TestingTarget($testArrayNested);

        $this->assertEquals(
            $newVal->result(),
            '$testName[\'SomeSubArray\'][$another[\'array\']][\'val\']'
        );
    }

    /*
     * multi nested arrays
     */
    public function testMulitNestedConvert()
    {
        $testArrayMultiNested = 'testName.SomeSubArray.(another.(var)).val';

        $newVal = new TestingTarget($testArrayMultiNested);

        $this->assertEquals(
            $newVal->result(),
            '$testName[\'SomeSubArray\'][$another[$var]][\'val\']'
        );
    }

    /*
     * multi nested arrays with empty item
     */
    public function testMulitNestedWithEmptyItemConvert()
    {
        $testArrayMultiNested = 'testName.SomeSubArray...(another.(var)).va\'l';

        $newVal = new TestingTarget($testArrayMultiNested);

        $this->assertEquals(
            $newVal->result(),
            '$testName[\'SomeSubArray\'][\'\'][\'\'][$another[$var]][\'va\\\'l\']'
        );
    }

    /*
     * multi nested arrays with empty item
     */
    public function testMulitNestedWithDollerAndEmptyItemConvert()
    {
        $testArrayMultiNested = 'testName.$SomeSub\'Array...(another.(var)).va\'l';

        $newVal = new TestingTarget($testArrayMultiNested);

        $this->assertEquals(
            $newVal->result(),
            '$testName[\'$SomeSub\\\'Array\'][\'\'][\'\'][$another[$var]][\'va\\\'l\']'
        );
    }
}
