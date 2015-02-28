<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\StringTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\Component\Utility\String;

/**
 * Tests string filtering.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\String
 */
class StringTest extends UnitTestCase {

  /**
   * Tests String::checkPlain().
   *
   * @dataProvider providerCheckPlain
   * @covers ::checkPlain
   *
   * @param string $text
   *   The text to provide to String::checkPlain().
   * @param string $expected
   *   The expected output from the function.
   * @param string $message
   *   The message to provide as output for the test.
   * @param bool $ignorewarnings
   *   Whether or not to ignore PHP 5.3+ invalid multibyte sequence warnings.
   */
  function testCheckPlain($text, $expected, $message, $ignorewarnings = FALSE) {
    $result = $ignorewarnings ? @String::checkPlain($text) : String::checkPlain($text);
    $this->assertEquals($expected, $result, $message);
  }

  /**
   * Data provider for testCheckPlain().
   *
   * @see testCheckPlain()
   */
  function providerCheckPlain() {
    // Checks that invalid multi-byte sequences are rejected.
    $tests[] = array("Foo\xC0barbaz", '', 'String::checkPlain() rejects invalid sequence "Foo\xC0barbaz"', TRUE);
    $tests[] = array("\xc2\"", '', 'String::checkPlain() rejects invalid sequence "\xc2\""', TRUE);
    $tests[] = array("Fooÿñ", "Fooÿñ", 'String::checkPlain() accepts valid sequence "Fooÿñ"');

    // Checks that special characters are escaped.
    $tests[] = array("<script>", '&lt;script&gt;', 'String::checkPlain() escapes &lt;script&gt;');
    $tests[] = array('<>&"\'', '&lt;&gt;&amp;&quot;&#039;', 'String::checkPlain() escapes reserved HTML characters.');

    return $tests;
  }

  /**
   * Tests string formatting with String::format().
   *
   * @dataProvider providerFormat
   * @covers ::format
   *
   * @param string $string
   *   The string to run through String::format().
   * @param string $args
   *   The arguments to pass into String::format().
   * @param string $expected
   *   The expected result from calling the function.
   * @param string $message
   *   The message to display as output to the test.
   * @param bool $expected_is_safe
   *   Whether the result is expected to be safe for HTML display.
   */
  function testFormat($string, $args, $expected, $message, $expected_is_safe) {
    $result = String::format($string, $args);
    $this->assertEquals($expected, $result, $message);
    $this->assertEquals($expected_is_safe, SafeMarkup::isSafe($result), 'String::format correctly sets the result as safe or not safe.');
  }

  /**
   * Data provider for testFormat().
   *
   * @see testFormat()
   */
  function providerFormat() {
    $tests[] = array('Simple text', array(), 'Simple text', 'String::format leaves simple text alone.', TRUE);
    $tests[] = array('Escaped text: @value', array('@value' => '<script>'), 'Escaped text: &lt;script&gt;', 'String::format replaces and escapes string.', TRUE);
    $tests[] = array('Escaped text: @value', array('@value' => SafeMarkup::set('<span>Safe HTML</span>')), 'Escaped text: <span>Safe HTML</span>', 'String::format does not escape an already safe string.', TRUE);
    $tests[] = array('Placeholder text: %value', array('%value' => '<script>'), 'Placeholder text: <em class="placeholder">&lt;script&gt;</em>', 'String::format replaces, escapes and themes string.', TRUE);
    $tests[] = array('Placeholder text: %value', array('%value' => SafeMarkup::set('<span>Safe HTML</span>')), 'Placeholder text: <em class="placeholder"><span>Safe HTML</span></em>', 'String::format does not escape an already safe string themed as a placeholder.', TRUE);
    $tests[] = array('Verbatim text: !value', array('!value' => '<script>'), 'Verbatim text: <script>', 'String::format replaces verbatim string as-is.', FALSE);
    $tests[] = array('Verbatim text: !value', array('!value' => SafeMarkup::set('<span>Safe HTML</span>')), 'Verbatim text: <span>Safe HTML</span>', 'String::format replaces verbatim string as-is.', TRUE);

    return $tests;
  }

  /**
   * Tests String::placeholder().
   *
   * @covers ::placeholder
   */
  function testPlaceholder() {
    $this->assertEquals('<em class="placeholder">Some text</em>', String::placeholder('Some text'));
  }

  /**
   * Tests String::decodeEntities().
   *
   * @dataProvider providerDecodeEntities
   * @covers ::decodeEntities
   */
  public function testDecodeEntities($text, $expected) {
    $this->assertEquals($expected, String::decodeEntities($text));
  }

  /**
   * Data provider for testDecodeEntities().
   *
   * @see testCheckPlain()
   */
  public function providerDecodeEntities() {
    return array(
      array('Drupal', 'Drupal'),
      array('<script>', '<script>'),
      array('&lt;script&gt;', '<script>'),
      array('&#60;script&#62;', '<script>'),
      array('&amp;lt;script&amp;gt;', '&lt;script&gt;'),
      array('"', '"'),
      array('&#34;', '"'),
      array('&amp;#34;', '&#34;'),
      array('&quot;', '"'),
      array('&amp;quot;', '&quot;'),
      array("'", "'"),
      array('&#39;', "'"),
      array('&amp;#39;', '&#39;'),
      array('©', '©'),
      array('&copy;', '©'),
      array('&#169;', '©'),
      array('→', '→'),
      array('&#8594;', '→'),
      array('➼', '➼'),
      array('&#10172;', '➼'),
      array('&euro;', '€'),
    );
  }

}
