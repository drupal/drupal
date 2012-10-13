<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test\Filter\Sass;

use Assetic\Asset\FileAsset;
use Assetic\Filter\Sass\ScssFilter;

/**
 * @group integration
 */
class ScssFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testImport()
    {
        if (!isset($_SERVER['SASS_BIN'])) {
            $this->markTestSkipped('There is no SASS_BIN environment variable.');
        }

        $asset = new FileAsset(__DIR__.'/../fixtures/sass/main.scss');
        $asset->load();

        $filter = new ScssFilter($_SERVER['SASS_BIN']);
        $filter->setStyle(ScssFilter::STYLE_COMPACT);
        $filter->filterLoad($asset);

        $expected = <<<EOF
.foo { color: blue; }

.foo { color: red; }

EOF;

        $this->assertEquals($expected, $asset->getContent(), '->filterLoad() loads imports');
    }
}
