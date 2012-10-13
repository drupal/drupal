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
use Assetic\Filter\CssImportFilter;
use Assetic\Filter\CssRewriteFilter;

class CssImportFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getFilters
     */
    public function testImport($filter1, $filter2)
    {
        $asset = new FileAsset(__DIR__.'/fixtures/cssimport/main.css', array(), __DIR__.'/fixtures/cssimport', 'main.css');
        $asset->setTargetPath('foo/bar.css');
        $asset->ensureFilter($filter1);
        $asset->ensureFilter($filter2);

        $expected = <<<CSS
/* main.css */
/* import.css */
body { color: red; }
/* more/evenmore/deep1.css */
/* more/evenmore/deep2.css */
body {
    background: url(../more/evenmore/bg.gif);
}
body { color: black; }
CSS;

        $this->assertEquals($expected, $asset->dump(), '->filterLoad() inlines CSS imports');
    }

    /**
     * The order of these two filters is only interchangeable because one acts on
     * load and the other on dump. We need a more scalable solution.
     */
    public function getFilters()
    {
        return array(
            array(new CssImportFilter(), new CssRewriteFilter()),
            array(new CssRewriteFilter(), new CssImportFilter()),
        );
    }

    public function testNonCssImport()
    {
        $asset = new FileAsset(__DIR__.'/fixtures/cssimport/noncssimport.css', array(), __DIR__.'/fixtures/cssimport', 'noncssimport.css');
        $asset->load();

        $filter = new CssImportFilter();
        $filter->filterLoad($asset);

        $this->assertEquals(file_get_contents(__DIR__.'/fixtures/cssimport/noncssimport.css'), $asset->getContent(), '->filterLoad() skips non css');
    }
}
