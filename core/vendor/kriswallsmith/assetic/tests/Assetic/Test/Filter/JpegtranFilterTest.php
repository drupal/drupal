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
use Assetic\Filter\JpegtranFilter;

/**
 * @group integration
 */
class JpegtranFilterTest extends BaseImageFilterTest
{
    private $filter;

    protected function setUp()
    {
        if (!isset($_SERVER['JPEGTRAN_BIN'])) {
            $this->markTestSkipped('No jpegtran configuration.');
        }

        $this->filter = new JpegtranFilter($_SERVER['JPEGTRAN_BIN']);
    }

    public function testFilter()
    {
        $asset = new FileAsset(__DIR__.'/fixtures/home.jpg');
        $asset->load();

        $before = $asset->getContent();
        $this->filter->filterDump($asset);

        $this->assertNotEmpty($asset->getContent(), '->filterLoad() sets content');
        $this->assertNotEquals($before, $asset->getContent(), '->filterDump() changes the content');
        $this->assertMimeType('image/jpeg', $asset->getContent(), '->filterDump() creates JPEG data');
    }
}
