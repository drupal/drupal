<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2011 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test\Filter;

use Assetic\Asset\FileAsset;
use Assetic\Filter\PackerFilter;

class PackerFilterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('JavaScriptPacker')) {
            $this->markTestSkipped('JavaScriptPacker is not installed.');
        }
    }

    public function testPacker()
    {
        $asset = new FileAsset(__DIR__.'/fixtures/packer/example.js');
        $asset->load();

        $filter = new PackerFilter();
        $filter->filterDump($asset);

        $this->assertEquals("var exampleFunction=function(arg1,arg2){alert('exampleFunction called!')}", $asset->getContent());
    }
}
