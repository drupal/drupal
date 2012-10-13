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
use Assetic\Asset\StringAsset;
use Assetic\Filter\LessFilter;

/**
 * @group integration
 */
class LessFilterTest extends \PHPUnit_Framework_TestCase
{
    protected $filter;

    protected function setUp()
    {
        if (!isset($_SERVER['NODE_BIN']) || !isset($_SERVER['NODE_PATH'])) {
            $this->markTestSkipped('No node.js configuration.');
        }

        $this->filter = new LessFilter($_SERVER['NODE_BIN'], array($_SERVER['NODE_PATH']));
    }

    public function testFilterLoad()
    {
        $asset = new StringAsset('.foo{.bar{width:1+1;}}');
        $asset->load();

        $this->filter->filterLoad($asset);

        $this->assertEquals(".foo .bar {\n  width: 2;\n}\n", $asset->getContent(), '->filterLoad() parses the content');
    }

    public function testImport()
    {
        $expected = <<<EOF
.foo {
  color: blue;
}
.foo {
  color: red;
}

EOF;

        $asset = new FileAsset(__DIR__.'/fixtures/less/main.less');
        $asset->load();

        $this->filter->filterLoad($asset);

        $this->assertEquals($expected, $asset->getContent(), '->filterLoad() sets an include path based on source url');
    }
}
