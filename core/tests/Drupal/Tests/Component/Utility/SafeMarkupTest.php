<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\SafeMarkupTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\MarkupTrait;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests marking strings as safe.
 *
 * @group Utility
 * @coversDefaultClass \Drupal\Component\Utility\SafeMarkup
 */
class SafeMarkupTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();

    UrlHelper::setAllowedProtocols(['http', 'https']);
  }


  /**
   * Helper function to add a string to the safe list for testing.
   *
   * @param string $string
   *   The content to be marked as secure.
   * @param string $strategy
   *   The escaping strategy used for this string. Two values are supported
   *   by default:
   *   - 'html': (default) The string is safe for use in HTML code.
   *   - 'all': The string is safe for all use cases.
   *   See the
   *   @link http://twig.sensiolabs.org/doc/filters/escape.html Twig escape documentation @endlink
   *   for more information on escaping strategies in Twig.
   *
   * @return string
   *   The input string that was marked as safe.
   */
  protected function safeMarkupSet($string, $strategy = 'html') {
    $reflected_class = new \ReflectionClass('\Drupal\Component\Utility\SafeMarkup');
    $reflected_property = $reflected_class->getProperty('safeStrings');
    $reflected_property->setAccessible(true);
    $current_value = $reflected_property->getValue();
    $current_value[$string][$strategy] = TRUE;
    $reflected_property->setValue($current_value);
    return $string;
  }

  /**
   * Tests SafeMarkup::isSafe() with different providers.
   *
   * @covers ::isSafe
   */
  public function testStrategy() {
    $returned = $this->safeMarkupSet('string0', 'html');
    $this->assertTrue(SafeMarkup::isSafe($returned), 'String set with "html" provider is safe for default (html)');
    $returned = $this->safeMarkupSet('string1', 'all');
    $this->assertTrue(SafeMarkup::isSafe($returned), 'String set with "all" provider is safe for default (html)');
    $returned = $this->safeMarkupSet('string2', 'css');
    $this->assertFalse(SafeMarkup::isSafe($returned), 'String set with "css" provider is not safe for default (html)');
    $returned = $this->safeMarkupSet('string3');
    $this->assertFalse(SafeMarkup::isSafe($returned, 'all'), 'String set with "html" provider is not safe for "all"');
  }

  /**
   * Data provider for testSet().
   */
  public function providerSet() {
    // Checks that invalid multi-byte sequences are escaped.
    $tests[] = array(
      'Foo�barbaz',
      'SafeMarkup::setMarkup() functions with valid sequence "Foo�barbaz"',
      TRUE
    );
    $tests[] = array(
      "Fooÿñ",
      'SafeMarkup::setMarkup() functions with valid sequence "Fooÿñ"'
    );
    $tests[] = array("<div>", 'SafeMarkup::setMultiple() does not escape HTML');

    return $tests;
  }

  /**
   * Tests SafeMarkup::setMultiple().
   * @dataProvider providerSet
   *
   * @param string $text
   *   The text or object to provide to SafeMarkup::setMultiple().
   * @param string $message
   *   The message to provide as output for the test.
   *
   * @covers ::setMultiple
   */
  public function testSet($text, $message) {
    SafeMarkup::setMultiple([$text => ['html' => TRUE]]);
    $this->assertTrue(SafeMarkup::isSafe($text), $message);
  }

  /**
   * Tests SafeMarkup::isSafe() with different objects.
   *
   * @covers ::isSafe
   */
  public function testIsSafe() {
    $safe_string = $this->getMock('\Drupal\Component\Render\MarkupInterface');
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
    // Checks that invalid multi-byte sequences are escaped.
    $tests[] = array("Foo\xC0barbaz", 'Foo�barbaz', 'SafeMarkup::checkPlain() escapes invalid sequence "Foo\xC0barbaz"', TRUE);
    $tests[] = array("\xc2\"", '�&quot;', 'SafeMarkup::checkPlain() escapes invalid sequence "\xc2\""', TRUE);
    $tests[] = array("Fooÿñ", "Fooÿñ", 'SafeMarkup::checkPlain() does not escape valid sequence "Fooÿñ"');

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
   * @param string[] $args
   *   The arguments to pass into SafeMarkup::format().
   * @param string $expected
   *   The expected result from calling the function.
   * @param string $message
   *   The message to display as output to the test.
   * @param bool $expected_is_safe
   *   Whether the result is expected to be safe for HTML display.
   */
  public function testFormat($string, array $args, $expected, $message, $expected_is_safe) {
    UrlHelper::setAllowedProtocols(['http', 'https', 'mailto']);

    $result = SafeMarkup::format($string, $args);
    $this->assertEquals($expected, $result, $message);
    $this->assertEquals($expected_is_safe, SafeMarkup::isSafe($result), 'SafeMarkup::format correctly sets the result as safe or not safe.');

    foreach ($args as $arg) {
      $this->assertSame($arg instanceof SafeMarkupTestMarkup, SafeMarkup::isSafe($arg));
    }
  }

  /**
   * Data provider for testFormat().
   *
   * @see testFormat()
   */
  function providerFormat() {
    $tests[] = array('Simple text', array(), 'Simple text', 'SafeMarkup::format leaves simple text alone.', TRUE);
    $tests[] = array('Escaped text: @value', array('@value' => '<script>'), 'Escaped text: &lt;script&gt;', 'SafeMarkup::format replaces and escapes string.', TRUE);
    $tests[] = array('Escaped text: @value', array('@value' => SafeMarkupTestMarkup::create('<span>Safe HTML</span>')), 'Escaped text: <span>Safe HTML</span>', 'SafeMarkup::format does not escape an already safe string.', TRUE);
    $tests[] = array('Placeholder text: %value', array('%value' => '<script>'), 'Placeholder text: <em class="placeholder">&lt;script&gt;</em>', 'SafeMarkup::format replaces, escapes and themes string.', TRUE);
    $tests[] = array('Placeholder text: %value', array('%value' => SafeMarkupTestMarkup::create('<span>Safe HTML</span>')), 'Placeholder text: <em class="placeholder"><span>Safe HTML</span></em>', 'SafeMarkup::format does not escape an already safe string themed as a placeholder.', TRUE);

    $tests['javascript-protocol-url'] = ['Simple text <a href=":url">giraffe</a>', [':url' => 'javascript://example.com?foo&bar'], 'Simple text <a href="//example.com?foo&amp;bar">giraffe</a>', 'Support for filtering bad protocols', TRUE];
    $tests['external-url'] = ['Simple text <a href=":url">giraffe</a>', [':url' => 'http://example.com?foo&bar'], 'Simple text <a href="http://example.com?foo&amp;bar">giraffe</a>', 'Support for filtering bad protocols', TRUE];
    $tests['relative-url'] = ['Simple text <a href=":url">giraffe</a>', [':url' => '/node/1?foo&bar'], 'Simple text <a href="/node/1?foo&amp;bar">giraffe</a>', 'Support for filtering bad protocols', TRUE];
    $tests['fragment-with-special-chars'] = ['Simple text <a href=":url">giraffe</a>', [':url' => 'http://example.com/#&lt;'], 'Simple text <a href="http://example.com/#&amp;lt;">giraffe</a>', 'Support for filtering bad protocols', TRUE];
    $tests['mailto-protocol'] = ['Hey giraffe <a href=":url">MUUUH</a>', [':url' => 'mailto:test@example.com'], 'Hey giraffe <a href="mailto:test@example.com">MUUUH</a>', '', TRUE];
    $tests['js-with-fromCharCode'] = ['Hey giraffe <a href=":url">MUUUH</a>', [':url' => "javascript:alert(String.fromCharCode(88,83,83))"], 'Hey giraffe <a href="alert(String.fromCharCode(88,83,83))">MUUUH</a>', '', TRUE];

    // Test some "URL" values that are not RFC 3986 compliant URLs. The result
    // of SafeMarkup::format() should still be valid HTML (other than the
    // value of the "href" attribute not being a valid URL), and not
    // vulnerable to XSS.
    $tests['non-url-with-colon'] = ['Hey giraffe <a href=":url">MUUUH</a>', [':url' => "llamas: they are not URLs"], 'Hey giraffe <a href=" they are not URLs">MUUUH</a>', '', TRUE];
    $tests['non-url-with-html'] = ['Hey giraffe <a href=":url">MUUUH</a>', [':url' => "<span>not a url</span>"], 'Hey giraffe <a href="&lt;span&gt;not a url&lt;/span&gt;">MUUUH</a>', '', TRUE];

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

/**
 * Marks text as safe.
 *
 * SafeMarkupTestMarkup is used to mark text as safe because
 * SafeMarkup::$safeStrings is a global static that affects all tests.
 */
class SafeMarkupTestMarkup implements MarkupInterface {
  use MarkupTrait;
}
