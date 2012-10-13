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

use Assetic\Asset\FileAsset;
use Assetic\Filter\JSMinFilter;

/**
 * @group integration
 */
class JSMinFilterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('JSMin')) {
            $this->markTestSkipped('JSMin is not installed.');
        }
    }

    public function testRelativeSourceUrlImportImports()
    {
        $asset = new FileAsset(__DIR__.'/fixtures/jsmin/js.js');
        $asset->load();

        $filter = new JSMinFilter();
        $filter->filterDump($asset);

        $this->assertEquals('var a="abc";;;var bbb="u";', $asset->getContent());
    }
}
