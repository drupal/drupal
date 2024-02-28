<?php

declare(strict_types=1);

namespace Drupal\Tests\TestSuites;

require_once __DIR__ . '/TestSuiteBase.php';

/**
 * Discovers tests for the unit test suite.
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no
 *   replacement and test discovery will be handled differently in PHPUnit 10.
 *
 * @see https://www.drupal.org/node/3405829
 */
class UnitTestSuite extends TestSuiteBase {

  /**
   * Factory method which loads up a suite with all unit tests.
   *
   * @return static
   *   The test suite.
   */
  public static function suite() {
    $root = dirname(__DIR__, 3);

    $suite = new static('unit');
    $suite->addTestsBySuiteNamespace($root, 'Unit');

    return $suite;
  }

}
