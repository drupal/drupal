<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test\Cache;

use Assetic\Cache\ApcCache;

/**
 * @group integration
 */
class ApcCacheTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!extension_loaded('apc') || !ini_get('apc.enable_cli')) {
            $this->markTestSkipped('APC must be installed and enabled.');
        }
    }

    public function testCache()
    {
        $cache = new ApcCache();

        $this->assertFalse($cache->has('foo'));

        $cache->set('foo', 'bar');
        $this->assertEquals('bar', $cache->get('foo'));

        $this->assertTrue($cache->has('foo'));

        $cache->remove('foo');
        $this->assertFalse($cache->has('foo'));
    }
}
