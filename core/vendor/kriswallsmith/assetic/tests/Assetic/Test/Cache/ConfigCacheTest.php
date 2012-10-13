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

use Assetic\Cache\ConfigCache;

class ConfigCacheTest extends \PHPUnit_Framework_TestCase
{
    private $dir;
    private $cache;

    protected function setUp()
    {
        $this->dir = sys_get_temp_dir().'/assetic/tests/config_cache';
        $this->cache = new ConfigCache($this->dir);
    }

    protected function tearDown()
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            unlink($file->getPathname());
        }
    }

    public function testCache()
    {
        $this->cache->set('foo', array(1, 2, 3));
        $this->assertEquals(array(1, 2, 3), $this->cache->get('foo'), '->get() returns the ->set() value');
    }

    public function testTimestamp()
    {
        $this->cache->set('bar', array(4, 5, 6));
        $this->assertInternalType('integer', $time = $this->cache->getTimestamp('bar'), '->getTimestamp() returns an integer');
        $this->assertNotEmpty($time, '->getTimestamp() returns a non-empty number');
    }

    public function testInvalidValue()
    {
        $this->setExpectedException('RuntimeException');
        $this->cache->get('_invalid');
    }

    public function testInvalidTimestamp()
    {
        $this->setExpectedException('RuntimeException');
        $this->cache->getTimestamp('_invalid');
    }

    public function testHas()
    {
        $this->cache->set('foo', 'bar');
        $this->assertTrue($this->cache->has('foo'));
        $this->assertFalse($this->cache->has('_invalid'));
    }
}
