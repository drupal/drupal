<?php

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * This test should not load since it requires a module that is not found.
 *
 * @group simpletest
 * @dependencies simpletest_missing_module
 */
class MissingDependentModuleUnitTest extends KernelTestBase {

  /**
   * Ensure that this test will not be loaded despite its dependency.
   */
  function testFail() {
    $this->fail('Running test with missing required module.');
  }

}
