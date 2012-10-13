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
use Assetic\Filter\CompassFilter;

/**
 * Compass filter test case.
 *
 * @author Maxime Thirouin <dev@moox.fr>
 * @group integration
 */
class CompassFilterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!isset($_SERVER['COMPASS_BIN'])) {
            $this->markTestSkipped('There is no COMPASS_BIN environment variable.');
        }
    }

    public function testFilterLoadWithScss()
    {
        $asset = new FileAsset(__DIR__.'/fixtures/compass/stylesheet.scss');
        $asset->load();

        $filter = new CompassFilter($_SERVER['COMPASS_BIN']);
        $filter->filterLoad($asset);

        $this->assertContains('.test-class', $asset->getContent());
        $this->assertContains('font-size: 2em;', $asset->getContent());
    }

    public function testFilterLoadWithSass()
    {
        $asset = new FileAsset(__DIR__.'/fixtures/compass/stylesheet.sass');
        $asset->load();

        $filter = new CompassFilter($_SERVER['COMPASS_BIN']);
        $filter->filterLoad($asset);

        $this->assertContains('.test-class', $asset->getContent());
        $this->assertContains('font-size: 2em;', $asset->getContent());
    }

    public function testCompassMixin()
    {
        $asset = new FileAsset(__DIR__.'/fixtures/compass/compass.sass');
        $asset->load();

        $filter = new CompassFilter($_SERVER['COMPASS_BIN']);
        $filter->filterLoad($asset);

        $this->assertContains('text-decoration', $asset->getContent());
    }
}
