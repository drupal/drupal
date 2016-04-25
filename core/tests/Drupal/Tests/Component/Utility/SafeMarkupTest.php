<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\SafeMarkupTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Render\HtmlEscapedText;
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
   * The error message of the last error in the error handler.
   *
   * @var string
   */
  protected $lastErrorMessage;

  /**
   * The error number of the last error in the error handler.
   *
   * @var int
   */
  protected $lastErrorNumber;

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();

    UrlHelper::setAllowedProtocols(['http', 'https']);
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
   */
  function testCheckPlain($text, $expected, $message) {
    $result = SafeMarkup::checkPlain($text);
    $this->assertTrue($result instanceof HtmlEscapedText);
    $this->assertEquals($expected, $result, $message);
  }

  /**
   * Tests Drupal\Component\Render\HtmlEscapedText.
   *
   * Verifies that the result of SafeMarkup::checkPlain() is the same as using
   * HtmlEscapedText directly.
   *
   * @dataProvider providerCheckPlain
   *
   * @param string $text
   *   The text to provide to the HtmlEscapedText constructor.
   * @param string $expected
   *   The expected output from the function.
   * @param string $message
   *   The message to provide as output for the test.
   */
  function testHtmlEscapedText($text, $expected, $message) {
    $result = new HtmlEscapedText($text);
    $this->assertEquals($expected, $result, $message);
  }

  /**
   * Data provider for testCheckPlain() and testEscapeString().
   *
   * @see testCheckPlain()
   */
  function providerCheckPlain() {
    // Checks that invalid multi-byte sequences are escaped.
    $tests[] = array("Foo\xC0barbaz", 'Foo�barbaz', 'Escapes invalid sequence "Foo\xC0barbaz"');
    $tests[] = array("\xc2\"", '�&quot;', 'Escapes invalid sequence "\xc2\""');
    $tests[] = array("Fooÿñ", "Fooÿñ", 'Does not escape valid sequence "Fooÿñ"');

    // Checks that special characters are escaped.
    $tests[] = array(SafeMarkupTestMarkup::create("<script>"), '&lt;script&gt;', 'Escapes &lt;script&gt; even inside an object that implements MarkupInterface.');
    $tests[] = array("<script>", '&lt;script&gt;', 'Escapes &lt;script&gt;');
    $tests[] = array('<>&"\'', '&lt;&gt;&amp;&quot;&#039;', 'Escapes reserved HTML characters.');
    $tests[] = array(SafeMarkupTestMarkup::create('<>&"\''), '&lt;&gt;&amp;&quot;&#039;', 'Escapes reserved HTML characters even inside an object that implements MarkupInterface.');

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
    $this->assertEquals($expected_is_safe, $result instanceof MarkupInterface, 'SafeMarkup::format correctly sets the result as safe or not safe.');

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
    /**
   * Custom error handler that saves the last error.
   *
   * We need this custom error handler because we cannot rely on the error to
   * exception conversion as __toString is never allowed to leak any kind of
   * exception.
   *
   * @param int $error_number
   *   The error number.
   * @param string $error_message
   *   The error message.
   */
  public function errorHandler($error_number, $error_message) {
    $this->lastErrorNumber = $error_number;
    $this->lastErrorMessage = $error_message;
  }

  /**
   * String formatting with SafeMarkup::format() and an unsupported placeholder.
   *
   * When you call SafeMarkup::format() with an unsupported placeholder, an
   * InvalidArgumentException should be thrown.
   */
  public function testUnexpectedFormat() {

    // We set a custom error handler because of https://github.com/sebastianbergmann/phpunit/issues/487
    set_error_handler([$this, 'errorHandler']);
    // We want this to trigger an error.
    $error = SafeMarkup::format('Broken placeholder: ~placeholder', ['~placeholder' => 'broken'])->__toString();
    restore_error_handler();

    $this->assertEquals(E_USER_ERROR, $this->lastErrorNumber);
    $this->assertEquals('Invalid placeholder (~placeholder) in string: Broken placeholder: ~placeholder', $this->lastErrorMessage);
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
 * Marks an object's __toString() method as returning markup.
 */
class SafeMarkupTestMarkup implements MarkupInterface {
  use MarkupTrait;
}
