<?php

namespace Drupal\KernelTests\Core\Common;

use Drupal\Component\Utility\UrlHelper;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests XSS filtering.
 *
 * @see \Drupal\Component\Utility\Xss::filter()
 * @see \Drupal\Component\Utility\UrlHelper::filterBadProtocol
 * @see \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols
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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Tests t() functionality.
   */
  public function testT() {
    $text = t('Simple text');
    $this->assertEquals('Simple text', $text, 't leaves simple text alone.');
    $text = t('Escaped text: @value', ['@value' => '<script>']);
    $this->assertEquals('Escaped text: &lt;script&gt;', $text, 't replaces and escapes string.');
    $text = t('Placeholder text: %value', ['%value' => '<script>']);
    $this->assertEquals('Placeholder text: <em class="placeholder">&lt;script&gt;</em>', $text, 't replaces, escapes and themes string.');
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
    $this->assertSame($expected_html, UrlHelper::filterBadProtocol($url), '\\Drupal\\Component\\Utility\\UrlHelper::filterBadProtocol() filters a URL and encodes it for HTML.');
    $this->assertSame($expected_plain, UrlHelper::stripDangerousProtocols($url), '\\Drupal\\Component\\Utility\\UrlHelper::stripDangerousProtocols() filters a URL and returns plain text.');

  }

}
