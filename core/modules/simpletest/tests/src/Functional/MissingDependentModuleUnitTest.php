<?php

namespace Drupal\Tests\simpletest\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * This test should not load since it requires a module that is not found.
 *
 * @group simpletest
 * @dependencies simpletest_missing_module
 */
class MissingDependentModuleUnitTest extends BrowserTestBase {

  /**
   * Ensure that this test will not be loaded despite its dependency.
   */
  public function testFail() {
    $this->fail('Running test with missing required module.');
  }

}
