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

use Assetic\Cache\FilesystemCache;

class FilesystemCacheTest extends \PHPUnit_Framework_TestCase
{
    public function testCache()
    {
        $cache = new FilesystemCache(sys_get_temp_dir());

        $this->assertFalse($cache->has('foo'));

        $cache->set('foo', 'bar');
        $this->assertEquals('bar', $cache->get('foo'));

        $this->assertTrue($cache->has('foo'));

        $cache->remove('foo');
        $this->assertFalse($cache->has('foo'));
    }

    public function testSetCreatesDir()
    {
        $dir = sys_get_temp_dir().'/assetic/fscachetest';

        $tearDown = function() use ($dir) {
            array_map('unlink', glob($dir.'/*'));
            @rmdir($dir);
        };

        $tearDown();

        $cache = new FilesystemCache($dir);
        $cache->set('foo', 'bar');

        $this->assertFileExists($dir.'/foo');

        $tearDown();
    }
}
