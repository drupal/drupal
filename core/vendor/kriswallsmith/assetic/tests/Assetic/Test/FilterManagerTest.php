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

use Assetic\FilterManager;

class FilterManagerTest extends \PHPUnit_Framework_TestCase
{
    private $fm;

    protected function setUp()
    {
        $this->fm = new FilterManager();
    }

    public function testInvalidName()
    {
        $this->setExpectedException('InvalidArgumentException');

        $this->fm->get('foo');
    }

    public function testGetFilter()
    {
        $filter = $this->getMock('Assetic\\Filter\\FilterInterface');
        $name = 'foo';

        $this->fm->set($name, $filter);

        $this->assertSame($filter, $this->fm->get($name), '->set() sets a filter');
    }

    public function testHas()
    {
        $this->fm->set('foo', $this->getMock('Assetic\\Filter\\FilterInterface'));
        $this->assertTrue($this->fm->has('foo'), '->has() returns true if the filter is set');
    }

    public function testHasInvalid()
    {
        $this->assertFalse($this->fm->has('foo'), '->has() returns false if the filter is not set');
    }

    public function testInvalidAlias()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->fm->set('@foo', $this->getMock('Assetic\\Filter\\FilterInterface'));
    }
}
