<?php

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;

@trigger_error('\\Drupal\\FunctionalTests\\BrowserMissingDependentModuleMethodTest is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3418480', E_USER_DEPRECATED);

/**
 * A fixture test class with requires annotation.
 *
 * This is a fixture class for
 * \Drupal\FunctionalTests\BrowserTestBaseTest::testMethodRequiresModule().
 *
 * This test class should not be discovered by run-tests.sh, phpstan or phpunit.
 *
 * @group fixture
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3418480
 */
class BrowserMissingDependentModuleMethodTest extends BrowserTestBase {

  /**
   * This method should be skipped since it requires a module that is not found.
   *
   * @requires module module_does_not_exist
   */
  public function testRequiresModule(): void {
    $this->fail('Running test with missing required module.');
  }

  /**
   * Public access for checkRequirements() to avoid reflection.
   */
  public function publicCheckRequirements() {
    return parent::checkRequirements();
  }

}
