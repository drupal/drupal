<?php

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;

/**
 * A fixture test class with requires annotation.
 *
 * This is a fixture class for
 * \Drupal\FunctionalTests\BrowserTestBaseTest::testRequiresModule().
 *
 * This test class should not be discovered by run-tests.sh or phpunit.
 *
 * @requires module module_does_not_exist
 * @group fixture
 */
class BrowserMissingDependentModuleTest extends BrowserTestBase {

  /**
   * Placeholder test method.
   *
   * Depending on configuration, PHPUnit might fail a test if it has no test
   * methods, so we must provide one. This method should never be executed.
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
