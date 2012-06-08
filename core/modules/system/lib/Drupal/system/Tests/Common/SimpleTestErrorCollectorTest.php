<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\SimpleTestErrorCollectorTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests SimpleTest error and exception collector.
 */
class SimpleTestErrorCollectorTest extends WebTestBase {

  /**
   * Errors triggered during the test.
   *
   * Errors are intercepted by the overriden implementation
   * of Drupal\simpletest\WebTestBase::error() below.
   *
   * @var Array
   */
  protected $collectedErrors = array();

  public static function getInfo() {
    return array(
      'name' => 'SimpleTest error collector',
      'description' => 'Performs tests on the Simpletest error and exception collector.',
      'group' => 'Common',
    );
  }

  function setUp() {
    parent::setUp('system_test', 'error_test');
  }

  /**
   * Test that simpletest collects errors from the tested site.
   */
  function testErrorCollect() {
    $this->collectedErrors = array();
    $this->drupalGet('error-test/generate-warnings-with-report');
    $this->assertEqual(count($this->collectedErrors), 3, t('Three errors were collected'));

    if (count($this->collectedErrors) == 3) {
      $this->assertError($this->collectedErrors[0], 'Notice', 'error_test_generate_warnings()', 'error_test.module', 'Undefined variable: bananas');
      $this->assertError($this->collectedErrors[1], 'Warning', 'error_test_generate_warnings()', 'error_test.module', 'Division by zero');
      $this->assertError($this->collectedErrors[2], 'User warning', 'error_test_generate_warnings()', 'error_test.module', 'Drupal is awesome');
    }
    else {
      // Give back the errors to the log report.
      foreach ($this->collectedErrors as $error) {
        parent::error($error['message'], $error['group'], $error['caller']);
      }
    }
  }

  /**
   * Error handler that collects errors in an array.
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
      $this->collectedErrors[] = array(
        'message' => $message,
        'group' => $group,
        'caller' => $caller
      );
    }
  }

  /**
   * Assert that a collected error matches what we are expecting.
   */
  function assertError($error, $group, $function, $file, $message = NULL) {
    $this->assertEqual($error['group'], $group, t("Group was %group", array('%group' => $group)));
    $this->assertEqual($error['caller']['function'], $function, t("Function was %function", array('%function' => $function)));
    $this->assertEqual(drupal_basename($error['caller']['file']), $file, t("File was %file", array('%file' => $file)));
    if (isset($message)) {
      $this->assertEqual($error['message'], $message, t("Message was %message", array('%message' => $message)));
    }
  }
}
