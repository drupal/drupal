<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test;

use Assetic\AssetManager;

class AssetManagerTest extends \PHPUnit_Framework_TestCase
{
    private $am;

    protected function setUp()
    {
        $this->am = new AssetManager();
    }

    public function testGetAsset()
    {
        $asset = $this->getMock('Assetic\\Asset\\AssetInterface');
        $this->am->set('foo', $asset);
        $this->assertSame($asset, $this->am->get('foo'), '->get() returns an asset');
    }

    public function testGetInvalidAsset()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->am->get('foo');
    }

    public function testHas()
    {
        $asset = $this->getMock('Assetic\\Asset\\AssetInterface');
        $this->am->set('foo', $asset);

        $this->assertTrue($this->am->has('foo'), '->has() returns true if the asset is set');
        $this->assertFalse($this->am->has('bar'), '->has() returns false if the asset is not set');
    }

    public function testInvalidName()
    {
        $this->setExpectedException('InvalidArgumentException');

        $this->am->set('@foo', $this->getMock('Assetic\\Asset\\AssetInterface'));
    }
}
