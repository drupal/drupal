<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Tests\UnitTestCase;

/**
 * XSS Filtering tests.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Xss
 *
 * Script injection vectors mostly adopted from http://ha.ckers.org/xss.html.
 *
 * Relevant CVEs:
 * - CVE-2002-1806, ~CVE-2005-0682, ~CVE-2005-2106, CVE-2005-3973,
 *   CVE-2006-1226 (= rev. 1.112?), CVE-2008-0273, CVE-2008-3740.
 */
class XssTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $allowed_protocols = [
      'http',
      'https',
      'ftp',
      'news',
      'nntp',
      'telnet',
      'mailto',
      'irc',
      'ssh',
      'sftp',
      'webcal',
      'rtsp',
    ];
    UrlHelper::setAllowedProtocols($allowed_protocols);
  }

  /**
   * Tests limiting allowed tags and XSS prevention.
   *
   * XSS tests assume that script is disallowed by default and src is allowed
   * by default, but on* and style attributes are disallowed.
   *
   * @param string $value
   *   The value to filter.
   * @param string $expected
   *   The expected result.
   * @param string $message
   *   The assertion message to display upon failure.
   * @param array $allowed_tags
   *   (optional) The allowed HTML tags to be passed to \Drupal\Component\Utility\Xss::filter().
   *
   * @dataProvider providerTestFilterXssNormalized
   */
  public function testFilterXssNormalized($value, $expected, $message, array $allowed_tags = NULL) {
    if ($allowed_tags === NULL) {
      $value = Xss::filter($value);
    }
    else {
      $value = Xss::filter($value, $allowed_tags);
    }
    $this->assertNormalized($value, $expected, $message);
  }

  /**
   * Data provider for testFilterXssNormalized().
   *
   * @see testFilterXssNormalized()
   *
   * @return array
   *   An array of arrays containing strings:
   *     - The value to filter.
   *     - The value to expect after filtering.
   *     - The assertion message.
   *     - (optional) The allowed HTML HTML tags array that should be passed to
   *       \Drupal\Component\Utility\Xss::filter().
   */
  public function providerTestFilterXssNormalized() {
    return [
      [
        "Who&#039;s Online",
        "who's online",
        'HTML filter -- html entity number',
      ],
      [
        "Who&amp;#039;s Online",
        "who&#039;s online",
        'HTML filter -- encoded html entity number',
      ],
      [
        "Who&amp;amp;#039; Online",
        "who&amp;#039; online",
        'HTML filter -- double encoded html entity number',
      ],
      // Custom elements with dashes in the tag name.
      [
        "<test-element></test-element>",
        "<test-element></test-element>",
        'Custom element with dashes in tag name.',
        ['test-element'],
      ],
    ];
  }

  /**
   * Tests limiting to allowed tags and XSS prevention.
   *
   * XSS tests assume that script is disallowed by default and src is allowed
   * by default, but on* and style attributes are disallowed.
   *
   * @param string $value
   *   The value to filter.
   * @param string $expected
   *   The string that is expected to be missing.
   * @param string $message
   *   The assertion message to display upon failure.
   * @param array $allowed_tags
   *   (optional) The allowed HTML tags to be passed to \Drupal\Component\Utility\Xss::filter().
   *
   * @dataProvider providerTestFilterXssNotNormalized
   */
  public function testFilterXssNotNormalized($value, $expected, $message, array $allowed_tags = NULL) {
    if ($allowed_tags === NULL) {
      $value = Xss::filter($value);
    }
    else {
      $value = Xss::filter($value, $allowed_tags);
    }
    $this->assertNotNormalized($value, $expected, $message);
  }

  /**
   * Data provider for testFilterXssNotNormalized().
   *
   * @see testFilterXssNotNormalized()
   *
   * @return array
   *   An array of arrays containing the following elements:
   *     - The value to filter.
   *     - The value to expect that's missing after filtering.
   *     - The assertion message.
   *     - (optional) The allowed HTML HTML tags array that should be passed to
   *       \Drupal\Component\Utility\Xss::filter().
   */
  public function providerTestFilterXssNotNormalized() {
    $cases = [
      // Tag stripping, different ways to work around removal of HTML tags.
      [
        '<script>alert(0)</script>',
        'script',
        'HTML tag stripping -- simple script without special characters.',
      ],
      [
        '<script src="http://www.example.com" />',
        'script',
        'HTML tag stripping -- empty script with source.',
      ],
      [
        '<ScRipt sRc=http://www.example.com/>',
        'script',
        'HTML tag stripping evasion -- varying case.',
      ],
      [
        "<script\nsrc\n=\nhttp://www.example.com/\n>",
        'script',
        'HTML tag stripping evasion -- multiline tag.',
      ],
      [
        '<script/a src=http://www.example.com/a.js></script>',
        'script',
        'HTML tag stripping evasion -- non whitespace character after tag name.',
      ],
      [
        '<script/src=http://www.example.com/a.js></script>',
        'script',
        'HTML tag stripping evasion -- no space between tag and attribute.',
      ],
      // Null between < and tag name works at least with IE6.
      [
        "<\0scr\0ipt>alert(0)</script>",
        'ipt',
        'HTML tag stripping evasion -- breaking HTML with nulls.',
      ],
      [
        "<scrscriptipt src=http://www.example.com/a.js>",
        'script',
        'HTML tag stripping evasion -- filter just removing "script".',
      ],
      [
        '<<script>alert(0);//<</script>',
        'script',
        'HTML tag stripping evasion -- double opening brackets.',
      ],
      [
        '<script src=http://www.example.com/a.js?<b>',
        'script',
        'HTML tag stripping evasion -- no closing tag.',
      ],
      // DRUPAL-SA-2008-047: This doesn't seem exploitable, but the filter should
      // work consistently.
      [
        '<script>>',
        'script',
        'HTML tag stripping evasion -- double closing tag.',
      ],
      [
        '<script src=//www.example.com/.a>',
        'script',
        'HTML tag stripping evasion -- no scheme or ending slash.',
      ],
      [
        '<script src=http://www.example.com/.a',
        'script',
        'HTML tag stripping evasion -- no closing bracket.',
      ],
      [
        '<script src=http://www.example.com/ <',
        'script',
        'HTML tag stripping evasion -- opening instead of closing bracket.',
      ],
      [
        '<nosuchtag attribute="newScriptInjectionVector">',
        'nosuchtag',
        'HTML tag stripping evasion -- unknown tag.',
      ],
      [
        '<t:set attributeName="innerHTML" to="&lt;script defer&gt;alert(0)&lt;/script&gt;">',
        't:set',
        'HTML tag stripping evasion -- colon in the tag name (namespaces\' tricks).',
      ],
      [
        '<img """><script>alert(0)</script>',
        'script',
        'HTML tag stripping evasion -- a malformed image tag.',
        ['img'],
      ],
      [
        '<blockquote><script>alert(0)</script></blockquote>',
        'script',
        'HTML tag stripping evasion -- script in a blockqoute.',
        ['blockquote'],
      ],
      [
        "<!--[if true]><script>alert(0)</script><![endif]-->",
        'script',
        'HTML tag stripping evasion -- script within a comment.',
      ],
      // Dangerous attributes removal.
      [
        '<p onmouseover="http://www.example.com/">',
        'onmouseover',
        'HTML filter attributes removal -- events, no evasion.',
        ['p'],
      ],
      [
        '<li style="list-style-image: url(javascript:alert(0))">',
        'style',
        'HTML filter attributes removal -- style, no evasion.',
        ['li'],
      ],
      [
        '<img onerror   =alert(0)>',
        'onerror',
        'HTML filter attributes removal evasion -- spaces before equals sign.',
        ['img'],
      ],
      [
        '<img onabort!#$%&()*~+-_.,:;?@[/|\]^`=alert(0)>',
        'onabort',
        'HTML filter attributes removal evasion -- non alphanumeric characters before equals sign.',
        ['img'],
      ],
      [
        '<img oNmediAError=alert(0)>',
        'onmediaerror',
        'HTML filter attributes removal evasion -- varying case.',
        ['img'],
      ],
      // Works at least with IE6.
      [
        "<img o\0nfocus\0=alert(0)>",
        'focus',
        'HTML filter attributes removal evasion -- breaking with nulls.',
        ['img'],
      ],
      // Only whitelisted scheme names allowed in attributes.
      [
        '<img src="javascript:alert(0)">',
        'javascript',
        'HTML scheme clearing -- no evasion.',
        ['img'],
      ],
      [
        '<img src=javascript:alert(0)>',
        'javascript',
        'HTML scheme clearing evasion -- no quotes.',
        ['img'],
      ],
      // A bit like CVE-2006-0070.
      [
        '<img src="javascript:confirm(0)">',
        'javascript',
        'HTML scheme clearing evasion -- no alert ;)',
        ['img'],
      ],
      [
        '<img src=`javascript:alert(0)`>',
        'javascript',
        'HTML scheme clearing evasion -- grave accents.',
        ['img'],
      ],
      [
        '<img dynsrc="javascript:alert(0)">',
        'javascript',
        'HTML scheme clearing -- rare attribute.',
        ['img'],
      ],
      [
        '<table background="javascript:alert(0)">',
        'javascript',
        'HTML scheme clearing -- another tag.',
        ['table'],
      ],
      [
        '<base href="javascript:alert(0);//">',
        'javascript',
        'HTML scheme clearing -- one more attribute and tag.',
        ['base'],
      ],
      [
        '<img src="jaVaSCriPt:alert(0)">',
        'javascript',
        'HTML scheme clearing evasion -- varying case.',
        ['img'],
      ],
      [
        '<img src=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#48;&#41;>',
        'javascript',
        'HTML scheme clearing evasion -- UTF-8 decimal encoding.',
        ['img'],
      ],
      [
        '<img src=&#00000106&#0000097&#00000118&#0000097&#00000115&#0000099&#00000114&#00000105&#00000112&#00000116&#0000058&#0000097&#00000108&#00000101&#00000114&#00000116&#0000040&#0000048&#0000041>',
        'javascript',
        'HTML scheme clearing evasion -- long UTF-8 encoding.',
        ['img'],
      ],
      [
        '<img src=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x30&#x29>',
        'javascript',
        'HTML scheme clearing evasion -- UTF-8 hex encoding.',
        ['img'],
      ],
      [
        "<img src=\"jav\tascript:alert(0)\">",
        'script',
        'HTML scheme clearing evasion -- an embedded tab.',
        ['img'],
      ],
      [
        '<img src="jav&#x09;ascript:alert(0)">',
        'script',
        'HTML scheme clearing evasion -- an encoded, embedded tab.',
        ['img'],
      ],
      [
        '<img src="jav&#x000000A;ascript:alert(0)">',
        'script',
        'HTML scheme clearing evasion -- an encoded, embedded newline.',
        ['img'],
      ],
      // With &#xD; this test would fail, but the entity gets turned into
      // &amp;#xD;, so it's OK.
      [
        '<img src="jav&#x0D;ascript:alert(0)">',
        'script',
        'HTML scheme clearing evasion -- an encoded, embedded carriage return.',
        ['img'],
      ],
      [
        "<img src=\"\n\n\nj\na\nva\ns\ncript:alert(0)\">",
        'cript',
        'HTML scheme clearing evasion -- broken into many lines.',
        ['img'],
      ],
      [
        "<img src=\"jav\0a\0\0cript:alert(0)\">",
        'cript',
        'HTML scheme clearing evasion -- embedded nulls.',
        ['img'],
      ],
      [
        '<img src="vbscript:msgbox(0)">',
        'vbscript',
        'HTML scheme clearing evasion -- another scheme.',
        ['img'],
      ],
      [
        '<img src="nosuchscheme:notice(0)">',
        'nosuchscheme',
        'HTML scheme clearing evasion -- unknown scheme.',
        ['img'],
      ],
      // Netscape 4.x javascript entities.
      [
        '<br size="&{alert(0)}">',
        'alert',
        'Netscape 4.x javascript entities.',
        ['br'],
      ],
      // DRUPAL-SA-2008-006: Invalid UTF-8, these only work as reflected XSS with
      // Internet Explorer 6.
      [
        "<p arg=\"\xe0\">\" style=\"background-image: url(javascript:alert(0));\"\xe0<p>",
        'style',
        'HTML filter -- invalid UTF-8.',
        ['p'],
      ],
    ];
    // @fixme This dataset currently fails under 5.4 because of
    //   https://www.drupal.org/node/1210798. Restore after its fixed.
    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
      $cases[] = [
        '<img src=" &#14;  javascript:alert(0)">',
        'javascript',
        'HTML scheme clearing evasion -- spaces and metacharacters before scheme.',
        ['img'],
      ];
    }
    return $cases;
  }

  /**
   * Checks that invalid multi-byte sequences are rejected.
   *
   * @param string $value
   *   The value to filter.
   * @param string $expected
   *   The expected result.
   * @param string $message
   *   The assertion message to display upon failure.
   *
   * @dataProvider providerTestInvalidMultiByte
   */
  public function testInvalidMultiByte($value, $expected, $message) {
    $this->assertEquals(Xss::filter($value), $expected, $message);
  }

  /**
   * Data provider for testInvalidMultiByte().
   *
   * @see testInvalidMultiByte()
   *
   * @return array
   *   An array of arrays containing strings:
   *     - The value to filter.
   *     - The value to expect after filtering.
   *     - The assertion message.
   */
  public function providerTestInvalidMultiByte() {
    return [
      ["Foo\xC0barbaz", '', 'Xss::filter() accepted invalid sequence "Foo\xC0barbaz"'],
      ["Fooÿñ", "Fooÿñ", 'Xss::filter() rejects valid sequence Fooÿñ"'],
      ["\xc0aaa", '', 'HTML filter -- overlong UTF-8 sequences.'],
    ];
  }

  /**
   * Checks that strings starting with a question sign are correctly processed.
   */
  public function testQuestionSign() {
    $value = Xss::filter('<?xml:namespace ns="urn:schemas-microsoft-com:time">');
    $this->assertTrue(stripos($value, '<?xml') === FALSE, 'HTML tag stripping evasion -- starting with a question sign (processing instructions).');
  }

  /**
   * Check that strings in HTML attributes are correctly processed.
   *
   * @covers ::attributes
   * @dataProvider providerTestAttributes
   */
  public function testAttribute($value, $expected, $message, $allowed_tags = NULL) {
    $value = Xss::filter($value, $allowed_tags);
    $this->assertEquals($expected, $value, $message);
  }

  /**
   * Data provider for testFilterXssAdminNotNormalized().
   */
  public function providerTestAttributes() {
    return [
      [
        '<img src="http://example.com/foo.jpg" title="Example: title" alt="Example: alt">',
        '<img src="http://example.com/foo.jpg" title="Example: title" alt="Example: alt">',
        'Image tag with alt and title attribute',
        ['img']
      ],
      [
        '<a href="https://www.drupal.org/" rel="dc:publisher">Drupal</a>',
        '<a href="https://www.drupal.org/" rel="dc:publisher">Drupal</a>',
        'Link tag with rel attribute',
        ['a']
      ],
      [
        '<span property="dc:subject">Drupal 8: The best release ever.</span>',
        '<span property="dc:subject">Drupal 8: The best release ever.</span>',
        'Span tag with property attribute',
        ['span']
      ],
      [
        '<img src="http://example.com/foo.jpg" data-caption="Drupal 8: The best release ever.">',
        '<img src="http://example.com/foo.jpg" data-caption="Drupal 8: The best release ever.">',
        'Image tag with data attribute',
        ['img']
      ],
      [
        '<a data-a2a-url="foo"></a>',
        '<a data-a2a-url="foo"></a>',
        'Link tag with numeric data attribute',
        ['a']
      ],
    ];
  }

  /**
   * Checks that \Drupal\Component\Utility\Xss::filterAdmin() correctly strips unallowed tags.
   */
  public function testFilterXSSAdmin() {
    $value = Xss::filterAdmin('<style /><iframe /><frame /><frameset /><meta /><link /><embed /><applet /><param /><layer />');
    $this->assertEquals($value, '', 'Admin HTML filter -- should never allow some tags.');
  }

  /**
   * Tests the loose, admin HTML filter.
   *
   * @param string $value
   *   The value to filter.
   * @param string $expected
   *   The expected result.
   * @param string $message
   *   The assertion message to display upon failure.
   *
   * @dataProvider providerTestFilterXssAdminNotNormalized
   */
  public function testFilterXssAdminNotNormalized($value, $expected, $message) {
    $this->assertNotNormalized(Xss::filterAdmin($value), $expected, $message);
  }

  /**
   * Data provider for testFilterXssAdminNotNormalized().
   *
   * @see testFilterXssAdminNotNormalized()
   *
   * @return array
   *   An array of arrays containing strings:
   *     - The value to filter.
   *     - The value to expect after filtering.
   *     - The assertion message.
   */
  public function providerTestFilterXssAdminNotNormalized() {
    return [
      // DRUPAL-SA-2008-044
      ['<object />', 'object', 'Admin HTML filter -- should not allow object tag.'],
      ['<script />', 'script', 'Admin HTML filter -- should not allow script tag.'],
    ];
  }

  /**
   * Asserts that a text transformed to lowercase with HTML entities decoded does contain a given string.
   *
   * Otherwise fails the test with a given message, similar to all the
   * SimpleTest assert* functions.
   *
   * Note that this does not remove nulls, new lines and other characters that
   * could be used to obscure a tag or an attribute name.
   *
   * @param string $haystack
   *   Text to look in.
   * @param string $needle
   *   Lowercase, plain text to look for.
   * @param string $message
   *   (optional) Message to display if failed. Defaults to an empty string.
   * @param string $group
   *   (optional) The group this message belongs to. Defaults to 'Other'.
   */
  protected function assertNormalized($haystack, $needle, $message = '', $group = 'Other') {
    $this->assertTrue(strpos(strtolower(Html::decodeEntities($haystack)), $needle) !== FALSE, $message, $group);
  }

  /**
   * Asserts that text transformed to lowercase with HTML entities decoded does not contain a given string.
   *
   * Otherwise fails the test with a given message, similar to all the
   * SimpleTest assert* functions.
   *
   * Note that this does not remove nulls, new lines, and other character that
   * could be used to obscure a tag or an attribute name.
   *
   * @param string $haystack
   *   Text to look in.
   * @param string $needle
   *   Lowercase, plain text to look for.
   * @param string $message
   *   (optional) Message to display if failed. Defaults to an empty string.
   * @param string $group
   *   (optional) The group this message belongs to. Defaults to 'Other'.
   */
  protected function assertNotNormalized($haystack, $needle, $message = '', $group = 'Other') {
    $this->assertTrue(strpos(strtolower(Html::decodeEntities($haystack)), $needle) === FALSE, $message, $group);
  }

}
