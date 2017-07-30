<?php

namespace Drupal\Tests\Component\Render;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Component\Render\MarkupInterface;
use PHPUnit\Framework\TestCase;

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
    $escapeable_string = new HtmlEscapedText($text);
    $this->assertEquals($expected, (string) $escapeable_string, $message);
    $this->assertEquals($expected, $escapeable_string->jsonSerialize());
  }

  /**
   * Data provider for testToString().
   *
   * @see testToString()
   */
  public function providerToString() {
    // Checks that invalid multi-byte sequences are escaped.
    $tests[] = ["Foo\xC0barbaz", 'Foo�barbaz', 'Escapes invalid sequence "Foo\xC0barbaz"'];
    $tests[] = ["\xc2\"", '�&quot;', 'Escapes invalid sequence "\xc2\""'];
    $tests[] = ["Fooÿñ", "Fooÿñ", 'Does not escape valid sequence "Fooÿñ"'];

    // Checks that special characters are escaped.
    $script_tag = $this->prophesize(MarkupInterface::class);
    $script_tag->__toString()->willReturn('<script>');
    $script_tag = $script_tag->reveal();
    $tests[] = [$script_tag, '&lt;script&gt;', 'Escapes &lt;script&gt; even inside an object that implements MarkupInterface.'];
    $tests[] = ["<script>", '&lt;script&gt;', 'Escapes &lt;script&gt;'];
    $tests[] = ['<>&"\'', '&lt;&gt;&amp;&quot;&#039;', 'Escapes reserved HTML characters.'];
    $specialchars = $this->prophesize(MarkupInterface::class);
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
    $escapeable_string = new HtmlEscapedText($string);
    $this->assertEquals(strlen($string), $escapeable_string->count());
  }

}
