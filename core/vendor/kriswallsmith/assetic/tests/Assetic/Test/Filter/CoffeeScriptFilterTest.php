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
use Assetic\Filter\CoffeeScriptFilter;

/**
 * @group integration
 */
class CoffeeScriptFilterTest extends \PHPUnit_Framework_TestCase
{
    private $filter;

    protected function setUp()
    {
        if (!isset($_SERVER['COFFEE_BIN']) || !isset($_SERVER['NODE_BIN'])) {
            $this->markTestSkipped('There is no COFFEE_BIN or NODE_BIN environment variable.');
        }

        $this->filter = new CoffeeScriptFilter($_SERVER['COFFEE_BIN'], $_SERVER['NODE_BIN']);
    }

    public function testFilterLoad()
    {
        $expected = <<<JAVASCRIPT
(function() {
  var square;

  square = function(x) {
    return x * x;
  };

}).call(this);

JAVASCRIPT;

        $asset = new StringAsset('square = (x) -> x * x');
        $asset->load();

        $this->filter->filterLoad($asset);

        $this->assertEquals($expected, $asset->getContent());
    }

    public function testBare()
    {
        $expected = <<<JAVASCRIPT
var square;

square = function(x) {
  return x * x;
};

JAVASCRIPT;
        $asset = new StringAsset('square = (x) -> x * x');
        $asset->load();

        $this->filter->setBare(true);
        $this->filter->filterLoad($asset);

        $this->assertEquals($expected, $asset->getContent());
    }
}
