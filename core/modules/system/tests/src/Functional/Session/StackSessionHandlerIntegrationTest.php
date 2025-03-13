<?php

declare(strict_types=1);

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
  public function testRequest(): void {
    $options['query'][MainContentViewSubscriber::WRAPPER_FORMAT] = 'drupal_ajax';
    $headers = ['X-Requested-With' => 'XMLHttpRequest'];
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

  /**
   * Tests a session modify request with a valid session cookie.
   *
   * The trace should include `validateId` because a session cookie is included.
   *
   * The trace should include `write` but not include `updateTimestamp` because
   * the session data is modified.
   */
  public function testRequestWriteInvokesValidateId(): void {
    $options['query'][MainContentViewSubscriber::WRAPPER_FORMAT] = 'drupal_ajax';
    $headers = ['X-Requested-With' => 'XMLHttpRequest'];

    // Call the write trace handler to store the trace and retrieve a session
    // cookie.
    $this->drupalGet('session-test/trace-handler');

    // Call the write trace handler again with the session cookie to modify
    // the session data.
    $actual_trace = json_decode($this->drupalGet('session-test/trace-handler', $options, $headers));
    $sessionId = $this->getSessionCookies()->getCookieByName($this->getSessionName())->getValue();

    $expect_trace = [
      ["BEGIN", "test_argument", "open"],
      ["BEGIN", NULL, "open"],
      ["END", NULL, "open"],
      ["END", "test_argument", "open"],
      ["BEGIN", "test_argument", "validateId", $sessionId],
      ["BEGIN", NULL, "validateId", $sessionId],
      ["END", NULL, "validateId", $sessionId],
      ["END", "test_argument", "validateId", $sessionId],
      ["BEGIN", "test_argument", "read", $sessionId],
      ["BEGIN", NULL, "read", $sessionId],
      ["END", NULL, "read", $sessionId],
      ["END", "test_argument", "read", $sessionId],
      ["BEGIN", "test_argument", "write", $sessionId],
      ["BEGIN", NULL, "write", $sessionId],
      ["END", NULL, "write", $sessionId],
      ["END", "test_argument", "write", $sessionId],
      ["BEGIN", "test_argument", "close"],
      ["BEGIN", NULL, "close"],
      ["END", NULL, "close"],
      ["END", "test_argument", "close"],
    ];
    $this->assertEquals($expect_trace, $actual_trace);
  }

  /**
   * Tests a session rewrite-unmodified request with a valid session cookie.
   *
   * The trace should include `validateId` because a session cookie is included.
   *
   * The trace should include `updateTimestamp` but not include `write` because
   * the session data is rewritten without modification and `session.lazy_write`
   * is enabled.
   */
  public function testRequestWriteInvokesUpdateTimestamp(): void {
    $options['query'][MainContentViewSubscriber::WRAPPER_FORMAT] = 'drupal_ajax';
    $headers = ['X-Requested-With' => 'XMLHttpRequest'];

    // Call the write trace handler to store the trace and retrieve a session
    // cookie.
    $this->drupalGet('session-test/trace-handler');

    // Call the rewrite-unmodified trace handler with the session cookie.
    $actual_trace = json_decode($this->drupalGet('session-test/trace-handler-rewrite-unmodified', $options, $headers));
    $sessionId = $this->getSessionCookies()->getCookieByName($this->getSessionName())->getValue();

    $expect_trace = [
      ["BEGIN", "test_argument", "open"],
      ["BEGIN", NULL, "open"],
      ["END", NULL, "open"],
      ["END", "test_argument", "open"],
      ["BEGIN", "test_argument", "validateId", $sessionId],
      ["BEGIN", NULL, "validateId", $sessionId],
      ["END", NULL, "validateId", $sessionId],
      ["END", "test_argument", "validateId", $sessionId],
      ["BEGIN", "test_argument", "read", $sessionId],
      ["BEGIN", NULL, "read", $sessionId],
      ["END", NULL, "read", $sessionId],
      ["END", "test_argument", "read", $sessionId],
      ["BEGIN", "test_argument", "updateTimestamp", $sessionId],
      ["BEGIN", NULL, "updateTimestamp", $sessionId],
      ["END", NULL, "updateTimestamp", $sessionId],
      ["END", "test_argument", "updateTimestamp", $sessionId],
      ["BEGIN", "test_argument", "close"],
      ["BEGIN", NULL, "close"],
      ["END", NULL, "close"],
      ["END", "test_argument", "close"],
    ];
    $this->assertEquals($expect_trace, $actual_trace);
  }

}
