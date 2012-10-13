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
use Assetic\Filter\OptiPngFilter;

/**
 * @group integration
 */
class OptiPngFilterTest extends BaseImageFilterTest
{
    private $filter;

    protected function setUp()
    {
        if (!isset($_SERVER['OPTIPNG_BIN'])) {
            $this->markTestSkipped('No OptiPNG configuration.');
        }

        $this->filter = new OptiPngFilter($_SERVER['OPTIPNG_BIN']);
    }

    /**
     * @dataProvider getImages
     */
    public function testFilter($image)
    {
        $asset = new FileAsset($image);
        $asset->load();

        $before = $asset->getContent();
        $this->filter->filterDump($asset);

        $this->assertNotEmpty($asset->getContent(), '->filterDump() sets content');
        $this->assertNotEquals($before, $asset->getContent(), '->filterDump() changes the content');
        $this->assertMimeType('image/png', $asset->getContent(), '->filterDump() creates PNG data');
    }

    public function getImages()
    {
        return array(
            array(__DIR__.'/fixtures/home.gif'),
            array(__DIR__.'/fixtures/home.png'),
        );
    }
}
