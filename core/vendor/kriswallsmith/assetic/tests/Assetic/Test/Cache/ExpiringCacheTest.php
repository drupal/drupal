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

use Assetic\Cache\ExpiringCache;

class ExpiringCacheTest extends \PHPUnit_Framework_TestCase
{
    private $inner;
    private $lifetime;
    private $cache;

    protected function setUp()
    {
        $this->inner = $this->getMock('Assetic\\Cache\\CacheInterface');
        $this->lifetime = 3600;
        $this->cache = new ExpiringCache($this->inner, $this->lifetime);
    }

    public function testHasExpired()
    {
        $key = 'asdf';
        $expiresKey = 'asdf.expires';
        $thePast = 0;

        $this->inner->expects($this->once())
            ->method('has')
            ->with($key)
            ->will($this->returnValue(true));
        $this->inner->expects($this->once())
            ->method('get')
            ->with($expiresKey)
            ->will($this->returnValue($thePast));
        $this->inner->expects($this->at(2))
            ->method('remove')
            ->with($expiresKey);
        $this->inner->expects($this->at(3))
            ->method('remove')
            ->with($key);

        $this->assertFalse($this->cache->has($key), '->has() returns false if an expired value exists');
    }

    public function testHasNotExpired()
    {
        $key = 'asdf';
        $expiresKey = 'asdf.expires';
        $theFuture = time() * 2;

        $this->inner->expects($this->once())
            ->method('has')
            ->with($key)
            ->will($this->returnValue(true));
        $this->inner->expects($this->once())
            ->method('get')
            ->with($expiresKey)
            ->will($this->returnValue($theFuture));

        $this->assertTrue($this->cache->has($key), '->has() returns true if a value the not expired');
    }

    public function testSetLifetime()
    {
        $key = 'asdf';
        $expiresKey = 'asdf.expires';
        $value = 'qwerty';

        $this->inner->expects($this->at(0))
            ->method('set')
            ->with($expiresKey, $this->greaterThanOrEqual(time() + $this->lifetime));
        $this->inner->expects($this->at(1))
            ->method('set')
            ->with($key, $value);

        $this->cache->set($key, $value);
    }

    public function testRemove()
    {
        $key = 'asdf';
        $expiresKey = 'asdf.expires';

        $this->inner->expects($this->at(0))
            ->method('remove')
            ->with($expiresKey);
        $this->inner->expects($this->at(1))
            ->method('remove')
            ->with($key);

        $this->cache->remove($key);
    }

    public function testGet()
    {
        $this->inner->expects($this->once())
            ->method('get')
            ->with('foo')
            ->will($this->returnValue('bar'));

        $this->assertEquals('bar', $this->cache->get('foo'), '->get() returns the cached value');
    }
}
