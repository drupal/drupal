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
use Assetic\Filter\CssEmbedFilter;

/**
 * @group integration
 */
class CssEmbedFilterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!isset($_SERVER['CSSEMBED_JAR'])) {
            $this->markTestSkipped('There is no CSSEMBED_JAR environment variable.');
        }
    }

    public function testCssEmbedDataUri()
    {
        $data = base64_encode(file_get_contents(__DIR__.'/fixtures/home.png'));

        $asset = new FileAsset(__DIR__ . '/fixtures/cssembed/test.css');
        $asset->load();

        $filter = new CssEmbedFilter($_SERVER['CSSEMBED_JAR']);
        $filter->filterDump($asset);

        $this->assertContains('url(data:image/png;base64,'.$data, $asset->getContent());
    }

    public function testCssEmbedMhtml()
    {
        $asset = new FileAsset(__DIR__ . '/fixtures/cssembed/test.css');
        $asset->load();

        $filter = new CssEmbedFilter($_SERVER['CSSEMBED_JAR']);
        $filter->setMhtml(true);
        $filter->setMhtmlRoot('/test');
        $filter->filterDump($asset);

        $this->assertContains('url(mhtml:/test/!', $asset->getContent());
    }
}
