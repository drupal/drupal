<?php

declare(strict_types=1);

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
   * {@inheritdoc}
   */
  protected static $modules = ['render_array_non_html_subscriber_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests handling of responses by events subscriber.
   */
  public function testResponses(): void {
    // Test that event subscriber does not interfere with normal requests.
    $url = Url::fromRoute('render_array_non_html_subscriber_test.render_array');

    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("Controller response successfully rendered.");

    // Test that correct response code is returned for any non-HTML format.
    foreach (['json', 'xml', 'foo'] as $format) {
      $url = Url::fromRoute('render_array_non_html_subscriber_test.render_array', [
        '_format' => $format,
      ]);

      $this->drupalGet($url);
      $this->assertSession()->statusCodeEquals(406);
      $this->assertSession()->pageTextNotContains("Controller response successfully rendered.");
    }

    // Test that event subscriber does not interfere with raw string responses.
    $url = Url::fromRoute('render_array_non_html_subscriber_test.raw_string', [
      '_format' => 'foo',
    ]);

    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains("Raw controller response.");
  }

}
