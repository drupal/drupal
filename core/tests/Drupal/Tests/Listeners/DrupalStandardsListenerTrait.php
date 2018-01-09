<?php

namespace Drupal\Tests\Listeners;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;

/**
 * Listens for PHPUnit tests and fails those with invalid coverage annotations.
 *
 * Enforces various coding standards within test runs.
 *
 * @internal
 */
trait DrupalStandardsListenerTrait {

  /**
   * Signals a coding standards failure to the user.
   *
   * @param \PHPUnit\Framework\TestCase $test
   *   The test where we should insert our test failure.
   * @param string $message
   *   The message to add to the failure notice. The test class name and test
   *   name will be appended to this message automatically.
   */
  private function fail(TestCase $test, $message) {
    // Add the report to the test's results.
    $message .= ': ' . get_class($test) . '::' . $test->getName();
    $fail = new AssertionFailedError($message);
    $result = $test->getTestResultObject();
    $result->addFailure($test, $fail, 0);
  }

  /**
   * Helper method to check if a string names a valid class or trait.
   *
   * @param string $class
   *   Name of the class to check.
   *
   * @return bool
   *   TRUE if the class exists, FALSE otherwise.
   */
  private function classExists($class) {
    return class_exists($class, TRUE) || trait_exists($class, TRUE) || interface_exists($class, TRUE);
  }

  /**
   * Check an individual test run for valid @covers annotation.
   *
   * This method is called from $this::endTest().
   *
   * @param \PHPUnit\Framework\TestCase $test
   *   The test to examine.
   */
  private function checkValidCoversForTest(TestCase $test) {
    // If we're generating a coverage report already, don't do anything here.
    if ($test->getTestResultObject() && $test->getTestResultObject()->getCollectCodeCoverageInformation()) {
      return;
    }
    // Gather our annotations.
    $annotations = $test->getAnnotations();
    // Glean the @coversDefaultClass annotation.
    $default_class = '';
    $valid_default_class = FALSE;
    if (isset($annotations['class']['coversDefaultClass'])) {
      if (count($annotations['class']['coversDefaultClass']) > 1) {
        $this->fail($test, '@coversDefaultClass has too many values');
      }
      // Grab the first one.
      $default_class = reset($annotations['class']['coversDefaultClass']);
      // Check whether the default class exists.
      $valid_default_class = $this->classExists($default_class);
      if (!$valid_default_class) {
        $this->fail($test, "@coversDefaultClass does not exist '$default_class'");
      }
    }
    // Glean @covers annotation.
    if (isset($annotations['method']['covers'])) {
      // Drupal allows multiple @covers per test method, so we have to check
      // them all.
      foreach ($annotations['method']['covers'] as $covers) {
        // Ensure the annotation isn't empty.
        if (trim($covers) === '') {
          $this->fail($test, '@covers should not be empty');
          // If @covers is empty, we can't proceed.
          return;
        }
        // Ensure we don't have ().
        if (strpos($covers, '()') !== FALSE) {
          $this->fail($test, "@covers invalid syntax: Do not use '()'");
        }
        // Glean the class and method from @covers.
        $class = $covers;
        $method = '';
        if (strpos($covers, '::') !== FALSE) {
          list($class, $method) = explode('::', $covers);
        }
        // Check for the existence of the class if it's specified by @covers.
        if (!empty($class)) {
          // If the class doesn't exist we have either a bad classname or
          // are missing the :: for a method. Either way we can't proceed.
          if (!$this->classExists($class)) {
            if (empty($method)) {
              $this->fail($test, "@covers invalid syntax: Needs '::' or class does not exist in $covers");
              return;
            }
            else {
              $this->fail($test, '@covers class does not exist ' . $class);
              return;
            }
          }
        }
        else {
          // The class isn't specified and we have the ::, so therefore this
          // test either covers a function, or relies on a default class.
          if (empty($default_class)) {
            // If there's no default class, then we need to check if the global
            // function exists. Since this listener should always be listening
            // for endTest(), the function should have already been loaded from
            // its .module or .inc file.
            if (!function_exists($method)) {
              $this->fail($test, '@covers global method does not exist ' . $method);
            }
          }
          else {
            // We have a default class and this annotation doesn't act like a
            // global function, so we should use the default class if it's
            // valid.
            if ($valid_default_class) {
              $class = $default_class;
            }
          }
        }
        // Finally, after all that, let's see if the method exists.
        if (!empty($class) && !empty($method)) {
          $ref_class = new \ReflectionClass($class);
          if (!$ref_class->hasMethod($method)) {
            $this->fail($test, '@covers method does not exist ' . $class . '::' . $method);
          }
        }
      }
    }
  }

  /**
   * Handles errors to ensure deprecation messages are not triggered.
   *
   * @param int $type
   *   The severity level of the error.
   * @param string $msg
   *   The error message.
   * @param $file
   *   The file that caused the error.
   * @param $line
   *   The line number that caused the error.
   * @param array $context
   *   The error context.
   */
  public static function errorHandler($type, $msg, $file, $line, $context = []) {
    if ($type === E_USER_DEPRECATED) {
      return;
    }
    $error_handler = class_exists('PHPUnit_Util_ErrorHandler') ? 'PHPUnit_Util_ErrorHandler' : 'PHPUnit\Util\ErrorHandler';
    return $error_handler::handleError($type, $msg, $file, $line, $context);
  }

  /**
   * Reacts to the end of a test.
   *
   * We must mark this method as belonging to the special legacy group because
   * it might trigger an E_USER_DEPRECATED error during coverage annotation
   * validation. The legacy group allows symfony/phpunit-bridge to keep the
   * deprecation notice as a warning instead of an error, which would fail the
   * test.
   *
   * @group legacy
   *
   * @param \PHPUnit\Framework\Test|\PHPUnit_Framework_Test $test
   *   The test object that has ended its test run.
   * @param float $time
   *   The time the test took.
   *
   * @see http://symfony.com/doc/current/components/phpunit_bridge.html#mark-tests-as-legacy
   */
  private function doEndTest($test, $time) {
    // \PHPUnit_Framework_Test does not have any useful methods of its own for
    // our purpose, so we have to distinguish between the different known
    // subclasses.
    if ($test instanceof TestCase) {
      // Change the error handler to ensure deprecation messages are not
      // triggered.
      set_error_handler([$this, 'errorHandler']);
      $this->checkValidCoversForTest($test);
      restore_error_handler();
    }
    elseif ($this->isTestSuite($test)) {
      foreach ($test->getGroupDetails() as $tests) {
        foreach ($tests as $test) {
          $this->doEndTest($test, $time);
        }
      }
    }
  }

  /**
   * Determine if a test object is a test suite regardless of PHPUnit version.
   *
   * @param \PHPUnit\Framework\Test|\PHPUnit_Framework_Test $test
   *   The test object to test if it is a test suite.
   *
   * @return bool
   *   TRUE if it is a test suite, FALSE if not.
   */
  private function isTestSuite($test) {
    if (class_exists('\PHPUnit_Framework_TestSuite') && $test instanceof \PHPUnit_Framework_TestSuite) {
      return TRUE;
    }
    if (class_exists('PHPUnit\Framework\TestSuite') && $test instanceof TestSuite) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Reacts to the end of a test.
   *
   * @param \PHPUnit\Framework\Test|\PHPUnit_Framework_Test $test
   *   The test object that has ended its test run.
   * @param float $time
   *   The time the test took.
   */
  protected function standardsEndTest($test, $time) {
    $this->doEndTest($test, $time);
  }

}
