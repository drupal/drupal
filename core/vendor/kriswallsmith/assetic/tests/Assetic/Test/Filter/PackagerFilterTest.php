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
use Assetic\Filter\PackagerFilter;

/**
 * @group integration
 */
class PackagerFilterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Packager', false)) {
            $this->markTestSkipped('Packager is not available.');
        }
    }

    public function testPackager()
    {
        $expected = <<<EOF
/*
---

name: Util

provides: [Util]

...
*/

function foo() {}


/*
---

name: App

requires: [Util/Util]

...
*/

var bar = foo();


EOF;

        $asset = new FileAsset(__DIR__.'/fixtures/packager/app/application.js', array(), __DIR__.'/fixtures/packager/app', 'application.js');
        $asset->load();

        $filter = new PackagerFilter();
        $filter->addPackage(__DIR__.'/fixtures/packager/lib');
        $filter->filterLoad($asset);

        $this->assertEquals($expected, $asset->getContent(), '->filterLoad() runs packager');
    }
}
