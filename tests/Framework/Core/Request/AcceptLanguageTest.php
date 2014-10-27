<?php

namespace Facula\Tests\Framework\Core\Request;

class AcceptLanguageTest extends AcceptTestBase
{
    /**
     * Test: Accept-Language: da
     */
    public function testSingleOne()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'da';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'da' => 1.0, // Notice the uppercase
                ),
                $request->getClientInfo('acceptedLanguages')
            )
        );
    }

    /**
     * Test: Accept-Language: DA, EN-GB, EN
     */
    public function testMultiWithoutQ()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'DA, EN-GB, EN';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'da' => 1.0,
                    'en-gb' => 1.0,
                    'en' => 1.0,
                ),
                $request->getClientInfo('acceptedLanguages')
            )
        );
    }

    /**
     * Test: Accept-Language: en;q=0.7
     */
    public function testStandardSingle()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en;q=0.7';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'en' => 0.7,
                ),
                $request->getClientInfo('acceptedLanguages')
            )
        );
    }

    /**
     * Test: Accept-Language: da, en-gb;q=0.8, en;q=0.7
     */
    public function testStandardMulti()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'da, en-gb;q=0.8, en;q=0.7';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'da' => 0.8,
                    'en-gb' => 0.8,
                    'en' => 0.7,
                ),
                $request->getClientInfo('acceptedLanguages')
            )
        );
    }

    /**
     * Test: Accept-Language: en;q=0.777777777777777777777777777777777777
     *
     * PHP should handle it well. Keep this test for alert of the future changes.
     */
    public function testHugeQ()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en;q=0.777777777777777777777777777777777777';

        $request = $this->getTestInstance();

        $this->assertTrue(
            $this->checkResultArray(
                array(
                    'en' => 0.777777777777777777777777777777777777,
                ),
                $request->getClientInfo('acceptedLanguages')
            )
        );
    }

    /**
     * Test, Large Q, high Priority
     */
    public function testLangaugePriority()
    {
        global $_SERVER;

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'lang1;q=0.2,lang2;q=0.3;lang3;q  = 0;lang4';

        $request = $this->getTestInstance();

        $this->assertTrue(
            array(
                'lang4',
                'lang2',
                'lang1',
            ) === $request->getClientInfo('languages') ? true : false
        );
    }
}
