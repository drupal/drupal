<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test\Filter;

use Assetic\Filter\FilterCollection;

class FilterCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $filter = new FilterCollection();
        $this->assertInstanceOf('Assetic\\Filter\\FilterInterface', $filter, 'FilterCollection implements FilterInterface');
    }

    public function testEnsure()
    {
        $filter = $this->getMock('Assetic\\Filter\\FilterInterface');
        $asset = $this->getMock('Assetic\\Asset\\AssetInterface');

        $filter->expects($this->once())->method('filterLoad');

        $coll = new FilterCollection();
        $coll->ensure($filter);
        $coll->ensure($filter);
        $coll->filterLoad($asset);
    }

    public function testAll()
    {
        $filter = new FilterCollection(array(
            $this->getMock('Assetic\\Filter\\FilterInterface'),
            $this->getMock('Assetic\\Filter\\FilterInterface'),
        ));

        $this->assertInternalType('array', $filter->all(), '->all() returns an array');
    }

    public function testEmptyAll()
    {
        $filter = new FilterCollection();
        $this->assertInternalType('array', $filter->all(), '->all() returns an array');
    }

    public function testCountable()
    {
        $filters = new FilterCollection(array($this->getMock('Assetic\\Filter\\FilterInterface')));

        $this->assertEquals(1, count($filters), 'Countable returns the count');
    }
}
