<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test\Util;

use Assetic\Util\TraversableString;

class TraversableStringTest extends \PHPUnit_Framework_TestCase
{
    public function testString()
    {
        $foo = new TraversableString('foo', array('foo', 'bar'));
        $this->assertEquals('foo', (string) $foo);
    }

    public function testArray()
    {
        $foo = new TraversableString('foo', array('foo', 'bar'));

        $values = array();
        foreach ($foo as $value) {
            $values[] = $value;
        }

        $this->assertEquals(array('foo', 'bar'), $values);
    }
}
