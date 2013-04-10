<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\StringTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Tests\UnitTestCase;
use Drupal\Component\Utility\String;

/**
 * Tests string filtering.
 *
 * @see \Drupal\Component\Utility\String
 */
class StringTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'String filtering tests',
      'description' => 'Confirm that String::checkPlain() and String::format() work correctly, including invalid multi-byte sequences.',
      'group' => 'Common',
    );
  }

  /**
   * Tests String::checkPlain().
   *
   * @dataProvider providerCheckPlain
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
    $this->assertEquals($result, $expected, $message);
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
   *
   * @param string $string
   *   The string to run through String::format().
   * @param string $args
   *   The arguments to pass into String::format().
   * @param string $expected
   *   The expected result from calling the function.
   * @param string $message
   *   The message to display as output to the test.
   *
   * @see String::format()
   */
  function testFormat($string, $args, $expected, $message) {
    $result = String::format($string, $args);
    $this->assertEquals($result, $expected, $message);
  }

  /**
   * Data provider for testFormat().
   *
   * @see testFormat()
   */
  function providerFormat() {
    $tests[] = array('Simple text', array(), 'Simple text', 'String::format leaves simple text alone.');
    $tests[] = array('Escaped text: @value', array('@value' => '<script>'), 'Escaped text: &lt;script&gt;', 'String::format replaces and escapes string.');
    $tests[] = array('Placeholder text: %value', array('%value' => '<script>'), 'Placeholder text: <em class="placeholder">&lt;script&gt;</em>', 'String::format replaces, escapes and themes string.');
    $tests[] = array('Verbatim text: !value', array('!value' => '<script>'), 'Verbatim text: <script>', 'String::format replaces verbatim string as-is.');

    return $tests;
  }

  /**
   * Tests String::placeholder().
   *
   * @see String::placeholder()
   */
  function testPlaceholder() {
    $this->assertEquals('<em class="placeholder">Some text</em>', String::placeholder('Some text'));
  }

}
