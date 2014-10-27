<?php

namespace Facula\Tests\Framework\Core\Request;

class AcceptCharsetTest extends AcceptTestBase
{
    /**
     * Test: Accept-Charset: utf-8
     *
     * Notice that we test all case in extreme condition,
     * in this case, we use all lowercase
     */
    public function testSingleOne()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_CHARSET'] = 'utf-8';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'UTF-8' => 1.0, // Notice the uppercase
                ),
                $request->getClientInfo('acceptedCharsets')
            )
        );
    }

    /**
     * Test: Accept-Charset: utf-8, gbk, windows-blablabla
     */
    public function testMultiWithoutQ()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_CHARSET'] = 'utf-8, gbk, windows-blablabla';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'UTF-8' => 1.0,
                    'GBK' => 1.0,
                    'WINDOWS-BLABLABLA' => 1.0,
                ),
                $request->getClientInfo('acceptedCharsets')
            )
        );
    }

    /**
     * Test: Accept-Charset: utf-8; q=1
     *
     * Notice that, we don't actually go with q number
     * It will be simply ignore whatever the value is
     */
    public function testStandardSingle()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_CHARSET'] = 'utf-8; q=1';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'UTF-8' => 1.0,
                ),
                $request->getClientInfo('acceptedCharsets')
            )
        );
    }

    /**
     * Test: Accept-Charset: windows-1252,utf-8;q=0.7,*;q=0.3
     */
    public function testStandardMulti()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_CHARSET'] = 'windows-1252,utf-8;q=0.7,*;q=0.3';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'WINDOWS-1252' => 0.7,
                    'UTF-8' => 0.7,
                    '*' => 0.3,
                ),
                $request->getClientInfo('acceptedCharsets')
            )
        );
    }

    /*
    // Following Test just for showing How it will handle invalid value
    // Pass it is no needed (However it passed anyway :D)

    // Test: Accept-Charset: ;;;a;;;;;;
    // Attackers may change the Accept-Charset and inject invalid
    // format, make sure it not break for that.

    public function testBadOne()
    {
        global $_SERVER;

        // It will not be parsed as we use ',' to split first, then ignore anything
        // after ';'
        $_SERVER['HTTP_ACCEPT_CHARSET'] = ';;;a;;;;;;';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'A' => 1.0
                ),
                $request->getClientInfo('acceptedCharsets')
            )
        );
    }

    // Test: Accept-Charset: ,Code1,Code-2,Code 3
    public function testBadTwo()
    {
        global $_SERVER;

        // There may nothing in the first and last parameter.
        // They must be ignored.
        // And for the rest charset name, get them AS IS and upper it
        $_SERVER['HTTP_ACCEPT_CHARSET'] = ',Code1,Code-2,Code 3,';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'CODE1' => 1.0,
                    'CODE-2' => 1.0,
                    'CODE 3' => 1.0,
                ),
                $request->getClientInfo('acceptedCharsets')
            )
        );
    }

    // Test: Accept-Charset: ,GB2312,,0;q=;;utf-,select,
    public function testBadThree()
    {
        // More complex one

        // 1, After ',' explode, we got:
        //    array('', 'GB2312', '', '0;q=;;utf-', 'select', '')

        // 2, Everything after ; will be ignored, so we got:
        //    array('', 'GB2312', '', '0', 'select', '')

        $_SERVER['HTTP_ACCEPT_CHARSET'] = ',GB2312,,0;q=;;utf-,select,';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'GB2312' => 1.0,
                    '0' => 1.0,
                    'SELECT' => 1.0,
                ),
                $request->getClientInfo('acceptedCharsets')
            )
        );
    }

    */
}
