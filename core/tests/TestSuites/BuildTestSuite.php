<?php

declare(strict_types=1);

namespace Drupal\Tests\TestSuites;

require_once __DIR__ . '/TestSuiteBase.php';

/**
 * Discovers tests for the build test suite.
 */
class BuildTestSuite extends TestSuiteBase {

  /**
   * Factory method which loads up a suite with all build tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite() {
    $root = dirname(__DIR__, 3);

    $suite = new static('build');
    $suite->addTestsBySuiteNamespace($root, 'Build');

    return $suite;
  }

}
