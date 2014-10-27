<?php

namespace Facula\Tests\Framework\Core\Request;

class AcceptType extends AcceptTestBase
{
    /**
     * Test: Accept: TEXT/html
     */
    public function testSingleOne()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT'] = 'TEXT/html';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'text/html' => 1.0,
                ),
                $request->getClientInfo('acceptedTypes')
            )
        );
    }

    /**
     * Test: Accept: Audio/*, text/*, text/html
     */
    public function testMultiWithoutQ()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT'] = 'Audio/*, text/*, text/html';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'audio/*' => 1.0,
                    'text/*' => 1.0,
                    'text/html' => 1.0,
                ),
                $request->getClientInfo('acceptedTypes')
            )
        );
    }

    /**
     * Test: Accept: text/*;q=0.3
     */
    public function testStandardSingle()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT'] = 'text/*;q=0.3';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'text/*' => 0.3,
                ),
                $request->getClientInfo('acceptedTypes')
            )
        );
    }

    /**
     * Test: Accept: text/*;q=0.3, text/html;q=0.7, text/html
     */
    public function testStandardMulti()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT'] = 'text/*;q=0.3, text/html;q=0.7';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'text/html' => 0.7,
                    'text/*' => 0.3,
                ),
                $request->getClientInfo('acceptedTypes')
            )
        );
    }
}
