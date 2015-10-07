<?php
/**
 * This file is part of vfsStream.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\vfs
 */
namespace org\bovigo\vfs\content;
/**
 * Test for org\bovigo\vfs\content\LargeFileContent.
 *
 * @since  1.3.0
 * @group  issue_79
 */
class LargeFileContentTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  LargeFileContent
     */
    private $largeFileContent;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->largeFileContent = new LargeFileContent(100);
    }

    /**
     * @test
     */
    public function hasSizeOriginallyGiven()
    {
        $this->assertEquals(100, $this->largeFileContent->size());
    }

    /**
     * @test
     */
    public function contentIsFilledUpWithSpacesIfNoDataWritten()
    {
        $this->assertEquals(
                str_repeat(' ', 100),
                $this->largeFileContent->content()
        );
    }

    /**
     * @test
     */
    public function readReturnsSpacesWhenNothingWrittenAtOffset()
    {
        $this->assertEquals(
                str_repeat(' ', 10),
                $this->largeFileContent->read(10)
        );
    }

    /**
     * @test
     */
    public function readReturnsContentFilledWithSpaces()
    {
        $this->largeFileContent->write('foobarbaz');
        $this->largeFileContent->seek(0, SEEK_SET);
        $this->assertEquals(
                'foobarbaz ',
                $this->largeFileContent->read(10)
        );
    }

    /**
     * @test
     */
    public function writesDataAtStartWhenOffsetNotMoved()
    {
        $this->assertEquals(9, $this->largeFileContent->write('foobarbaz'));
        $this->assertEquals(
                'foobarbaz' . str_repeat(' ', 91),
                $this->largeFileContent->content()
        );
    }

    /**
     * @test
     */
    public function writeDataAtStartDoesNotIncreaseSize()
    {
        $this->assertEquals(9, $this->largeFileContent->write('foobarbaz'));
        $this->assertEquals(100, $this->largeFileContent->size());
    }

    /**
     * @test
     */
    public function writesDataAtOffsetWhenOffsetMoved()
    {
        $this->largeFileContent->seek(50, SEEK_SET);
        $this->assertEquals(9, $this->largeFileContent->write('foobarbaz'));
        $this->assertEquals(
                str_repeat(' ', 50) . 'foobarbaz' . str_repeat(' ', 41),
                $this->largeFileContent->content()
        );
    }

    /**
     * @test
     */
    public function writeDataInBetweenDoesNotIncreaseSize()
    {
        $this->largeFileContent->seek(50, SEEK_SET);
        $this->assertEquals(9, $this->largeFileContent->write('foobarbaz'));
        $this->assertEquals(100, $this->largeFileContent->size());
    }

    /**
     * @test
     */
    public function writesDataOverEndWhenOffsetAndDataLengthLargerThanSize()
    {
        $this->largeFileContent->seek(95, SEEK_SET);
        $this->assertEquals(9, $this->largeFileContent->write('foobarbaz'));
        $this->assertEquals(
                str_repeat(' ', 95) . 'foobarbaz',
                $this->largeFileContent->content()
        );
    }

    /**
     * @test
     */
    public function writeDataOverLastOffsetIncreasesSize()
    {
        $this->largeFileContent->seek(95, SEEK_SET);
        $this->assertEquals(9, $this->largeFileContent->write('foobarbaz'));
        $this->assertEquals(104, $this->largeFileContent->size());
    }

    /**
     * @test
     */
    public function writesDataAfterEndWhenOffsetAfterEnd()
    {
        $this->largeFileContent->seek(0, SEEK_END);
        $this->assertEquals(9, $this->largeFileContent->write('foobarbaz'));
        $this->assertEquals(
                str_repeat(' ', 100) . 'foobarbaz',
                $this->largeFileContent->content()
        );
    }

    /**
     * @test
     */
    public function writeDataAfterLastOffsetIncreasesSize()
    {
        $this->largeFileContent->seek(0, SEEK_END);
        $this->assertEquals(9, $this->largeFileContent->write('foobarbaz'));
        $this->assertEquals(109, $this->largeFileContent->size());
    }

    /**
     * @test
     */
    public function truncateReducesSize()
    {
        $this->assertTrue($this->largeFileContent->truncate(50));
        $this->assertEquals(50, $this->largeFileContent->size());
    }

    /**
     * @test
     */
    public function truncateRemovesWrittenContentAfterOffset()
    {
        $this->largeFileContent->seek(45, SEEK_SET);
        $this->largeFileContent->write('foobarbaz');
        $this->assertTrue($this->largeFileContent->truncate(50));
        $this->assertEquals(
                str_repeat(' ', 45) . 'fooba',
                $this->largeFileContent->content()
        );
    }

    /**
     * @test
     */
    public function createInstanceWithKilobytes()
    {
        $this->assertEquals(
                100 * 1024,
                LargeFileContent::withKilobytes(100)
                                ->size()
        );
    }

    /**
     * @test
     */
    public function createInstanceWithMegabytes()
    {
        $this->assertEquals(
                100 * 1024 * 1024,
                LargeFileContent::withMegabytes(100)
                                ->size()
        );
    }

    /**
     * @test
     */
    public function createInstanceWithGigabytes()
    {
        $this->assertEquals(
                100 * 1024 *  1024 * 1024,
                LargeFileContent::withGigabytes(100)
                                ->size()
        );
    }
}
