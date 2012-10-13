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
use Assetic\Filter\PhpCssEmbedFilter;

/**
 * @group integration
 */
class PhpCssEmbedFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testCssEmbedDataUri()
    {
        $data = base64_encode(file_get_contents(__DIR__.'/fixtures/home.png'));

        $asset = new FileAsset(__DIR__ . '/fixtures/cssembed/test.css');
        $asset->load();

        $filter = new PhpCssEmbedFilter();
        $filter->filterLoad($asset);

        $this->assertContains('url(data:image/png;base64,'.$data, $asset->getContent());
    }
}
