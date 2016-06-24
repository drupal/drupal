<?php

namespace Drupal\Tests\TestSuites;

require_once __DIR__ . '/TestSuiteBase.php';

/**
 * Discovers tests for the functional-javascript test suite.
 */
class FunctionalJavascriptTestSuite extends TestSuiteBase {

  /**
   * Factory method which loads up a suite with all functional javascript tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite() {
    $root = dirname(dirname(dirname(__DIR__)));

    $suite = new static('functional-javascript');
    $suite->addTestsBySuiteNamespace($root, 'FunctionalJavascript');

    return $suite;
  }

}
