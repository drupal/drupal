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
use Assetic\Filter\JSMinPlusFilter;

/**
 * @group integration
 */
class JSMinPlusFilterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('JSMinPlus')) {
            $this->markTestSkipped('JSMinPlus is not installed.');
        }
    }

    public function testRelativeSourceUrlImportImports()
    {
        $asset = new FileAsset(__DIR__.'/fixtures/jsmin/js.js');
        $asset->load();

        $filter = new JSMinPlusFilter();
        $filter->filterDump($asset);

        $this->assertEquals('var a="abc",bbb="u"', $asset->getContent());
    }
}
