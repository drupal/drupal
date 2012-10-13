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
use Assetic\Filter\CssMinFilter;

/**
 * @group integration
 */
class CssMinFilterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('CssMin')) {
            $this->markTestSkipped('CssMin is not installed.');
        }
    }

    public function testRelativeSourceUrlImportImports()
    {
        $asset = new FileAsset(__DIR__.'/fixtures/cssmin/main.css');
        $asset->load();

        $filter = new CssMinFilter(__DIR__.'/fixtures/cssmin');
        $filter->setFilter('ImportImports', true);
        $filter->filterDump($asset);

        $this->assertEquals('body{color:white}body{background:black}', $asset->getContent());
    }
}
