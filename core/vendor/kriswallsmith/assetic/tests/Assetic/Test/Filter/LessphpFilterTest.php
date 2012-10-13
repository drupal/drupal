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
use Assetic\Filter\LessphpFilter;

/**
 * @group integration
 */
class LessphpFilterTest extends LessFilterTest
{
    protected function setUp()
    {
        $this->filter = new LessphpFilter();
    }

    public function testPresets()
    {
        $expected = <<<EOF
.foo {
  color: green;
}

EOF;

        $asset = new StringAsset('.foo { color: @bar }');
        $asset->load();

        $this->filter->setPresets(array(
            'bar' => 'green'
        ));

        $this->filter->filterLoad($asset);

        $this->assertEquals($expected, $asset->getContent(), '->setPresets() to pass variables into lessphp filter');
    }
}
