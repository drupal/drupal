<?php

namespace Drupal\Tests\Component\Render;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Component\Render\MarkupInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophet;

/**
 * Tests the HtmlEscapedText class.
 *
 * @coversDefaultClass \Drupal\Component\Render\HtmlEscapedText
 * @group utility
 */
class HtmlEscapedTextTest extends TestCase {

  /**
   * @covers ::__toString
   * @covers ::jsonSerialize
   *
   * @dataProvider providerToString
   */
  public function testToString($text, $expected, $message) {
    $escapable_string = new HtmlEscapedText($text);
    $this->assertEquals($expected, (string) $escapable_string, $message);
    $this->assertEquals($expected, $escapable_string->jsonSerialize());
  }

  /**
   * Data provider for testToString().
   *
   * @see testToString()
   */
  public static function providerToString() {
    $prophet = new Prophet();

    // Checks that invalid multi-byte sequences are escaped.
    $tests[] = ["Foo\xC0bar", 'Foo�bar', 'Escapes invalid sequence "Foo\xC0bar"'];
    $tests[] = ["\xc2\"", '�&quot;', 'Escapes invalid sequence "\xc2\""'];
    $tests[] = ["Foo ÿñ", "Foo ÿñ", 'Does not escape valid sequence "Foo ÿñ"'];

    // Checks that special characters are escaped.
    $script_tag = $prophet->prophesize(MarkupInterface::class);
    $script_tag->__toString()->willReturn('<script>');
    $script_tag = $script_tag->reveal();
    $tests[] = [$script_tag, '&lt;script&gt;', 'Escapes &lt;script&gt; even inside an object that implements MarkupInterface.'];
    $tests[] = ["<script>", '&lt;script&gt;', 'Escapes &lt;script&gt;'];
    $tests[] = ['<>&"\'', '&lt;&gt;&amp;&quot;&#039;', 'Escapes reserved HTML characters.'];
    $specialchars = $prophet->prophesize(MarkupInterface::class);
    $specialchars->__toString()->willReturn('<>&"\'');
    $specialchars = $specialchars->reveal();
    $tests[] = [$specialchars, '&lt;&gt;&amp;&quot;&#039;', 'Escapes reserved HTML characters even inside an object that implements MarkupInterface.'];

    return $tests;
  }

  /**
   * @covers ::count
   */
  public function testCount() {
    $string = 'Can I please have a <em>kitten</em>';
    $escapable_string = new HtmlEscapedText($string);
    $this->assertEquals(strlen($string), $escapable_string->count());
  }

}
