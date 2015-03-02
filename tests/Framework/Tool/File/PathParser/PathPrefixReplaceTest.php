<?php

namespace Facula\Tests\Framework\Tool\File\PathParser;

use Facula\Base\Tool\File\PathParser as Target;
use PHPUnit_Framework_TestCase;

class PathPrefixReplaceTest extends PHPUnit_Framework_TestCase
{
    public function testNormalPath()
    {
        $this->assertSame(
            '[PROJECT]\\Some\\Sub\\Path',
            Target::replacePathPrefix(
                '\\var\\www\\facula',
                '[PROJECT]',
                '\\var\\www\\facula\\Some\\Sub\\Path'
            )
        );
    }
    
    public function testSameStringInPath()
    {
        $this->assertSame(
            '[PROJECT]\\var\\www\\facula\\Some\\Sub\\Path',
            Target::replacePathPrefix(
                '\\var\\www\\facula',
                '[PROJECT]',
                '\\var\\www\\facula\\var\\www\\facula\\Some\\Sub\\Path'
            )
        );
    }
}
