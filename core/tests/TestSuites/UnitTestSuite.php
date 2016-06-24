<?php

namespace Drupal\Tests\TestSuites;

require_once __DIR__ . '/TestSuiteBase.php';

/**
 * Discovers tests for the unit test suite.
 */
class UnitTestSuite extends TestSuiteBase {

  /**
   * Factory method which loads up a suite with all unit tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite() {
    $root = dirname(dirname(dirname(__DIR__)));

    $suite = new static('unit');
    $suite->addTestsBySuiteNamespace($root, 'Unit');

    return $suite;
  }

}
