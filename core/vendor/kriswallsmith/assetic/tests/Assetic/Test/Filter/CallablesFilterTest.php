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

use Assetic\Filter\CallablesFilter;

class CallablesFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $filter = new CallablesFilter();
        $this->assertInstanceOf('Assetic\\Filter\\FilterInterface', $filter, 'CallablesFilter implements FilterInterface');
    }

    public function testLoader()
    {
        $nb = 0;
        $filter = new CallablesFilter(function($asset) use (&$nb) { $nb++; });
        $filter->filterLoad($this->getMock('Assetic\\Asset\\AssetInterface'));
        $this->assertEquals(1, $nb, '->filterLoad() calls the loader callable');
    }

    public function testDumper()
    {
        $nb = 0;
        $filter = new CallablesFilter(null, function($asset) use (&$nb) { $nb++; });
        $filter->filterDump($this->getMock('Assetic\\Asset\\AssetInterface'));
        $this->assertEquals(1, $nb, '->filterDump() calls the loader callable');
    }
}
