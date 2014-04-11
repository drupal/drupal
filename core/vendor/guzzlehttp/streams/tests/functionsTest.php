<?php

namespace GuzzleHttp\Stream;

use GuzzleHttp\Stream;

class functionsTest extends \PHPUnit_Framework_TestCase
{
    public function testCopiesToMemory()
    {
        $s = Stream\create('foobaz');
        $this->assertEquals('foobaz', Stream\copy_to_string($s));
        $s->seek(0);
        $this->assertEquals('foo', Stream\copy_to_string($s, 3));
        $this->assertEquals('baz', Stream\copy_to_string($s, 3));
        $this->assertEquals('', Stream\copy_to_string($s));
    }

    public function testCopiesToStream()
    {
        $s1 = Stream\create('foobaz');
        $s2 = Stream\create('');
        Stream\copy_to_stream($s1, $s2);
        $this->assertEquals('foobaz', (string) $s2);
        $s2 = Stream\create('');
        $s1->seek(0);
        Stream\copy_to_stream($s1, $s2, 3);
        $this->assertEquals('foo', (string) $s2);
        Stream\copy_to_stream($s1, $s2, 3);
        $this->assertEquals('foobaz', (string) $s2);
    }

    public function testReadsLines()
    {
        $s = Stream\create("foo\nbaz\nbar");
        $this->assertEquals("foo\n", Stream\read_line($s));
        $this->assertEquals("baz\n", Stream\read_line($s));
        $this->assertEquals("bar", Stream\read_line($s));
    }

    public function testReadsLinesUpToMaxLength()
    {
        $s = Stream\create("12345\n");
        $this->assertEquals("123", Stream\read_line($s, 4));
        $this->assertEquals("45\n", Stream\read_line($s));
    }

    public function testReadsLineUntilFalseReturnedFromRead()
    {
        $s = $this->getMockBuilder('GuzzleHttp\Stream\Stream')
            ->setMethods(['read', 'eof'])
            ->disableOriginalConstructor()
            ->getMock();
        $s->expects($this->exactly(2))
            ->method('read')
            ->will($this->returnCallback(function () {
                static $c = false;
                if ($c) {
                    return false;
                }
                $c = true;
                return 'h';
            }));
        $s->expects($this->exactly(2))
            ->method('eof')
            ->will($this->returnValue(false));
        $this->assertEquals("h", Stream\read_line($s));
    }

    public function testCalculatesHash()
    {
        $s = Stream\create('foobazbar');
        $this->assertEquals(md5('foobazbar'), Stream\hash($s, 'md5'));
    }

    public function testCalculatesHashSeeksToOriginalPosition()
    {
        $s = Stream\create('foobazbar');
        $s->seek(4);
        $this->assertEquals(md5('foobazbar'), Stream\hash($s, 'md5'));
        $this->assertEquals(4, $s->tell());
    }

    public function testFactoryCreatesFromEmptyString()
    {
        $s = Stream\create();
        $this->assertInstanceOf('GuzzleHttp\Stream\Stream', $s);
    }

    public function testFactoryCreatesFromResource()
    {
        $r = fopen(__FILE__, 'r');
        $s = Stream\create($r);
        $this->assertInstanceOf('GuzzleHttp\Stream\Stream', $s);
        $this->assertSame(file_get_contents(__FILE__), (string) $s);
    }

    public function testFactoryCreatesFromObjectWithToString()
    {
        $r = new HasToString();
        $s = Stream\create($r);
        $this->assertInstanceOf('GuzzleHttp\Stream\Stream', $s);
        $this->assertEquals('foo', (string) $s);
    }

    public function testCreatePassesThrough()
    {
        $s = Stream\create('foo');
        $this->assertSame($s, Stream\create($s));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionForUnknown()
    {
        Stream\create(new \stdClass());
    }
}

class HasToString
{
    public function __toString() {
        return 'foo';
    }
}
