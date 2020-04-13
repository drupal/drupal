<?php

namespace Drupal\KernelTests\Core\Common;

use Drupal\Component\Utility\UrlHelper;
use Drupal\KernelTests\KernelTestBase;

/**
 * Confirm that \Drupal\Component\Utility\Xss::filter() and check_url() work
 * correctly, including invalid multi-byte sequences.
 *
 * @group Common
 */
class XssUnitTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['filter', 'system'];

  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Tests t() functionality.
   */
  public function testT() {
    $text = t('Simple text');
    $this->assertEqual($text, 'Simple text', 't leaves simple text alone.');
    $text = t('Escaped text: @value', ['@value' => '<script>']);
    $this->assertEqual($text, 'Escaped text: &lt;script&gt;', 't replaces and escapes string.');
    $text = t('Placeholder text: %value', ['%value' => '<script>']);
    $this->assertEqual($text, 'Placeholder text: <em class="placeholder">&lt;script&gt;</em>', 't replaces, escapes and themes string.');
  }

  /**
   * Checks that harmful protocols are stripped.
   */
  public function testBadProtocolStripping() {
    // Ensure that check_url() strips out harmful protocols, and encodes for
    // HTML.
    // Ensure \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols() can
    // be used to return a plain-text string stripped of harmful protocols.
    $url = 'javascript:http://www.example.com/?x=1&y=2';
    $expected_plain = 'http://www.example.com/?x=1&y=2';
    $expected_html = 'http://www.example.com/?x=1&amp;y=2';
    $this->assertIdentical(UrlHelper::filterBadProtocol($url), $expected_html, '\Drupal\Component\Utility\UrlHelper::filterBadProtocol() filters a URL and encodes it for HTML.');
    $this->assertIdentical(UrlHelper::stripDangerousProtocols($url), $expected_plain, '\Drupal\Component\Utility\UrlHelper::stripDangerousProtocols() filters a URL and returns plain text.');

  }

}
