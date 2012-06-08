<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\XssUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests for check_plain(), filter_xss(), format_string(), and check_url().
 */
class XssUnitTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'String filtering tests',
      'description' => 'Confirm that check_plain(), filter_xss(), format_string() and check_url() work correctly, including invalid multi-byte sequences.',
      'group' => 'Common',
    );
  }

  /**
   * Check that invalid multi-byte sequences are rejected.
   */
  function testInvalidMultiByte() {
     // Ignore PHP 5.3+ invalid multibyte sequence warning.
     $text = @check_plain("Foo\xC0barbaz");
     $this->assertEqual($text, '', 'check_plain() rejects invalid sequence "Foo\xC0barbaz"');
     // Ignore PHP 5.3+ invalid multibyte sequence warning.
     $text = @check_plain("\xc2\"");
     $this->assertEqual($text, '', 'check_plain() rejects invalid sequence "\xc2\""');
     $text = check_plain("Fooÿñ");
     $this->assertEqual($text, "Fooÿñ", 'check_plain() accepts valid sequence "Fooÿñ"');
     $text = filter_xss("Foo\xC0barbaz");
     $this->assertEqual($text, '', 'filter_xss() rejects invalid sequence "Foo\xC0barbaz"');
     $text = filter_xss("Fooÿñ");
     $this->assertEqual($text, "Fooÿñ", 'filter_xss() accepts valid sequence Fooÿñ');
  }

  /**
   * Check that special characters are escaped.
   */
  function testEscaping() {
     $text = check_plain("<script>");
     $this->assertEqual($text, '&lt;script&gt;', 'check_plain() escapes &lt;script&gt;');
     $text = check_plain('<>&"\'');
     $this->assertEqual($text, '&lt;&gt;&amp;&quot;&#039;', 'check_plain() escapes reserved HTML characters.');
  }

  /**
   * Test t() and format_string() replacement functionality.
   */
  function testFormatStringAndT() {
    foreach (array('format_string', 't') as $function) {
      $text = $function('Simple text');
      $this->assertEqual($text, 'Simple text', $function . ' leaves simple text alone.');
      $text = $function('Escaped text: @value', array('@value' => '<script>'));
      $this->assertEqual($text, 'Escaped text: &lt;script&gt;', $function . ' replaces and escapes string.');
      $text = $function('Placeholder text: %value', array('%value' => '<script>'));
      $this->assertEqual($text, 'Placeholder text: <em class="placeholder">&lt;script&gt;</em>', $function . ' replaces, escapes and themes string.');
      $text = $function('Verbatim text: !value', array('!value' => '<script>'));
      $this->assertEqual($text, 'Verbatim text: <script>', $function . ' replaces verbatim string as-is.');
    }
  }

  /**
   * Check that harmful protocols are stripped.
   */
  function testBadProtocolStripping() {
    // Ensure that check_url() strips out harmful protocols, and encodes for
    // HTML. Ensure drupal_strip_dangerous_protocols() can be used to return a
    // plain-text string stripped of harmful protocols.
    $url = 'javascript:http://www.example.com/?x=1&y=2';
    $expected_plain = 'http://www.example.com/?x=1&y=2';
    $expected_html = 'http://www.example.com/?x=1&amp;y=2';
    $this->assertIdentical(check_url($url), $expected_html, t('check_url() filters a URL and encodes it for HTML.'));
    $this->assertIdentical(drupal_strip_dangerous_protocols($url), $expected_plain, t('drupal_strip_dangerous_protocols() filters a URL and returns plain text.'));
  }
}
