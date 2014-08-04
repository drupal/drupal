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
 * Test for org\bovigo\vfs\content\StringBasedFileContent.
 *
 * @since  1.3.0
 * @group  issue_79
 */
class StringBasedFileContentTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  StringBasedFileContent
     */
    private $stringBasedFileContent;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->stringBasedFileContent = new StringBasedFileContent('foobarbaz');
    }

    /**
     * @test
     */
    public function hasContentOriginallySet()
    {
        $this->assertEquals('foobarbaz', $this->stringBasedFileContent->content());
    }

    /**
     * @test
     */
    public function hasNotReachedEofAfterCreation()
    {
        $this->assertFalse($this->stringBasedFileContent->eof());
    }

    /**
     * @test
     */
    public function sizeEqualsLengthOfGivenString()
    {
        $this->assertEquals(9, $this->stringBasedFileContent->size());
    }

    /**
     * @test
     */
    public function readReturnsSubstringWithRequestedLength()
    {
        $this->assertEquals('foo', $this->stringBasedFileContent->read(3));
    }

    /**
     * @test
     */
    public function readMovesOffset()
    {
        $this->assertEquals('foo', $this->stringBasedFileContent->read(3));
        $this->assertEquals('bar', $this->stringBasedFileContent->read(3));
        $this->assertEquals('baz', $this->stringBasedFileContent->read(3));
    }

    /**
     * @test
     */
    public function reaMoreThanSizeReturnsWholeContent()
    {
        $this->assertEquals('foobarbaz', $this->stringBasedFileContent->read(10));
    }

    /**
     * @test
     */
    public function readAfterEndReturnsEmptyString()
    {
        $this->stringBasedFileContent->read(9);
        $this->assertEquals('', $this->stringBasedFileContent->read(3));
    }

    /**
     * @test
     */
    public function readDoesNotChangeSize()
    {
        $this->stringBasedFileContent->read(3);
        $this->assertEquals(9, $this->stringBasedFileContent->size());
    }

    /**
     * @test
     */
    public function readLessThenSizeDoesNotReachEof()
    {
        $this->stringBasedFileContent->read(3);
        $this->assertFalse($this->stringBasedFileContent->eof());
    }

    /**
     * @test
     */
    public function readSizeReachesEof()
    {
        $this->stringBasedFileContent->read(9);
        $this->assertTrue($this->stringBasedFileContent->eof());
    }

    /**
     * @test
     */
    public function readMoreThanSizeReachesEof()
    {
        $this->stringBasedFileContent->read(10);
        $this->assertTrue($this->stringBasedFileContent->eof());
    }

    /**
     * @test
     */
    public function seekWithInvalidOptionReturnsFalse()
    {
        $this->assertFalse($this->stringBasedFileContent->seek(0, 55));
    }

    /**
     * @test
     */
    public function canSeekToGivenOffset()
    {
        $this->assertTrue($this->stringBasedFileContent->seek(5, SEEK_SET));
        $this->assertEquals('rbaz', $this->stringBasedFileContent->read(10));
    }

    /**
     * @test
     */
    public function canSeekFromCurrentOffset()
    {
        $this->assertTrue($this->stringBasedFileContent->seek(5, SEEK_SET));
        $this->assertTrue($this->stringBasedFileContent->seek(2, SEEK_CUR));
        $this->assertEquals('az', $this->stringBasedFileContent->read(10));
    }

    /**
     * @test
     */
    public function canSeekToEnd()
    {
        $this->assertTrue($this->stringBasedFileContent->seek(0, SEEK_END));
        $this->assertEquals('', $this->stringBasedFileContent->read(10));
    }

    /**
     * @test
     */
    public function writeOverwritesExistingContentWhenOffsetNotAtEof()
    {
        $this->assertEquals(3, $this->stringBasedFileContent->write('bar'));
        $this->assertEquals('barbarbaz', $this->stringBasedFileContent->content());
    }

    /**
     * @test
     */
    public function writeAppendsContentWhenOffsetAtEof()
    {
        $this->assertTrue($this->stringBasedFileContent->seek(0, SEEK_END));
        $this->assertEquals(3, $this->stringBasedFileContent->write('bar'));
        $this->assertEquals('foobarbazbar', $this->stringBasedFileContent->content());
    }

    /**
     * @test
     * @group  issue_33
     * @since  1.1.0
     */
    public function truncateRemovesSuperflouosContent()
    {
        $this->assertTrue($this->stringBasedFileContent->truncate(6));
        $this->assertEquals('foobar', $this->stringBasedFileContent->content());
    }

    /**
     * @test
     * @group  issue_33
     * @since  1.1.0
     */
    public function truncateDecreasesSize()
    {
        $this->assertTrue($this->stringBasedFileContent->truncate(6));
        $this->assertEquals(6, $this->stringBasedFileContent->size());
    }

    /**
     * @test
     * @group  issue_33
     * @since  1.1.0
     */
    public function truncateToGreaterSizeAddsZeroBytes()
    {
        $this->assertTrue($this->stringBasedFileContent->truncate(25));
        $this->assertEquals(
                "foobarbaz\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0",
                $this->stringBasedFileContent->content()
        );
    }

    /**
     * @test
     * @group  issue_33
     * @since  1.1.0
     */
    public function truncateToGreaterSizeIncreasesSize()
    {
        $this->assertTrue($this->stringBasedFileContent->truncate(25));
        $this->assertEquals(25, $this->stringBasedFileContent->size());
    }
}
