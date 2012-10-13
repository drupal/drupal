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

use Assetic\Asset\GlobAsset;

class GlobAssetTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $asset = new GlobAsset(__DIR__.'/*.php');
        $this->assertInstanceOf('Assetic\\Asset\\AssetInterface', $asset, 'Asset implements AssetInterface');
    }

    public function testIteration()
    {
        $assets = new GlobAsset(__DIR__.'/*.php');
        $this->assertGreaterThan(0, iterator_count($assets), 'GlobAsset initializes for iteration');
    }

    public function testRecursiveIteration()
    {
        $assets = new GlobAsset(__DIR__.'/*.php');
        $this->assertGreaterThan(0, iterator_count($assets), 'GlobAsset initializes for recursive iteration');
    }

    public function testGetLastModifiedType()
    {
        $assets = new GlobAsset(__DIR__.'/*.php');
        $this->assertInternalType('integer', $assets->getLastModified(), '->getLastModified() returns an integer');
    }

    public function testGetLastModifiedValue()
    {
        $assets = new GlobAsset(__DIR__.'/*.php');
        $this->assertLessThan(time(), $assets->getLastModified(), '->getLastModified() returns a file mtime');
    }

    public function testLoad()
    {
        $assets = new GlobAsset(__DIR__.'/*.php');
        $assets->load();

        $this->assertNotEmpty($assets->getContent(), '->load() loads contents');
    }

    public function testDump()
    {
        $assets = new GlobAsset(__DIR__.'/*.php');
        $this->assertNotEmpty($assets->dump(), '->dump() dumps contents');
    }
}
