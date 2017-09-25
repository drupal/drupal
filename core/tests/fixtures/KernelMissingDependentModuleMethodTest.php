<?php

namespace Drupal\KernelTests;

/**
 * A fixture test class with requires annotation.
 *
 * This is a fixture class for
 * \Drupal\KernelTests\KernelTestBaseTest::testRequiresModule().
 *
 * This test class should not be discovered by run-tests.sh or phpunit.
 *
 * @group fixture
 */
class KernelMissingDependentModuleMethodTest extends KernelTestBase {

  /**
   * This method should be skipped since it requires a module that is not found.
   *
   * @requires module module_does_not_exist
   */
  public function testRequiresModule() {
    $this->fail('Running test with missing required module.');
  }

  /**
   * Public access for checkRequirements() to avoid reflection.
   */
  public function publicCheckRequirements() {
    return parent::checkRequirements();
  }

}
