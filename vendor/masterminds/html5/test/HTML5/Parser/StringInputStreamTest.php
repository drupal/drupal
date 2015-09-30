<?php
namespace Masterminds\HTML5\Tests\Parser;

use Masterminds\HTML5\Parser\StringInputStream;

class StringInputStreamTest extends \Masterminds\HTML5\Tests\TestCase
{

    /**
     * A canary test to make sure the basics are setup and working.
     */
    public function testConstruct()
    {
        $s = new StringInputStream("abc");

        $this->assertInstanceOf('\Masterminds\HTML5\Parser\StringInputStream', $s);
    }

    public function testNext()
    {
        $s = new StringInputStream("abc");

        $s->next();
        $this->assertEquals('b', $s->current());
        $s->next();
        $this->assertEquals('c', $s->current());
    }

    public function testKey()
    {
        $s = new StringInputStream("abc");

        $this->assertEquals(0, $s->key());

        $s->next();
        $this->assertEquals(1, $s->key());
    }

    public function testPeek()
    {
        $s = new StringInputStream("abc");

        $this->assertEquals('b', $s->peek());

        $s->next();
        $this->assertEquals('c', $s->peek());
    }

    public function testCurrent()
    {
        $s = new StringInputStream("abc");

        // Before scanning the string begins the current is empty.
        $this->assertEquals('a', $s->current());

        $s->next();
        $this->assertEquals('b', $s->current());

        // Test movement through the string.
        $s->next();
        $this->assertEquals('c', $s->current());
    }

    public function testColumnOffset()
    {
        $s = new StringInputStream("abc\ndef\n");
        $this->assertEquals(0, $s->columnOffset());
        $s->next();
        $this->assertEquals(1, $s->columnOffset());
        $s->next();
        $this->assertEquals(2, $s->columnOffset());
        $s->next();
        $this->assertEquals(3, $s->columnOffset());
        $s->next(); // LF
        $this->assertEquals(0, $s->columnOffset());
        $s->next();
        $canary = $s->current(); // e
        $this->assertEquals('e', $canary);
        $this->assertEquals(1, $s->columnOffset());

        $s = new StringInputStream("abc");
        $this->assertEquals(0, $s->columnOffset());
        $s->next();
        $this->assertEquals(1, $s->columnOffset());
        $s->next();
        $this->assertEquals(2, $s->columnOffset());
    }

    public function testCurrentLine()
    {
        $txt = "1\n2\n\n\n\n3";
        $stream = new StringInputStream($txt);
        $this->assertEquals(1, $stream->currentLine());

        // Advance over 1 and LF on to line 2 value 2.
        $stream->next();
        $stream->next();
        $canary = $stream->current();
        $this->assertEquals(2, $stream->currentLine());
        $this->assertEquals('2', $canary);

        // Advance over 4x LF
        $stream->next();
        $stream->next();
        $stream->next();
        $stream->next();
        $stream->next();
        $this->assertEquals(6, $stream->currentLine());
        $this->assertEquals('3', $stream->current());

        // Make sure it doesn't do 7.
        $this->assertEquals(6, $stream->currentLine());
    }

    public function testRemainingChars()
    {
        $text = "abcd";
        $s = new StringInputStream($text);
        $this->assertEquals($text, $s->remainingChars());

        $text = "abcd";
        $s = new StringInputStream($text);
        $s->next(); // Pop one.
        $this->assertEquals('bcd', $s->remainingChars());
    }

    public function testCharsUnitl()
    {
        $text = "abcdefffffffghi";
        $s = new StringInputStream($text);
        $this->assertEquals('', $s->charsUntil('a'));
        // Pointer at 'a', moves 2 to 'c'
        $this->assertEquals('ab', $s->charsUntil('w', 2));

        // Pointer at 'c', moves to first 'f'
        $this->assertEquals('cde', $s->charsUntil('fzxv'));

        // Only get five 'f's
        $this->assertEquals('fffff', $s->charsUntil('g', 5));

        // Get just the last two 'f's
        $this->assertEquals('ff', $s->charsUntil('g'));

        // This should scan to the end.
        $this->assertEquals('ghi', $s->charsUntil('w', 9));
    }

    public function testCharsWhile()
    {
        $text = "abcdefffffffghi";
        $s = new StringInputStream($text);

        $this->assertEquals('ab', $s->charsWhile('ba'));

        $this->assertEquals('', $s->charsWhile('a'));
        $this->assertEquals('cde', $s->charsWhile('cdeba'));
        $this->assertEquals('ff', $s->charsWhile('f', 2));
        $this->assertEquals('fffff', $s->charsWhile('f'));
        $this->assertEquals('g', $s->charsWhile('fg'));
        $this->assertEquals('hi', $s->charsWhile('fghi', 99));
    }

    public function testBOM()
    {
        // Ignore in-text BOM.
        $stream = new StringInputStream("a\xEF\xBB\xBF");
        $this->assertEquals("a\xEF\xBB\xBF", $stream->remainingChars(), 'A non-leading U+FEFF (BOM/ZWNBSP) should remain');

        // Strip leading BOM
        $leading = new StringInputStream("\xEF\xBB\xBFa");
        $this->assertEquals('a', $leading->current(), 'BOM should be stripped');
    }

    public function testCarriageReturn()
    {
        // Replace NULL with Unicode replacement.
        $stream = new StringInputStream("\0\0\0");
        $this->assertEquals("\xEF\xBF\xBD\xEF\xBF\xBD\xEF\xBF\xBD", $stream->remainingChars(), 'Null character should be replaced by U+FFFD');
        $this->assertEquals(3, count($stream->errors), 'Null character should set parse error: ' . print_r($stream->errors, true));

        // Remove CR when next to LF.
        $stream = new StringInputStream("\r\n");
        $this->assertEquals("\n", $stream->remainingChars(), 'CRLF should be replaced by LF');

        // Convert CR to LF when on its own.
        $stream = new StringInputStream("\r");
        $this->assertEquals("\n", $stream->remainingChars(), 'CR should be replaced by LF');
    }

    public function invalidParseErrorTestHandler($input, $numErrors, $name)
    {
        $stream = new StringInputStream($input, 'UTF-8');
        $this->assertEquals($input, $stream->remainingChars(), $name . ' (stream content)');
        $this->assertEquals($numErrors, count($stream->errors), $name . ' (number of errors)');
    }

    public function testInvalidReplace()
    {
        $invalidTest = array(

            // Min/max overlong
            "\xC0\x80a" => 'Overlong representation of U+0000',
            "\xE0\x80\x80a" => 'Overlong representation of U+0000',
            "\xF0\x80\x80\x80a" => 'Overlong representation of U+0000',
            "\xF8\x80\x80\x80\x80a" => 'Overlong representation of U+0000',
            "\xFC\x80\x80\x80\x80\x80a" => 'Overlong representation of U+0000',
            "\xC1\xBFa" => 'Overlong representation of U+007F',
            "\xE0\x9F\xBFa" => 'Overlong representation of U+07FF',
            "\xF0\x8F\xBF\xBFa" => 'Overlong representation of U+FFFF',

            "a\xDF" => 'Incomplete two byte sequence (missing final byte)',
            "a\xEF\xBF" => 'Incomplete three byte sequence (missing final byte)',
            "a\xF4\xBF\xBF" => 'Incomplete four byte sequence (missing final byte)',

            // Min/max continuation bytes
            "a\x80" => 'Lone 80 continuation byte',
            "a\xBF" => 'Lone BF continuation byte',

            // Invalid bytes (these can never occur)
            "a\xFE" => 'Invalid FE byte',
            "a\xFF" => 'Invalid FF byte'
        );
        foreach ($invalidTest as $test => $note) {
            $stream = new StringInputStream($test);
            $this->assertEquals('a', $stream->remainingChars(), $note);
        }

        // MPB:
        // It appears that iconv just leaves these alone. Not sure what to
        // do.
        /*
         * $converted = array( "a\xF5\x90\x80\x80" => 'U+110000, off unicode planes.', ); foreach ($converted as $test => $note) { $stream = new StringInputStream($test); $this->assertEquals(2, mb_strlen($stream->remainingChars()), $note); }
         */
    }

    public function testInvalidParseError()
    {
        // C0 controls (except U+0000 and U+000D due to different handling)
        $this->invalidParseErrorTestHandler("\x01", 1, 'U+0001 (C0 control)');
        $this->invalidParseErrorTestHandler("\x02", 1, 'U+0002 (C0 control)');
        $this->invalidParseErrorTestHandler("\x03", 1, 'U+0003 (C0 control)');
        $this->invalidParseErrorTestHandler("\x04", 1, 'U+0004 (C0 control)');
        $this->invalidParseErrorTestHandler("\x05", 1, 'U+0005 (C0 control)');
        $this->invalidParseErrorTestHandler("\x06", 1, 'U+0006 (C0 control)');
        $this->invalidParseErrorTestHandler("\x07", 1, 'U+0007 (C0 control)');
        $this->invalidParseErrorTestHandler("\x08", 1, 'U+0008 (C0 control)');
        $this->invalidParseErrorTestHandler("\x09", 0, 'U+0009 (C0 control)');
        $this->invalidParseErrorTestHandler("\x0A", 0, 'U+000A (C0 control)');
        $this->invalidParseErrorTestHandler("\x0B", 1, 'U+000B (C0 control)');
        $this->invalidParseErrorTestHandler("\x0C", 0, 'U+000C (C0 control)');
        $this->invalidParseErrorTestHandler("\x0E", 1, 'U+000E (C0 control)');
        $this->invalidParseErrorTestHandler("\x0F", 1, 'U+000F (C0 control)');
        $this->invalidParseErrorTestHandler("\x10", 1, 'U+0010 (C0 control)');
        $this->invalidParseErrorTestHandler("\x11", 1, 'U+0011 (C0 control)');
        $this->invalidParseErrorTestHandler("\x12", 1, 'U+0012 (C0 control)');
        $this->invalidParseErrorTestHandler("\x13", 1, 'U+0013 (C0 control)');
        $this->invalidParseErrorTestHandler("\x14", 1, 'U+0014 (C0 control)');
        $this->invalidParseErrorTestHandler("\x15", 1, 'U+0015 (C0 control)');
        $this->invalidParseErrorTestHandler("\x16", 1, 'U+0016 (C0 control)');
        $this->invalidParseErrorTestHandler("\x17", 1, 'U+0017 (C0 control)');
        $this->invalidParseErrorTestHandler("\x18", 1, 'U+0018 (C0 control)');
        $this->invalidParseErrorTestHandler("\x19", 1, 'U+0019 (C0 control)');
        $this->invalidParseErrorTestHandler("\x1A", 1, 'U+001A (C0 control)');
        $this->invalidParseErrorTestHandler("\x1B", 1, 'U+001B (C0 control)');
        $this->invalidParseErrorTestHandler("\x1C", 1, 'U+001C (C0 control)');
        $this->invalidParseErrorTestHandler("\x1D", 1, 'U+001D (C0 control)');
        $this->invalidParseErrorTestHandler("\x1E", 1, 'U+001E (C0 control)');
        $this->invalidParseErrorTestHandler("\x1F", 1, 'U+001F (C0 control)');

        // DEL (U+007F)
        $this->invalidParseErrorTestHandler("\x7F", 1, 'U+007F');

        // C1 Controls
        $this->invalidParseErrorTestHandler("\xC2\x80", 1, 'U+0080 (C1 control)');
        $this->invalidParseErrorTestHandler("\xC2\x9F", 1, 'U+009F (C1 control)');
        $this->invalidParseErrorTestHandler("\xC2\xA0", 0, 'U+00A0 (first codepoint above highest C1 control)');

        // Charcters surrounding surrogates
        $this->invalidParseErrorTestHandler("\xED\x9F\xBF", 0, 'U+D7FF (one codepoint below lowest surrogate codepoint)');
        $this->invalidParseErrorTestHandler("\xEF\xBF\xBD", 0, 'U+DE00 (one codepoint above highest surrogate codepoint)');

        // Permanent noncharacters
        $this->invalidParseErrorTestHandler("\xEF\xB7\x90", 1, 'U+FDD0 (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xEF\xB7\xAF", 1, 'U+FDEF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xEF\xBF\xBE", 1, 'U+FFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xEF\xBF\xBF", 1, 'U+FFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF0\x9F\xBF\xBE", 1, 'U+1FFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF0\x9F\xBF\xBF", 1, 'U+1FFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF0\xAF\xBF\xBE", 1, 'U+2FFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF0\xAF\xBF\xBF", 1, 'U+2FFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF0\xBF\xBF\xBE", 1, 'U+3FFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF0\xBF\xBF\xBF", 1, 'U+3FFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF1\x8F\xBF\xBE", 1, 'U+4FFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF1\x8F\xBF\xBF", 1, 'U+4FFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF1\x9F\xBF\xBE", 1, 'U+5FFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF1\x9F\xBF\xBF", 1, 'U+5FFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF1\xAF\xBF\xBE", 1, 'U+6FFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF1\xAF\xBF\xBF", 1, 'U+6FFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF1\xBF\xBF\xBE", 1, 'U+7FFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF1\xBF\xBF\xBF", 1, 'U+7FFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF2\x8F\xBF\xBE", 1, 'U+8FFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF2\x8F\xBF\xBF", 1, 'U+8FFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF2\x9F\xBF\xBE", 1, 'U+9FFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF2\x9F\xBF\xBF", 1, 'U+9FFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF2\xAF\xBF\xBE", 1, 'U+AFFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF2\xAF\xBF\xBF", 1, 'U+AFFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF2\xBF\xBF\xBE", 1, 'U+BFFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF2\xBF\xBF\xBF", 1, 'U+BFFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF3\x8F\xBF\xBE", 1, 'U+CFFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF3\x8F\xBF\xBF", 1, 'U+CFFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF3\x9F\xBF\xBE", 1, 'U+DFFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF3\x9F\xBF\xBF", 1, 'U+DFFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF3\xAF\xBF\xBE", 1, 'U+EFFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF3\xAF\xBF\xBF", 1, 'U+EFFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF3\xBF\xBF\xBE", 1, 'U+FFFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF3\xBF\xBF\xBF", 1, 'U+FFFFF (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF4\x8F\xBF\xBE", 1, 'U+10FFFE (permanent noncharacter)');
        $this->invalidParseErrorTestHandler("\xF4\x8F\xBF\xBF", 1, 'U+10FFFF (permanent noncharacter)');

        // MPB: These pass on some versions of iconv, and fail on others. Since we aren't in the
        // business of writing tests against iconv, I've just commented these out. Should revisit
        // at a later point.
        /*
         * $this->invalidParseErrorTestHandler("\xED\xA0\x80", 1, 'U+D800 (UTF-16 surrogate character)'); $this->invalidParseErrorTestHandler("\xED\xAD\xBF", 1, 'U+DB7F (UTF-16 surrogate character)'); $this->invalidParseErrorTestHandler("\xED\xAE\x80", 1, 'U+DB80 (UTF-16 surrogate character)'); $this->invalidParseErrorTestHandler("\xED\xAF\xBF", 1, 'U+DBFF (UTF-16 surrogate character)'); $this->invalidParseErrorTestHandler("\xED\xB0\x80", 1, 'U+DC00 (UTF-16 surrogate character)'); $this->invalidParseErrorTestHandler("\xED\xBE\x80", 1, 'U+DF80 (UTF-16 surrogate character)'); $this->invalidParseErrorTestHandler("\xED\xBF\xBF", 1, 'U+DFFF (UTF-16 surrogate character)'); // Paired UTF-16 surrogates $this->invalidParseErrorTestHandler("\xED\xA0\x80\xED\xB0\x80", 2, 'U+D800 U+DC00 (paired UTF-16 surrogates)'); $this->invalidParseErrorTestHandler("\xED\xA0\x80\xED\xBF\xBF", 2, 'U+D800 U+DFFF (paired UTF-16 surrogates)'); $this->invalidParseErrorTestHandler("\xED\xAD\xBF\xED\xB0\x80", 2, 'U+DB7F U+DC00 (paired UTF-16 surrogates)'); $this->invalidParseErrorTestHandler("\xED\xAD\xBF\xED\xBF\xBF", 2, 'U+DB7F U+DFFF (paired UTF-16 surrogates)'); $this->invalidParseErrorTestHandler("\xED\xAE\x80\xED\xB0\x80", 2, 'U+DB80 U+DC00 (paired UTF-16 surrogates)'); $this->invalidParseErrorTestHandler("\xED\xAE\x80\xED\xBF\xBF", 2, 'U+DB80 U+DFFF (paired UTF-16 surrogates)'); $this->invalidParseErrorTestHandler("\xED\xAF\xBF\xED\xB0\x80", 2, 'U+DBFF U+DC00 (paired UTF-16 surrogates)'); $this->invalidParseErrorTestHandler("\xED\xAF\xBF\xED\xBF\xBF", 2, 'U+DBFF U+DFFF (paired UTF-16 surrogates)');
         */
    }
}
