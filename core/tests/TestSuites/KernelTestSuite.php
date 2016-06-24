<?php

namespace Drupal\Tests\TestSuites;

require_once __DIR__ . '/TestSuiteBase.php';

/**
 * Discovers tests for the kernel test suite.
 */
class KernelTestSuite extends TestSuiteBase {

  /**
   * Factory method which loads up a suite with all kernel tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite() {
    $root = dirname(dirname(dirname(__DIR__)));

    $suite = new static('kernel');
    $suite->addTestsBySuiteNamespace($root, 'Kernel');

    return $suite;
  }

}
