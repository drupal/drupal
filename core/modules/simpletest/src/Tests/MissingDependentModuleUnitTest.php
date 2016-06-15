<?php

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * This test should not load since it requires a module that is not found.
 *
 * @group simpletest
 * @dependencies simpletest_missing_module
 */
class MissingDependentModuleUnitTest extends WebTestBase {

  /**
   * Ensure that this test will not be loaded despite its dependency.
   */
  function testFail() {
    $this->fail('Running test with missing required module.');
  }

}
