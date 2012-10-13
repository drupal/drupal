<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test\Factory\Resource;

use Assetic\Factory\Resource\CoalescingDirectoryResource;
use Assetic\Factory\Resource\DirectoryResource;

class CoalescingDirectoryResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldFilterFiles()
    {
        // notice only one directory has a trailing slash
        $resource = new CoalescingDirectoryResource(array(
            new DirectoryResource(__DIR__.'/Fixtures/dir1/', '/\.txt$/'),
            new DirectoryResource(__DIR__.'/Fixtures/dir2', '/\.txt$/'),
        ));

        $paths = array();
        foreach ($resource as $file) {
            $paths[] = realpath((string) $file);
        }
        sort($paths);

        $this->assertEquals(array(
            realpath(__DIR__.'/Fixtures/dir1/file1.txt'),
            realpath(__DIR__.'/Fixtures/dir1/file2.txt'),
            realpath(__DIR__.'/Fixtures/dir2/file3.txt'),
        ), $paths, 'files from multiple directories are merged');
    }
}
