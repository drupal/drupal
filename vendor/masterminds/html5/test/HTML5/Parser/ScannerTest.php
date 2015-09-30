<?php
/**
 * @file
 * Test the Scanner. This requires the InputStream tests are all good.
 */
namespace Masterminds\HTML5\Tests\Parser;

use Masterminds\HTML5\Parser\StringInputStream;
use Masterminds\HTML5\Parser\Scanner;

class ScannerTest extends \Masterminds\HTML5\Tests\TestCase
{

    /**
     * A canary test to make sure the basics are setup and working.
     */
    public function testConstruct()
    {
        $is = new StringInputStream("abc");
        $s = new Scanner($is);

        $this->assertInstanceOf('\Masterminds\HTML5\Parser\Scanner', $s);
    }

    public function testNext()
    {
        $s = new Scanner(new StringInputStream("abc"));

        $this->assertEquals('b', $s->next());
        $this->assertEquals('c', $s->next());
    }

    public function testPosition()
    {
        $s = new Scanner(new StringInputStream("abc"));

        $this->assertEquals(0, $s->position());

        $s->next();
        $this->assertEquals(1, $s->position());
    }

    public function testPeek()
    {
        $s = new Scanner(new StringInputStream("abc"));

        $this->assertEquals('b', $s->peek());

        $s->next();
        $this->assertEquals('c', $s->peek());
    }

    public function testCurrent()
    {
        $s = new Scanner(new StringInputStream("abc"));

        // Before scanning the string begins the current is empty.
        $this->assertEquals('a', $s->current());

        $c = $s->next();
        $this->assertEquals('b', $s->current());

        // Test movement through the string.
        $c = $s->next();
        $this->assertEquals('c', $s->current());
    }

    public function testUnconsume()
    {
        $s = new Scanner(new StringInputStream("abcdefghijklmnopqrst"));

        // Get initial position.
        $s->next();
        $start = $s->position();

        // Move forward a bunch of positions.
        $amount = 7;
        for ($i = 0; $i < $amount; $i ++) {
            $s->next();
        }

        // Roll back the amount we moved forward.
        $s->unconsume($amount);

        $this->assertEquals($start, $s->position());
    }

    public function testGetHex()
    {
        $s = new Scanner(new StringInputStream("ab13ck45DE*"));

        $this->assertEquals('ab13c', $s->getHex());

        $s->next();
        $this->assertEquals('45DE', $s->getHex());
    }

    public function testGetAsciiAlpha()
    {
        $s = new Scanner(new StringInputStream("abcdef1%mnop*"));

        $this->assertEquals('abcdef', $s->getAsciiAlpha());

        // Move past the 1% to scan the next group of text.
        $s->next();
        $s->next();
        $this->assertEquals('mnop', $s->getAsciiAlpha());
    }

    public function testGetAsciiAlphaNum()
    {
        $s = new Scanner(new StringInputStream("abcdef1ghpo#mn94op"));

        $this->assertEquals('abcdef1ghpo', $s->getAsciiAlphaNum());

        // Move past the # to scan the next group of text.
        $s->next();
        $this->assertEquals('mn94op', $s->getAsciiAlphaNum());
    }

    public function testGetNumeric()
    {
        $s = new Scanner(new StringInputStream("1784a 45 9867 #"));

        $this->assertEquals('1784', $s->getNumeric());

        // Move past the 'a ' to scan the next group of text.
        $s->next();
        $s->next();
        $this->assertEquals('45', $s->getNumeric());
    }

    public function testCurrentLine()
    {
        $s = new Scanner(new StringInputStream("1784a\n45\n9867 #\nThis is a test."));

        $this->assertEquals(1, $s->currentLine());

        // Move to the next line.
        $s->getAsciiAlphaNum();
        $s->next();
        $this->assertEquals(2, $s->currentLine());
    }

    public function testColumnOffset()
    {
        $s = new Scanner(new StringInputStream("1784a a\n45 9867 #\nThis is a test."));

        // Move the pointer to the space.
        $s->getAsciiAlphaNum();
        $this->assertEquals(5, $s->columnOffset());

        // We move the pointer ahead. There must be a better way to do this.
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $s->next();
        $this->assertEquals(3, $s->columnOffset());
    }

    public function testRemainingChars()
    {
        $string = "\n45\n9867 #\nThis is a test.";
        $s = new Scanner(new StringInputStream("1784a\n45\n9867 #\nThis is a test."));

        $s->getAsciiAlphaNum();
        $this->assertEquals($string, $s->remainingChars());
    }
}
