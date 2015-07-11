<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\SafeMarkupTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Tests\UnitTestCase;

/**
 * Tests marking strings as safe.
 *
 * @group Utility
 * @coversDefaultClass \Drupal\Component\Utility\SafeMarkup
 */
class SafeMarkupTest extends UnitTestCase {

  /**
   * Tests SafeMarkup::set() and SafeMarkup::isSafe().
   *
   * @dataProvider providerSet
   *
   * @param string $text
   *   The text or object to provide to SafeMarkup::set().
   * @param string $message
   *   The message to provide as output for the test.
   *
   * @covers ::set
   */
  public function testSet($text, $message) {
    $returned = SafeMarkup::set($text);
    $this->assertTrue(is_string($returned), 'The return value of SafeMarkup::set() is really a string');
    $this->assertEquals($returned, $text, 'The passed in value should be equal to the string value according to PHP');
    $this->assertTrue(SafeMarkup::isSafe($text), $message);
    $this->assertTrue(SafeMarkup::isSafe($returned), 'The return value has been marked as safe');
  }

  /**
   * Data provider for testSet().
   *
   * @see testSet()
   */
  public function providerSet() {
    // Checks that invalid multi-byte sequences are rejected.
    $tests[] = array("Foo\xC0barbaz", '', 'SafeMarkup::checkPlain() rejects invalid sequence "Foo\xC0barbaz"', TRUE);
    $tests[] = array("Fooÿñ", 'SafeMarkup::set() accepts valid sequence "Fooÿñ"');
    $tests[] = array(new TextWrapper("Fooÿñ"), 'SafeMarkup::set() accepts valid sequence "Fooÿñ" in an object implementing __toString()');
    $tests[] = array("<div>", 'SafeMarkup::set() accepts HTML');

    return $tests;
  }

  /**
   * Tests SafeMarkup::set() and SafeMarkup::isSafe() with different providers.
   *
   * @covers ::isSafe
   */
  public function testStrategy() {
    $returned = SafeMarkup::set('string0', 'html');
    $this->assertTrue(SafeMarkup::isSafe($returned), 'String set with "html" provider is safe for default (html)');
    $returned = SafeMarkup::set('string1', 'all');
    $this->assertTrue(SafeMarkup::isSafe($returned), 'String set with "all" provider is safe for default (html)');
    $returned = SafeMarkup::set('string2', 'css');
    $this->assertFalse(SafeMarkup::isSafe($returned), 'String set with "css" provider is not safe for default (html)');
    $returned = SafeMarkup::set('string3');
    $this->assertFalse(SafeMarkup::isSafe($returned, 'all'), 'String set with "html" provider is not safe for "all"');
  }

  /**
   * Tests SafeMarkup::isSafe() with different objects.
   *
   * @covers ::isSafe
   */
  public function testIsSafe() {
    $safe_string = $this->getMock('\Drupal\Component\Utility\SafeStringInterface');
    $this->assertTrue(SafeMarkup::isSafe($safe_string));
    $string_object = new SafeMarkupTestString('test');
    $this->assertFalse(SafeMarkup::isSafe($string_object));
  }

  /**
   * Tests SafeMarkup::setMultiple().
   *
   * @covers ::setMultiple
   */
  public function testSetMultiple() {
    $texts = array(
      'multistring0' => array('html' => TRUE),
      'multistring1' => array('all' => TRUE),
    );
    SafeMarkup::setMultiple($texts);
    foreach ($texts as $string => $providers) {
      $this->assertTrue(SafeMarkup::isSafe($string), 'The value has been marked as safe for html');
    }
  }

  /**
   * Tests SafeMarkup::setMultiple().
   *
   * Only TRUE may be passed in as the value.
   *
   * @covers ::setMultiple
   *
   * @expectedException \UnexpectedValueException
   */
  public function testInvalidSetMultiple() {
    $texts = array(
      'invalidstring0' => array('html' => 1),
    );
    SafeMarkup::setMultiple($texts);
  }

  /**
   * Tests SafeMarkup::checkPlain().
   *
   * @dataProvider providerCheckPlain
   * @covers ::checkPlain
   *
   * @param string $text
   *   The text to provide to SafeMarkup::checkPlain().
   * @param string $expected
   *   The expected output from the function.
   * @param string $message
   *   The message to provide as output for the test.
   * @param bool $ignorewarnings
   *   Whether or not to ignore PHP 5.3+ invalid multibyte sequence warnings.
   */
  function testCheckPlain($text, $expected, $message, $ignorewarnings = FALSE) {
    $result = $ignorewarnings ? @SafeMarkup::checkPlain($text) : SafeMarkup::checkPlain($text);
    $this->assertEquals($expected, $result, $message);
  }

  /**
   * Data provider for testCheckPlain().
   *
   * @see testCheckPlain()
   */
  function providerCheckPlain() {
    // Checks that invalid multi-byte sequences are rejected.
    $tests[] = array("Foo\xC0barbaz", '', 'SafeMarkup::checkPlain() rejects invalid sequence "Foo\xC0barbaz"', TRUE);
    $tests[] = array("\xc2\"", '', 'SafeMarkup::checkPlain() rejects invalid sequence "\xc2\""', TRUE);
    $tests[] = array("Fooÿñ", "Fooÿñ", 'SafeMarkup::checkPlain() accepts valid sequence "Fooÿñ"');

    // Checks that special characters are escaped.
    $tests[] = array("<script>", '&lt;script&gt;', 'SafeMarkup::checkPlain() escapes &lt;script&gt;');
    $tests[] = array('<>&"\'', '&lt;&gt;&amp;&quot;&#039;', 'SafeMarkup::checkPlain() escapes reserved HTML characters.');

    return $tests;
  }

  /**
   * Tests string formatting with SafeMarkup::format().
   *
   * @dataProvider providerFormat
   * @covers ::format
   *
   * @param string $string
   *   The string to run through SafeMarkup::format().
   * @param string $args
   *   The arguments to pass into SafeMarkup::format().
   * @param string $expected
   *   The expected result from calling the function.
   * @param string $message
   *   The message to display as output to the test.
   * @param bool $expected_is_safe
   *   Whether the result is expected to be safe for HTML display.
   */
  function testFormat($string, $args, $expected, $message, $expected_is_safe) {
    $result = SafeMarkup::format($string, $args);
    $this->assertEquals($expected, $result, $message);
    $this->assertEquals($expected_is_safe, SafeMarkup::isSafe($result), 'SafeMarkup::format correctly sets the result as safe or not safe.');
  }

  /**
   * Data provider for testFormat().
   *
   * @see testFormat()
   */
  function providerFormat() {
    $tests[] = array('Simple text', array(), 'Simple text', 'SafeMarkup::format leaves simple text alone.', TRUE);
    $tests[] = array('Escaped text: @value', array('@value' => '<script>'), 'Escaped text: &lt;script&gt;', 'SafeMarkup::format replaces and escapes string.', TRUE);
    $tests[] = array('Escaped text: @value', array('@value' => SafeMarkup::set('<span>Safe HTML</span>')), 'Escaped text: <span>Safe HTML</span>', 'SafeMarkup::format does not escape an already safe string.', TRUE);
    $tests[] = array('Placeholder text: %value', array('%value' => '<script>'), 'Placeholder text: <em class="placeholder">&lt;script&gt;</em>', 'SafeMarkup::format replaces, escapes and themes string.', TRUE);
    $tests[] = array('Placeholder text: %value', array('%value' => SafeMarkup::set('<span>Safe HTML</span>')), 'Placeholder text: <em class="placeholder"><span>Safe HTML</span></em>', 'SafeMarkup::format does not escape an already safe string themed as a placeholder.', TRUE);
    $tests[] = array('Verbatim text: !value', array('!value' => '<script>'), 'Verbatim text: <script>', 'SafeMarkup::format replaces verbatim string as-is.', FALSE);
    $tests[] = array('Verbatim text: !value', array('!value' => SafeMarkup::set('<span>Safe HTML</span>')), 'Verbatim text: <span>Safe HTML</span>', 'SafeMarkup::format replaces verbatim string as-is.', TRUE);

    return $tests;
  }

  /**
   * Tests SafeMarkup::placeholder().
   *
   * @covers ::placeholder
   */
  function testPlaceholder() {
    $this->assertEquals('<em class="placeholder">Some text</em>', SafeMarkup::placeholder('Some text'));
  }

  /**
   * Tests SafeMarkup::replace().
   *
   * @dataProvider providerReplace
   * @covers ::replace
   */
  public function testReplace($search, $replace, $subject, $expected, $is_safe) {
    $result = SafeMarkup::replace($search, $replace, $subject);
    $this->assertEquals($expected, $result);
    $this->assertEquals($is_safe, SafeMarkup::isSafe($result));
  }

  /**
   * Tests the interaction between the safe list and XSS filtering.
   *
   * @covers ::xssFilter
   * @covers ::escape
   */
  public function testAdminXss() {
    // Use the predefined XSS admin tag list. This strips the <marquee> tags.
    $this->assertEquals('text', SafeMarkup::xssFilter('<marquee>text</marquee>', Xss::getAdminTagList()));
    $this->assertTrue(SafeMarkup::isSafe('text'), 'The string \'text\' is marked as safe.');

    // This won't strip the <marquee> tags and the string with HTML will be
    // marked as safe.
    $filtered = SafeMarkup::xssFilter('<marquee>text</marquee>', array('marquee'));
    $this->assertEquals('<marquee>text</marquee>', $filtered);
    $this->assertTrue(SafeMarkup::isSafe('<marquee>text</marquee>'), 'The string \'<marquee>text</marquee>\' is marked as safe.');

    // SafeMarkup::xssFilter() with the default tag list will strip the
    // <marquee> tag even though the string was marked safe above.
    $this->assertEquals('text', SafeMarkup::xssFilter('<marquee>text</marquee>'));

    // SafeMarkup::escape() will not escape the markup tag since the string was
    // marked safe above.
    $this->assertEquals('<marquee>text</marquee>', SafeMarkup::escape($filtered));

    // SafeMarkup::checkPlain() will escape the markup tag even though the
    // string was marked safe above.
    $this->assertEquals('&lt;marquee&gt;text&lt;/marquee&gt;', SafeMarkup::checkPlain($filtered));

    // Ensure that SafeMarkup::xssFilter strips all tags when passed an empty
    // array and uses the default tag list when not passed a tag list.
    $this->assertEquals('text', SafeMarkup::xssFilter('<em>text</em>', []));
    $this->assertEquals('<em>text</em>', SafeMarkup::xssFilter('<em>text</em>'));
  }

  /**
   * Data provider for testReplace().
   *
   * @see testReplace()
   */
  public function providerReplace() {
    $tests = [];

    // Subject unsafe.
    $tests[] = [
      '<placeholder>',
      SafeMarkup::set('foo'),
      '<placeholder>bazqux',
      'foobazqux',
      FALSE,
    ];

    // All safe.
    $tests[] = [
      '<placeholder>',
      SafeMarkup::set('foo'),
      SafeMarkup::set('<placeholder>barbaz'),
      'foobarbaz',
      TRUE,
    ];

    // Safe subject, but should result in unsafe string because replacement is
    // unsafe.
    $tests[] = [
      '<placeholder>',
      'fubar',
      SafeMarkup::set('<placeholder>barbaz'),
      'fubarbarbaz',
      FALSE,
    ];

    // Array with all safe.
    $tests[] = [
      ['<placeholder1>', '<placeholder2>', '<placeholder3>'],
      [SafeMarkup::set('foo'), SafeMarkup::set('bar'), SafeMarkup::set('baz')],
      SafeMarkup::set('<placeholder1><placeholder2><placeholder3>'),
      'foobarbaz',
      TRUE,
    ];

    // Array with unsafe replacement.
    $tests[] = [
      ['<placeholder1>', '<placeholder2>', '<placeholder3>',],
      [SafeMarkup::set('bar'), SafeMarkup::set('baz'), 'qux'],
      SafeMarkup::set('<placeholder1><placeholder2><placeholder3>'),
      'barbazqux',
      FALSE,
    ];

    return $tests;
  }

}

class SafeMarkupTestString {

  protected $string;

  public function __construct($string) {
    $this->string = $string;
  }

  public function __toString() {
    return $this->string;
  }

}
