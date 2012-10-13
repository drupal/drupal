<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test\Asset;

use Assetic\Asset\FileAsset;

class FileAssetTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $asset = new FileAsset(__FILE__);
        $this->assertInstanceOf('Assetic\\Asset\\AssetInterface', $asset, 'Asset implements AssetInterface');
    }

    public function testLazyLoading()
    {
        $asset = new FileAsset(__FILE__);
        $this->assertEmpty($asset->getContent(), 'The asset content is empty before load');

        $asset->load();
        $this->assertNotEmpty($asset->getContent(), 'The asset content is not empty after load');
    }

    public function testGetLastModifiedType()
    {
        $asset = new FileAsset(__FILE__);
        $this->assertInternalType('integer', $asset->getLastModified(), '->getLastModified() returns an integer');
    }

    public function testGetLastModifiedTypeFileNotFound()
    {
        $asset = new FileAsset(__DIR__ . "/foo/bar/baz.css");

        $this->setExpectedException("RuntimeException", "The source file");
        $asset->getLastModified();
    }

    public function testGetLastModifiedValue()
    {
        $asset = new FileAsset(__FILE__);
        $this->assertLessThan(time(), $asset->getLastModified(), '->getLastModified() returns the mtime');
    }

    public function testDefaultBaseAndPath()
    {
        $asset = new FileAsset(__FILE__);
        $this->assertEquals(__DIR__, $asset->getSourceRoot(), '->__construct() defaults base to the asset directory');
        $this->assertEquals(basename(__FILE__), $asset->getSourcePath(), '->__construct() defaults path to the asset basename');
    }

    public function testPathGuessing()
    {
        $asset = new FileAsset(__FILE__, array(), __DIR__);
        $this->assertEquals(basename(__FILE__), $asset->getSourcePath(), '->__construct() guesses the asset path');
    }

    public function testInvalidBase()
    {
        $this->setExpectedException('InvalidArgumentException');

        $asset = new FileAsset(__FILE__, array(), __DIR__.'/foo');
    }
}
