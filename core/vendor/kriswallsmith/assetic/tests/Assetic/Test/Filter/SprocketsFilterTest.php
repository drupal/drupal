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
use Assetic\Filter\SprocketsFilter;

/**
 * @group integration
 */
class SprocketsFilterTest extends \PHPUnit_Framework_TestCase
{
    private $assetRoot;

    protected function setUp()
    {
        if (!isset($_SERVER['SPROCKETS_LIB']) || !isset($_SERVER['RUBY_BIN'])) {
            $this->markTestSkipped('There is no sprockets configuration.');
        }

        $this->assetRoot = sys_get_temp_dir().'/assetic_sprockets';
        if (is_dir($this->assetRoot)) {
            $this->cleanup();
        } else {
            mkdir($this->assetRoot);
        }
    }

    protected function tearDown()
    {
        $this->cleanup();
    }

    private function cleanup()
    {
        $it = new \RecursiveDirectoryIterator($this->assetRoot);
        foreach (new \RecursiveIteratorIterator($it) as $path => $file) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testFilterLoad()
    {
        $asset = new FileAsset(__DIR__.'/fixtures/sprockets/main.js');
        $asset->load();

        $filter = new SprocketsFilter($_SERVER['SPROCKETS_LIB'], $_SERVER['RUBY_BIN']);
        $filter->addIncludeDir(__DIR__.'/fixtures/sprockets/lib1');
        $filter->addIncludeDir(__DIR__.'/fixtures/sprockets/lib2');
        $filter->setAssetRoot($this->assetRoot);
        $filter->filterLoad($asset);

        $this->assertContains('/* header.js */', $asset->getContent());
        $this->assertContains('/* include.js */', $asset->getContent());
        $this->assertContains('/* footer.js */', $asset->getContent());
        $this->assertFileExists($this->assetRoot.'/images/image.gif');
    }
}
