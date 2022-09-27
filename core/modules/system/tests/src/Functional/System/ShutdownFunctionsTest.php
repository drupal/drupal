<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests shutdown functions.
 *
 * @group system
 */
class ShutdownFunctionsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // This test intentionally throws an exception in a PHP shutdown function.
    // Prevent it from being interpreted as an actual test failure.
    // Not using File API; a potential error must trigger a PHP warning.
    unlink(\Drupal::root() . '/' . $this->siteDirectory . '/error.log');
    parent::tearDown();
  }

  /**
   * Tests shutdown functions.
   */
  public function testShutdownFunctions() {
    $arg1 = $this->randomMachineName();
    $arg2 = $this->randomMachineName();
    $this->drupalGet('system-test/shutdown-functions/' . $arg1 . '/' . $arg2);

    // If using PHP-FPM then fastcgi_finish_request() will have been fired
    // returning the response before shutdown functions have fired.
    // @see \Drupal\system_test\Controller\SystemTestController::shutdownFunctions()
    $server_using_fastcgi = strpos($this->getSession()->getPage()->getContent(), 'The function fastcgi_finish_request exists when serving the request.');
    if ($server_using_fastcgi) {
      // We need to wait to ensure that the shutdown functions have fired.
      sleep(1);
    }
    $this->assertEquals([$arg1, $arg2], \Drupal::state()->get('_system_test_first_shutdown_function'));
    $this->assertEquals([$arg1, $arg2], \Drupal::state()->get('_system_test_second_shutdown_function'));

    if (!$server_using_fastcgi) {
      // Make sure exceptions displayed through
      // \Drupal\Core\Utility\Error::renderExceptionSafe() are correctly
      // escaped.
      $this->assertSession()->responseContains('Drupal is &lt;blink&gt;awesome&lt;/blink&gt;.');
    }
  }

}
