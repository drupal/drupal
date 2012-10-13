<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test\Factory;

use Assetic\Factory\LazyAssetManager;

class LazyAssetManagerTest extends \PHPUnit_Framework_TestCase
{
    private $factory;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder('Assetic\\Factory\\AssetFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->am = new LazyAssetManager($this->factory);
    }

    public function testGetFromLoader()
    {
        $resource = $this->getMock('Assetic\\Factory\\Resource\\ResourceInterface');
        $loader = $this->getMock('Assetic\\Factory\\Loader\\FormulaLoaderInterface');
        $asset = $this->getMock('Assetic\\Asset\\AssetInterface');

        $formula = array(
            array('js/core.js', 'js/more.js'),
            array('?yui_js'),
            array('output' => 'js/all.js')
        );

        $loader->expects($this->once())
            ->method('load')
            ->with($resource)
            ->will($this->returnValue(array('foo' => $formula)));
        $this->factory->expects($this->once())
            ->method('createAsset')
            ->with($formula[0], $formula[1], $formula[2] + array('name' => 'foo'))
            ->will($this->returnValue($asset));

        $this->am->setLoader('foo', $loader);
        $this->am->addResource($resource, 'foo');

        $this->assertSame($asset, $this->am->get('foo'), '->get() returns an asset from the loader');

        // test the "once" expectations
        $this->am->get('foo');
    }

    public function testGetResources()
    {
        $resources = array(
            $this->getMock('Assetic\\Factory\\Resource\\ResourceInterface'),
            $this->getMock('Assetic\\Factory\\Resource\\ResourceInterface'),
        );

        $this->am->addResource($resources[0], 'foo');
        $this->am->addResource($resources[1], 'bar');

        $ret = $this->am->getResources();

        foreach ($resources as $resource) {
            $this->assertTrue(in_array($resource, $ret, true));
        }
    }

    public function testGetResourcesEmpty()
    {
        $this->am->getResources();
    }

    public function testSetFormula()
    {
        $this->am->setFormula('foo', array());
        $this->am->load();
        $this->assertTrue($this->am->hasFormula('foo'), '->load() does not remove manually added formulae');
    }

    public function testIsDebug()
    {
        $this->factory->expects($this->once())
            ->method('isDebug')
            ->will($this->returnValue(false));

        $this->assertSame(false, $this->am->isDebug(), '->isDebug() proxies the factory');
    }
}
