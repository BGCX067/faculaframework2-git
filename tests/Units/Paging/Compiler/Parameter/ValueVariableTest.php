<?php

namespace Facula\Tests\Framework\Core;

use Facula\Unit\Paging\Compiler\Parameters\Operator\ValueVariable as TestingTarget;
use PHPUnit_Framework_TestCase;

/*
 * Testing the ValueVariable functions
 */
class ValueVariableTest extends PHPUnit_Framework_TestCase
{
    /*
     * normal variable
     */
    public function testVariableNameConvert()
    {
        $testName = 'testName';

        $newVal = new TestingTarget($testName);

        $this->assertEquals('$testName', $newVal->result());
    }

    /*
     * array variable with escape
     */
    public function testArrayEscapeConvert()
    {
        $testArrayName = 'testName.SomeSubArray.some\.thing';

        $newVal = new TestingTarget($testArrayName);

        $this->assertEquals(
            '$testName[\'SomeSubArray\'][\'some.thing\']',
            $newVal->result()
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
            '$testName[\'SomeSubArray\'][$another[\'array\']][\'val\']',
            $newVal->result()
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
            '$testName[\'SomeSubArray\'][$another[$var]][\'val\']',
            $newVal->result()
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
            '$testName[\'SomeSubArray\'][\'\'][\'\'][$another[$var]][\'va\\\'l\']',
            $newVal->result()
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
            '$testName[\'$SomeSub\\\'Array\'][\'\'][\'\'][$another[$var]][\'va\\\'l\']',
            $newVal->result()
        );
    }
}
