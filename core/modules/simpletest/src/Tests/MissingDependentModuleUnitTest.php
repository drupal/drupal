<?php

/**
 * @file
 * Definition of \Drupal\simpletest\Tests\MissingDependentModuleUnitTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\UnitTestBase;

/**
 * This test should not load since it requires a module that is not found.
 *
 * @group simpletest
 * @requires module simpletest_missing_module
 */
class MissingDependentModuleUnitTest extends UnitTestBase {
  /**
   * Ensure that this test will not be loaded despite its dependency.
   */
  function testFail() {
    $this->fail('Running test with missing required module.');
  }
}
