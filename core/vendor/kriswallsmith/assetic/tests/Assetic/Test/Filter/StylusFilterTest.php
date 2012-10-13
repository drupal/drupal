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

use Assetic\Asset\StringAsset;
use Assetic\Filter\StylusFilter;

/**
 * @group integration
 */
class StylusFilterTest extends \PHPUnit_Framework_TestCase
{
    private $filter;

    protected function setUp()
    {
        if (!isset($_SERVER['NODE_BIN']) || !isset($_SERVER['NODE_PATH'])) {
            $this->markTestSkipped('No node.js configuration.');
        }

        $this->filter = new StylusFilter($_SERVER['NODE_BIN'], array($_SERVER['NODE_PATH']));
    }

    public function testFilterLoad()
    {
        $asset = new StringAsset("body\n  font 12px Helvetica, Arial, sans-serif\n  color black");
        $asset->load();

        $this->filter->filterLoad($asset);

        $this->assertEquals("body {\n  font: 12px Helvetica, Arial, sans-serif;\n  color: #000;\n}\n", $asset->getContent(), '->filterLoad() parses the content');
    }

    public function testFilterLoadWithCompression()
    {
        $asset = new StringAsset("body\n  font 12px Helvetica, Arial, sans-serif\n  color black;");
        $asset->load();

        $this->filter->setCompress(true);
        $this->filter->filterLoad($asset);

        $this->assertEquals("body{font:12px Helvetica,Arial,sans-serif;color:#000}\n", $asset->getContent(), '->filterLoad() parses the content and compress it');
    }
}
