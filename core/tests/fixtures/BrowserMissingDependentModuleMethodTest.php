<?php

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;

/**
 * A fixture test class with requires annotation.
 *
 * This is a fixture class for
 * \Drupal\FunctionalTests\BrowserTestBaseTest::testMethodRequiresModule().
 *
 * This test class should not be discovered by run-tests.sh, phpstan or phpunit.
 *
 * @group fixture
 */
class BrowserMissingDependentModuleMethodTest extends BrowserTestBase {

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
