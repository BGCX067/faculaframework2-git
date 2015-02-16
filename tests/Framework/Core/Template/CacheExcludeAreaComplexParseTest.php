<?php

namespace Facula\Tests\Framework\Core\Template;

use Facula\Tests\Framework\Core\Template\Dummy;
use PHPUnit_Framework_TestCase;

/**
 * Notice this is a middle way test.
 *
 * This test case will not indicate how this method should be used.
 * Do not follow this test case to use the method.
 */
class CacheExcludeAreaComplexParseTest extends PHPUnit_Framework_TestCase
{
    protected static $template = '
    1<?php echo(\'CACHEABLE_HEAD\'); ?>2
    3<!-- NOCACHE --><?php echo(\'NONCACHEABLE_AREA\'); ?><!-- /NOCACHE -->4
    5<?php echo(\'CACHEABLE_TAIL\'); ?>6';

    protected static $result = '
    1&lt;?php echo(\'CACHEABLE_HEAD\'); ?&gt;2
    3&lt;?php echo(stripslashes(\'<?php echo(\\\'NONCACHEABLE_AREA\\\'); ?>\')); ?&gt;4
    5&lt;?php echo(\'CACHEABLE_TAIL\'); ?&gt;6';

    protected static function getDummy()
    {
        $dummy = new Dummy();

        $dummy->setConfig('AspTags', false);

        return $dummy;
    }

    /**
     * Test Complex Parse: MAKE and then SECURE
     */
    public function testParse()
    {
        $dummy = static::getDummy();
        $maked = $dummy->handleCacheExcludeAreaDelegate(
            static::$template,
            Dummy::CACHE_EXCLUDE_HANDLE_TYPE_MAKE,
            false
        );

        $this->assertEquals(
            static::$result,
            $dummy->handleCacheExcludeAreaDelegate(
                $maked,
                Dummy::CACHE_EXCLUDE_HANDLE_TYPE_SECURE,
                true
            )
        );
    }
}
