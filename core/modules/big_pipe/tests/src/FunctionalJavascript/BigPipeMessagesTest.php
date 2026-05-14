<?php

declare(strict_types=1);

namespace Drupal\Tests\big_pipe\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests rendering of the messages placeholder via BigPipe.
 */
#[Group('big_pipe')]
#[RunTestsInSeparateProcesses]
class BigPipeMessagesTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'big_pipe',
    'big_pipe_messages_test',
    'render_placeholder_message_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'big_pipe_test_theme';

  /**
   * Ensure messages set in placeholders always appear.
   *
   * @see https://www.drupal.org/node/2712935
   */
  public function testMessages(): void {
    $this->drupalLogin($this->drupalCreateUser());
    $messages_markup = '<div class="messages messages--status" role="status"';
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
      $assert->elementExists('css', 'div[aria-label="Status message"]');
      $assert->responseContains('aria-label="Status message">P1');
      $assert->responseContains('aria-label="Status message">P2');

      // Verify that we end with all messages printed, hence again zero queued.
      $this->drupalGet(Url::fromRoute('render_placeholder_message_test.queued'));
      $assert->responseNotContains($messages_markup);
    }
  }

}
