<?php

/**
 * @file
 * Contains \Drupal\Tests\Standards\DrupalStandardsListener.
 *
 * Listener for PHPUnit tests, to enforce various coding standards within test
 * runs.
 */

namespace Drupal\Tests\Standards;

/**
 * Listens for PHPUnit tests and fails those with invalid coverage annotations.
 */
class DrupalStandardsListener extends \PHPUnit_Framework_BaseTestListener {

  /**
   * Signals a coding standards failure to the user.
   *
   * @param \PHPUnit_Framework_TestCase $test
   *   The test where we should insert our test failure.
   * @param string $message
   *   The message to add to the failure notice. The test class name and test
   *   name will be appended to this message automatically.
   */
  protected function fail(\PHPUnit_Framework_TestCase $test, $message) {
    // Add the report to the test's results.
    $message .= ': ' . get_class($test) . '::' . $test->getName();
    $fail = new \PHPUnit_Framework_AssertionFailedError($message);
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
  protected function classExists($class) {
    return class_exists($class, TRUE) || trait_exists($class, TRUE) || interface_exists($class, TRUE);
  }

  /**
   * Check an individual test run for valid @covers annotation.
   *
   * This method is called from $this::endTest().
   *
   * @param \PHPUnit_Framework_TestCase $test
   *   The test to examine.
   */
  public function checkValidCoversForTest(\PHPUnit_Framework_TestCase $test) {
    // If we're generating a coverage report already, don't do anything here.
    if ($test->getTestResultObject()->getCollectCodeCoverageInformation()) {
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
   * {@inheritdoc}
   */
  public function endTest(\PHPUnit_Framework_Test $test, $time) {
    // \PHPUnit_Framework_TestListener interface passes us a
    // \PHPUnit_Framework_Test argument in this signature, but we have to assume
    // that it is a \PHPUnit_Framework_TestCase. Things are not really useful
    // otherwise.
    $this->checkValidCoversForTest($test);
  }

}
