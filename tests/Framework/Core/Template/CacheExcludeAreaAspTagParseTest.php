<?php

namespace Facula\Tests\Framework\Core\Template;

use Facula\Tests\Framework\Core\Template\Dummy;
use Facula\Base\Tool\PHP\Ini;
use PHPUnit_Framework_TestCase;

/**
 * Notice this is a middle way test.
 *
 * This test case will not indicate how this method should be used.
 * Do not follow this test case to use the method.
 */
class CacheExcludeAreaAspTagParseTest extends PHPUnit_Framework_TestCase
{
    protected static $template = '
    1<% echo(\'CACHEABLE_HEAD\'); %>2
    3<!-- NOCACHE --><% echo(\'NONCACHEABLE_AREA\'); %><!-- /NOCACHE -->4
    5<% echo(\'CACHEABLE_TAIL\'); %>6';

    protected static $testParameters = array(
        array(
            Dummy::CACHE_EXCLUDE_HANDLE_TYPE_MAKE,
            false,
            '
    1<% echo(\'CACHEABLE_HEAD\'); %>2
    3<?php echo(stripslashes(\'<!-- NOCACHE --><% echo(\\\'NONCACHEABLE_AREA\\\'); %><!-- /NOCACHE -->\')); ?>4
    5<% echo(\'CACHEABLE_TAIL\'); %>6'
        ),
        array(
            Dummy::CACHE_EXCLUDE_HANDLE_TYPE_MAKE,
            true,
            '
    1<% echo(\'CACHEABLE_HEAD\'); %>2
    3<?php echo(stripslashes(\'<% echo(\\\'NONCACHEABLE_AREA\\\'); %>\')); ?>4
    5<% echo(\'CACHEABLE_TAIL\'); %>6'
        ),

        // Test for SECURE type.
        // Notice you could only use SECURE handle type on a MAKE'd template string
        // Do not doing anything like this test case data
        array(
            Dummy::CACHE_EXCLUDE_HANDLE_TYPE_SECURE,
            false,
            '
    1&lt;% echo(\'CACHEABLE_HEAD\'); %&gt;2
    3<!-- NOCACHE --><% echo(\'NONCACHEABLE_AREA\'); %><!-- /NOCACHE -->4
    5&lt;% echo(\'CACHEABLE_TAIL\'); %&gt;6'
        ),
        array(
            Dummy::CACHE_EXCLUDE_HANDLE_TYPE_SECURE,
            true,
            '
    1&lt;% echo(\'CACHEABLE_HEAD\'); %&gt;2
    3<% echo(\'NONCACHEABLE_AREA\'); %>4
    5&lt;% echo(\'CACHEABLE_TAIL\'); %&gt;6'
        ),
    );

    protected static function getDummy()
    {
        return new Dummy();
    }

    /**
     * Run all test case of in this test set
     */
    public function testAllCasese()
    {
        // Skip this test of asp_tags is disabled.
        if (!Ini::getBool('asp_tags'))
        {
            $this->assertTrue(true);

            return;
        }

        foreach (static::$testParameters as $parameter)
        {
            $this->assertEquals(
                $parameter[2],
                static::getDummy()->handleCacheExcludeAreaDelegate(
                    static::$template,
                    $parameter[0],
                    $parameter[1]
                )
            );
        }
    }
}
