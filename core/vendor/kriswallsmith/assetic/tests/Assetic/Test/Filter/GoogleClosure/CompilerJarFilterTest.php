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
use Assetic\Filter\GoogleClosure\CompilerJarFilter;

/**
 * @group integration
 */
class CompilerJarFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testCompile()
    {
        if (!isset($_SERVER['CLOSURE_JAR'])) {
            $this->markTestSkipped('There is no CLOSURE_JAR environment variable.');
        }

        $input = <<<EOF
(function() {
function unused(){}
function foo(bar)
{
    var foo = 'foo';

    return foo + bar;
}
alert(foo("bar"));
})();
EOF;

        $expected = <<<EOF
(function(){alert("foobar")})();

EOF;

        $asset = new StringAsset($input);
        $asset->load();

        $filter = new CompilerJarFilter($_SERVER['CLOSURE_JAR']);
        $filter->filterLoad($asset);
        $filter->filterDump($asset);

        $this->assertEquals($expected, $asset->getContent());


        $input = <<<EOF
(function() {
    var int = 123;
    console.log(int);
})();
EOF;

        $expected = <<<EOF
(function(){console.log(123)})();

EOF;

        $asset = new StringAsset($input);
        $asset->load();

        $filter->setLanguage(CompilerJarFilter::LANGUAGE_ECMASCRIPT5);

        $filter->filterLoad($asset);
        $filter->filterDump($asset);

        $this->assertEquals($expected, $asset->getContent());
    }
}
