<?php
/**
 * This file is part of vfsStream.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\vfs
 */
namespace org\bovigo\vfs;
use org\bovigo\vfs\content\LargeFileContent;
/**
 * Test for large file mocks.
 *
 * @package     bovigo_vfs
 * @subpackage  test
 * @since       1.3.0
 * @group       issue_79
 */
class vfsStreamWrapperLargeFileTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * large file to test
     *
     * @var  vfsStreamFile
     */
    private $largeFile;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $root = vfsStream::setup();
        $this->largeFile = vfsStream::newFile('large.txt')
                                    ->withContent(LargeFileContent::withGigabytes(100))
                                    ->at($root);
    }

    /**
     * @test
     */
    public function hasLargeFileSize()
    {
        $this->assertEquals(
                100 * 1024 * 1024 * 1024,
                filesize($this->largeFile->url())
        );
    }

    /**
     * @test
     */
    public function canReadFromLargeFile()
    {
        $fp = fopen($this->largeFile->url(), 'rb');
        $data = fread($fp, 15);
        fclose($fp);
        $this->assertEquals(str_repeat(' ', 15), $data);
    }

    /**
     * @test
     */
    public function canWriteIntoLargeFile()
    {
        $fp = fopen($this->largeFile->url(), 'rb+');
        fseek($fp, 100 * 1024 * 1024, SEEK_SET);
        fwrite($fp, 'foobarbaz');
        fclose($fp);
        $this->largeFile->seek((100 * 1024 * 1024) - 3, SEEK_SET);
        $this->assertEquals(
                '   foobarbaz   ',
                $this->largeFile->read(15)
        );
    }
}
