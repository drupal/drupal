<?php

namespace Drupal\Tests\system\Functional\Render;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional test verifying that messages set in placeholders always appear.
 *
 * @group Render
 */
class PlaceholderMessageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['render_placeholder_message_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests rendering of message placeholder.
   */
  public function testMessagePlaceholder() {
    $messages_markup = '<div role="contentinfo" aria-label="Status message"';

    $test_routes = [
      // Messages placeholder rendered first.
      'render_placeholder_message_test.first',
      // Messages placeholder rendered after one, before another.
      'render_placeholder_message_test.middle',
      // Messages placeholder rendered last.
      'render_placeholder_message_test.last',
    ];

    $assert = $this->assertSession();
    foreach ($test_routes as $route) {
      // Verify that we start off with zero messages queued.
      $this->drupalGet(Url::fromRoute('render_placeholder_message_test.queued'));
      $assert->responseNotContains($messages_markup);

      // Verify the test case at this route behaves as expected.
      $this->drupalGet(Url::fromRoute($route));
      $assert->elementContains('css', 'p.logged-message:nth-of-type(1)', 'Message: P1');
      $assert->elementContains('css', 'p.logged-message:nth-of-type(2)', 'Message: P2');
      $assert->responseContains($messages_markup);
      $assert->elementExists('css', 'div[aria-label="Status message"] ul');
      $assert->elementContains('css', 'div[aria-label="Status message"] ul li:nth-of-type(1)', 'P1');
      $assert->elementContains('css', 'div[aria-label="Status message"] ul li:nth-of-type(2)', 'P2');

      // Verify that we end with all messages printed, hence again zero queued.
      $this->drupalGet(Url::fromRoute('render_placeholder_message_test.queued'));
      $assert->responseNotContains($messages_markup);
    }
  }

}
