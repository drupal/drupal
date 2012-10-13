<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test\Filter\Yui;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\Yui\BaseCompressorFilter;

class BaseCompressorFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $filter = new YuiCompressorFilterForTest('/path/to/jar');
        $this->assertInstanceOf('Assetic\\Filter\\FilterInterface', $filter, 'BaseCompressorFilter implements FilterInterface');
    }
}

class YuiCompressorFilterForTest extends BaseCompressorFilter
{
    public function filterDump(AssetInterface $asset)
    {
    }
}
