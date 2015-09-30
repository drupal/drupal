<?php
namespace Masterminds\HTML5\Tests\Parser;

use Masterminds\HTML5\Parser\FileInputStream;

class FileInputStreamTest extends \Masterminds\HTML5\Tests\TestCase
{

    public function testConstruct()
    {
        $s = new FileInputStream(__DIR__ . '/FileInputStreamTest.html');

        $this->assertInstanceOf('\Masterminds\HTML5\Parser\FileInputStream', $s);
    }

    public function testNext()
    {
        $s = new FileInputStream(__DIR__ . '/FileInputStreamTest.html');

        $s->next();
        $this->assertEquals('!', $s->current());
        $s->next();
        $this->assertEquals('d', $s->current());
    }

    public function testKey()
    {
        $s = new FileInputStream(__DIR__ . '/FileInputStreamTest.html');

        $this->assertEquals(0, $s->key());

        $s->next();
        $this->assertEquals(1, $s->key());
    }

    public function testPeek()
    {
        $s = new FileInputStream(__DIR__ . '/FileInputStreamTest.html');

        $this->assertEquals('!', $s->peek());

        $s->next();
        $this->assertEquals('d', $s->peek());
    }

    public function testCurrent()
    {
        $s = new FileInputStream(__DIR__ . '/FileInputStreamTest.html');

        $this->assertEquals('<', $s->current());

        $s->next();
        $this->assertEquals('!', $s->current());

        $s->next();
        $this->assertEquals('d', $s->current());
    }

    public function testColumnOffset()
    {
        $s = new FileInputStream(__DIR__ . '/FileInputStreamTest.html');
        $this->assertEquals(0, $s->columnOffset());
        $s->next();
        $this->assertEquals(1, $s->columnOffset());
        $s->next();
        $this->assertEquals(2, $s->columnOffset());
        $s->next();
        $this->assertEquals(3, $s->columnOffset());

        // Make sure we get to the second line
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $this->assertEquals(0, $s->columnOffset());

        $s->next();
        $canary = $s->current(); // h
        $this->assertEquals('h', $canary);
        $this->assertEquals(1, $s->columnOffset());
    }

    public function testCurrentLine()
    {
        $s = new FileInputStream(__DIR__ . '/FileInputStreamTest.html');

        $this->assertEquals(1, $s->currentLine());

        // Make sure we get to the second line
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $this->assertEquals(2, $s->currentLine());

        // Make sure we get to the third line
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $this->assertEquals(3, $s->currentLine());
    }

    public function testRemainingChars()
    {
        $text = file_get_contents(__DIR__ . '/FileInputStreamTest.html');
        $s = new FileInputStream(__DIR__ . '/FileInputStreamTest.html');
        $this->assertEquals($text, $s->remainingChars());

        $text = substr(file_get_contents(__DIR__ . '/FileInputStreamTest.html'), 1);
        $s = new FileInputStream(__DIR__ . '/FileInputStreamTest.html');
        $s->next(); // Pop one.
        $this->assertEquals($text, $s->remainingChars());
    }

    public function testCharsUnitl()
    {
        $s = new FileInputStream(__DIR__ . '/FileInputStreamTest.html');

        $this->assertEquals('', $s->charsUntil('<'));
        // Pointer at '<', moves to ' '
        $this->assertEquals('<!doctype', $s->charsUntil(' ', 20));

        // Pointer at ' ', moves to '>'
        $this->assertEquals(' html', $s->charsUntil('>'));

        // Pointer at '>', moves to '\n'.
        $this->assertEquals('>', $s->charsUntil("\n"));

        // Pointer at '\n', move forward then to the next'\n'.
        $s->next();
        $this->assertEquals('<html lang="en">', $s->charsUntil("\n"));

        // Ony get one of the spaces.
        $this->assertEquals("\n ", $s->charsUntil('<', 2));

        // Get the other space.
        $this->assertEquals(" ", $s->charsUntil('<'));

        // This should scan to the end of the file.
        $text = "<head>
    <meta charset=\"utf-8\">
    <title>Test</title>
  </head>
  <body>
    <p>This is a test.</p>
  </body>
</html>";
        $this->assertEquals($text, $s->charsUntil("\t"));
    }

    public function testCharsWhile()
    {
        $s = new FileInputStream(__DIR__ . '/FileInputStreamTest.html');

        $this->assertEquals('<!', $s->charsWhile('!<'));
        $this->assertEquals('', $s->charsWhile('>'));
        $this->assertEquals('doctype', $s->charsWhile('odcyept'));
        $this->assertEquals(' htm', $s->charsWhile('html ', 4));
    }
}
