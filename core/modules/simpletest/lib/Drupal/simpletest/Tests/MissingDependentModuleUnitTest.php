<?php

/**
 * @file
 * Definition of \Drupal\simpletest\Tests\MissingDependentModuleUnitTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\UnitTestBase;

/**
 * Test required modules for tests.
 */
class MissingDependentModuleUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Testing dependent module test',
      'description' => 'This test should not load since it requires a module that is not found.',
      'group' => 'SimpleTest',
      'dependencies' => array('simpletest_missing_module'),
    );
  }

  /**
   * Ensure that this test will not be loaded despite its dependency.
   */
  function testFail() {
    $this->fail(t('Running test with missing required module.'));
  }
}
