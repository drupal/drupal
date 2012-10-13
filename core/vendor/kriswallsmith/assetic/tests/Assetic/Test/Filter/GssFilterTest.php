<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test\Filter\GoogleClosure;

use Assetic\Asset\StringAsset;
use Assetic\Filter\GssFilter;

/**
 * @group integration
 */
class GssFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testCompile()
    {
        if (!isset($_SERVER['GSS_JAR'])) {
            $this->markTestSkipped('There is no GSS_JAR environment variable.');
        }

        $input = <<<EOF
@def BG_COLOR rgb(235, 239, 249);
body {background-color: BG_COLOR;}
EOF;

        $expected = <<<EOF
body{background-color:#ebeff9}
EOF;

        $asset = new StringAsset($input);
        $asset->load();

        $filter = new GssFilter($_SERVER['GSS_JAR']);
        $filter->filterLoad($asset);

        $this->assertEquals($expected, $asset->getContent());
    }
}
