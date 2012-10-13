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
use Assetic\Filter\UglifyJsFilter;

/**
 * @group integration
 */
class UglifyJsFilterTest extends \PHPUnit_Framework_TestCase
{
    private $asset;
    private $filter;

    protected function setUp()
    {
        if (!isset($_SERVER['UGLIFYJS_BIN'])) {
            $this->markTestSkipped('There is no uglifyJs configuration.');
        }

        $this->asset = new FileAsset(__DIR__.'/fixtures/uglifyjs/script.js');
        $this->asset->load();

        if (isset($_SERVER['NODE_BIN'])) {
            $this->filter = new UglifyJsFilter($_SERVER['UGLIFYJS_BIN'], $_SERVER['NODE_BIN']);
        } else {
            $this->filter = new UglifyJsFilter($_SERVER['UGLIFYJS_BIN']);
        }
    }

    protected function tearDown()
    {
        $this->asset = null;
        $this->filter = null;
    }

    public function testUglify()
    {
        $this->filter->filterDump($this->asset);

        $expected = <<<JS
/**
 * Copyright
 */function bar(a){return var2.push(a),a}var foo=new Array(1,2,3,4),bar=Array(a,b,c),var1=new Array(5),var2=new Array(a),foo=function(a){return a};
JS;
        $this->assertSame($expected, $this->asset->getContent());
    }

    public function testUnsafeUglify()
    {
        $this->filter->setUnsafe(true);
        $this->filter->filterDump($this->asset);

        $expected = <<<JS
/**
 * Copyright
 */function bar(a){return var2.push(a),a}var foo=[1,2,3,4],bar=[a,b,c],var1=Array(5),var2=Array(a),foo=function(a){return a};
JS;
        $this->assertSame($expected, $this->asset->getContent());
    }

    public function testBeautifyUglify()
    {
        $this->filter->setBeautify(true);
        $this->filter->filterDump($this->asset);

        $expected = <<<JS
/**
 * Copyright
 */function bar(a) {
    return var2.push(a), a;
}

var foo = new Array(1, 2, 3, 4), bar = Array(a, b, c), var1 = new Array(5), var2 = new Array(a), foo = function(a) {
    return a;
};
JS;

        $this->assertSame($expected, $this->asset->getContent());
    }

    public function testNoCopyrightUglify()
    {
        $this->filter->setNoCopyright(true);
        $this->filter->filterDump($this->asset);

        $expected = 'function bar(a){return var2.push(a),a}var foo=new Array(1,2,3,4),bar=Array(a,b,c),var1=new Array(5),var2=new Array(a),foo=function(a){return a};';
        $this->assertSame($expected, $this->asset->getContent());
    }
}
