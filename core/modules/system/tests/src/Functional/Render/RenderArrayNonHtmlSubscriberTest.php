<?php

namespace Drupal\Tests\system\Functional\Render;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional test verifying that render array throws 406 for non-HTML requests.
 *
 * @group Render
 */
class RenderArrayNonHtmlSubscriberTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['render_array_non_html_subscriber_test'];

  /**
   * Tests handling of responses by events subscriber.
   */
  public function testResponses() {
    // Test that event subscriber does not interfere with normal requests.
    $url = Url::fromRoute('render_array_non_html_subscriber_test.render_array');

    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertRaw(t('Controller response successfully rendered.'));

    // Test that correct response code is returned for any non-HTML format.
    foreach (['json', 'hal+json', 'xml', 'foo'] as $format) {
      $url = Url::fromRoute('render_array_non_html_subscriber_test.render_array', [
        '_format' => $format,
      ]);

      $this->drupalGet($url);
      $this->assertSession()->statusCodeEquals(406);
      $this->assertNoRaw(t('Controller response successfully rendered.'));
    }

    // Test that event subscriber does not interfere with raw string responses.
    $url = Url::fromRoute('render_array_non_html_subscriber_test.raw_string', [
      '_format' => 'foo',
    ]);

    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertRaw(t('Raw controller response.'));
  }

}
