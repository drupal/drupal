<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test\Factory\Resource;

use Assetic\Factory\Resource\FileResource;

class FileResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testIsFresh()
    {
        $resource = new FileResource(__FILE__);
        $this->assertTrue($resource->isFresh(time() + 5));
        $this->assertFalse($resource->isFresh(0));
    }

    public function testGetContent()
    {
        $resource = new FileResource(__FILE__);
        $this->assertEquals(file_get_contents(__FILE__), $resource->getContent());
    }

    public function testIsFreshOnInvalidPath()
    {
        $resource = new FileResource(__FILE__.'foo');
        $this->assertFalse($resource->isFresh(time()), '->isFresh() returns false if the file does not exist');
    }

    public function testGetContentOnInvalidPath()
    {
        $resource = new FileResource(__FILE__.'foo');
        $this->assertSame('', $resource->getContent(), '->getContent() returns an empty string when path is invalid');
    }
}
