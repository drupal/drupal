<?php

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests SimpleTest error and exception collector.
 *
 * @group Common
 */
class SimpleTestErrorCollectorTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system_test', 'error_test'];

  /**
   * Errors triggered during the test.
   *
   * Errors are intercepted by the overridden implementation
   * of Drupal\simpletest\WebTestBase::error() below.
   *
   * @var Array
   */
  protected $collectedErrors = [];

  /**
   * Tests that simpletest collects errors from the tested site.
   */
  public function testErrorCollect() {
    $this->collectedErrors = [];
    $this->drupalGet('error-test/generate-warnings-with-report');
    $this->assertEqual(count($this->collectedErrors), 3, 'Three errors were collected');

    if (count($this->collectedErrors) == 3) {
      $this->assertError($this->collectedErrors[0], 'Notice', 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()', 'ErrorTestController.php', 'Undefined variable: bananas');
      $this->assertError($this->collectedErrors[1], 'Warning', 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()', 'ErrorTestController.php', 'Division by zero');
      $this->assertError($this->collectedErrors[2], 'User warning', 'Drupal\error_test\Controller\ErrorTestController->generateWarnings()', 'ErrorTestController.php', 'Drupal &amp; awesome');
    }
    else {
      // Give back the errors to the log report.
      foreach ($this->collectedErrors as $error) {
        parent::error($error['message'], $error['group'], $error['caller']);
      }
    }
  }

  /**
   * Stores errors into an array.
   *
   * This test class is trying to verify that simpletest correctly sees errors
   * and warnings. However, it can't generate errors and warnings that
   * propagate up to the testing framework itself, or these tests would always
   * fail. So, this special copy of error() doesn't propagate the errors up
   * the class hierarchy. It just stuffs them into a protected collectedErrors
   * array for various assertions to inspect.
   */
  protected function error($message = '', $group = 'Other', array $caller = NULL) {
    // Due to a WTF elsewhere, simpletest treats debug() and verbose()
    // messages as if they were an 'error'. But, we don't want to collect
    // those here. This function just wants to collect the real errors (PHP
    // notices, PHP fatal errors, etc.), and let all the 'errors' from the
    // 'User notice' group bubble up to the parent classes to be handled (and
    // eventually displayed) as normal.
    if ($group == 'User notice') {
      parent::error($message, $group, $caller);
    }
    // Everything else should be collected but not propagated.
    else {
      $this->collectedErrors[] = [
        'message' => $message,
        'group' => $group,
        'caller' => $caller
      ];
    }
  }

  /**
   * Asserts that a collected error matches what we are expecting.
   */
  public function assertError($error, $group, $function, $file, $message = NULL) {
    $this->assertEqual($error['group'], $group, format_string("Group was %group", ['%group' => $group]));
    $this->assertEqual($error['caller']['function'], $function, format_string("Function was %function", ['%function' => $function]));
    $this->assertEqual(drupal_basename($error['caller']['file']), $file, format_string("File was %file", ['%file' => $file]));
    if (isset($message)) {
      $this->assertEqual($error['message'], $message, format_string("Message was %message", ['%message' => $message]));
    }
  }

}
