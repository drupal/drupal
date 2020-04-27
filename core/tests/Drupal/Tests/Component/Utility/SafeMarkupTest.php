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
use PHPUnit\Framework\TestCase;

/**
 * Tests marking strings as safe.
 *
 * @group Utility
 * @group legacy
 * @coversDefaultClass \Drupal\Component\Utility\SafeMarkup
 */
class SafeMarkupTest extends TestCase {

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
   * @expectedDeprecation SafeMarkup::isSafe() is scheduled for removal in Drupal 9.0.0. Instead, you should just check if a variable is an instance of \Drupal\Component\Render\MarkupInterface. See https://www.drupal.org/node/2549395.
   */
  public function testIsSafe() {
    $safe_string = $this->getMockBuilder('\Drupal\Component\Render\MarkupInterface')->getMock();
    $this->assertTrue(SafeMarkup::isSafe($safe_string));
    $string_object = new SafeMarkupTestString('test');
    $this->assertFalse(SafeMarkup::isSafe($string_object));
  }

  /**
   * Tests SafeMarkup::checkPlain().
   *
   * @dataProvider providerCheckPlain
   * @covers ::checkPlain
   * @expectedDeprecation SafeMarkup::checkPlain() is scheduled for removal in Drupal 9.0.0. Rely on Twig's auto-escaping feature, or use the @link theme_render #plain_text @endlink key when constructing a render array that contains plain text in order to use the renderer's auto-escaping feature. If neither of these are possible, \Drupal\Component\Utility\Html::escape() can be used in places where explicit escaping is needed. See https://www.drupal.org/node/2549395.
   *
   * @param string $text
   *   The text to provide to SafeMarkup::checkPlain().
   * @param string $expected
   *   The expected output from the function.
   * @param string $message
   *   The message to provide as output for the test.
   */
  public function testCheckPlain($text, $expected, $message) {
    $result = SafeMarkup::checkPlain($text);
    $this->assertInstanceOf(HtmlEscapedText::class, $result);
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
  public function testHtmlEscapedText($text, $expected, $message) {
    $result = new HtmlEscapedText($text);
    $this->assertEquals($expected, $result, $message);
  }

  /**
   * Data provider for testCheckPlain() and testHtmlEscapedText().
   *
   * @see testCheckPlain()
   * @see testHtmlEscapedText()
   */
  public function providerCheckPlain() {
    // Checks that invalid multi-byte sequences are escaped.
    $tests[] = ["Foo\xC0barbaz", 'Foo�barbaz', 'Escapes invalid sequence "Foo\xC0barbaz"'];
    $tests[] = ["\xc2\"", '�&quot;', 'Escapes invalid sequence "\xc2\""'];
    $tests[] = ["Fooÿñ", "Fooÿñ", 'Does not escape valid sequence "Fooÿñ"'];

    // Checks that special characters are escaped.
    $tests[] = [SafeMarkupTestMarkup::create("<script>"), '&lt;script&gt;', 'Escapes &lt;script&gt; even inside an object that implements MarkupInterface.'];
    $tests[] = ["<script>", '&lt;script&gt;', 'Escapes &lt;script&gt;'];
    $tests[] = ['<>&"\'', '&lt;&gt;&amp;&quot;&#039;', 'Escapes reserved HTML characters.'];
    $tests[] = [SafeMarkupTestMarkup::create('<>&"\''), '&lt;&gt;&amp;&quot;&#039;', 'Escapes reserved HTML characters even inside an object that implements MarkupInterface.'];

    return $tests;
  }

  /**
   * Tests string formatting with SafeMarkup::format().
   *
   * @dataProvider providerFormat
   * @covers ::format
   * @expectedDeprecation SafeMarkup::format() is scheduled for removal in Drupal 9.0.0. Use \Drupal\Component\Render\FormattableMarkup. See https://www.drupal.org/node/2549395.
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
    $this->assertEquals($expected, (string) $result, $message);
    if ($expected_is_safe) {
      $this->assertInstanceOf(MarkupInterface::class, $result);
    }
    else {
      $this->assertNotInstanceOf(MarkupInterface::class, $result);
    }
  }

  /**
   * Data provider for testFormat().
   *
   * @see testFormat()
   */
  public function providerFormat() {
    $tests[] = ['Simple text', [], 'Simple text', 'SafeMarkup::format leaves simple text alone.', TRUE];
    $tests[] = ['Escaped text: @value', ['@value' => '<script>'], 'Escaped text: &lt;script&gt;', 'SafeMarkup::format replaces and escapes string.', TRUE];
    $tests[] = ['Escaped text: @value', ['@value' => SafeMarkupTestMarkup::create('<span>Safe HTML</span>')], 'Escaped text: <span>Safe HTML</span>', 'SafeMarkup::format does not escape an already safe string.', TRUE];
    $tests[] = ['Placeholder text: %value', ['%value' => '<script>'], 'Placeholder text: <em class="placeholder">&lt;script&gt;</em>', 'SafeMarkup::format replaces, escapes and themes string.', TRUE];
    $tests[] = ['Placeholder text: %value', ['%value' => SafeMarkupTestMarkup::create('<span>Safe HTML</span>')], 'Placeholder text: <em class="placeholder"><span>Safe HTML</span></em>', 'SafeMarkup::format does not escape an already safe string themed as a placeholder.', TRUE];

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

    // Tests non-standard placeholders that will not replace.
    $tests['non-standard-placeholder'] = ['Hey hey', ['risky' => "<script>alert('foo');</script>"], 'Hey hey', '', TRUE];
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
 * Marks an object's __toString() method as returning markup.
 */
class SafeMarkupTestMarkup implements MarkupInterface {
  use MarkupTrait;

}
