<?php

namespace Facula\Tests\Framework\Core\Request;

class AcceptEncodingTest extends AcceptTestBase
{
    /**
     * Test: Accept-Encoding: gzip
     */
    public function testSingleOne()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'gzip' => 1.0,
                ),
                $request->getClientInfo('acceptedEncodings')
            )
        );
    }

    /**
     * Test: Accept-Charset: compress, gzip
     */
    public function testMultiWithoutQ()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'compress, gzip';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'compress' => 1.0,
                    'gzip' => 1.0,
                ),
                $request->getClientInfo('acceptedEncodings')
            )
        );
    }

    /**
     * Test: Accept-Charset: compress;q=0.5
     */
    public function testStandardSingle()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'compress;q=0.5';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'compress' => 0.5,
                ),
                $request->getClientInfo('acceptedEncodings')
            )
        );
    }

    /**
     * Test: Accept-Charset: gzip;q=1.0, identity; q=0.5, *;q=0
     */
    public function testStandardMulti()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip;q=1.0, identity; q=0.5, *;q=0';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'gzip' => 1.0,
                    'identity' => 0.5,
                ),
                $request->getClientInfo('acceptedEncodings')
            )
        );
    }
}
