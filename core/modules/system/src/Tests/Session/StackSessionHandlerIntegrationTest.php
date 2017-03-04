<?php

namespace Drupal\system\Tests\Session;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the stacked session handler functionality.
 *
 * @group Session
 */
class StackSessionHandlerIntegrationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['session_test'];

  /**
   * Tests a request.
   */
  public function testRequest() {
    $actual_trace = $this->drupalGetAjax('session-test/trace-handler');
    $expect_trace = [
      ['BEGIN', 'test_argument', 'open'],
      ['BEGIN', NULL, 'open'],
      ['END', NULL, 'open'],
      ['END', 'test_argument', 'open'],
      ['BEGIN', 'test_argument', 'read', $this->sessionId],
      ['BEGIN', NULL, 'read', $this->sessionId],
      ['END', NULL, 'read', $this->sessionId],
      ['END', 'test_argument', 'read', $this->sessionId],
      ['BEGIN', 'test_argument', 'write', $this->sessionId],
      ['BEGIN', NULL, 'write', $this->sessionId],
      ['END', NULL, 'write', $this->sessionId],
      ['END', 'test_argument', 'write', $this->sessionId],
      ['BEGIN', 'test_argument', 'close'],
      ['BEGIN', NULL, 'close'],
      ['END', NULL, 'close'],
      ['END', 'test_argument', 'close'],
    ];
    $this->assertEqual($expect_trace, $actual_trace);
  }

}
