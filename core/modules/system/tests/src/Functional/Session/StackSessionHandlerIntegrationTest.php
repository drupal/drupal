<?php

namespace Drupal\Tests\system\Functional\Session;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the stacked session handler functionality.
 *
 * @group Session
 */
class StackSessionHandlerIntegrationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['session_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests a request.
   */
  public function testRequest() {
    $options['query'][MainContentViewSubscriber::WRAPPER_FORMAT] = 'drupal_ajax';
    $headers[] = 'X-Requested-With: XMLHttpRequest';
    $actual_trace = json_decode($this->drupalGet('session-test/trace-handler', $options, $headers));
    $sessionId = $this->getSessionCookies()->getCookieByName($this->getSessionName())->getValue();
    $expect_trace = [
      ['BEGIN', 'test_argument', 'open'],
      ['BEGIN', NULL, 'open'],
      ['END', NULL, 'open'],
      ['END', 'test_argument', 'open'],
      ['BEGIN', 'test_argument', 'read', $sessionId],
      ['BEGIN', NULL, 'read', $sessionId],
      ['END', NULL, 'read', $sessionId],
      ['END', 'test_argument', 'read', $sessionId],
      ['BEGIN', 'test_argument', 'write', $sessionId],
      ['BEGIN', NULL, 'write', $sessionId],
      ['END', NULL, 'write', $sessionId],
      ['END', 'test_argument', 'write', $sessionId],
      ['BEGIN', 'test_argument', 'close'],
      ['BEGIN', NULL, 'close'],
      ['END', NULL, 'close'],
      ['END', 'test_argument', 'close'],
    ];
    $this->assertEquals($expect_trace, $actual_trace);
  }

}
