<?php

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;

@trigger_error('\\Drupal\\FunctionalTests\\BrowserMissingDependentModuleTest is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3418480', E_USER_DEPRECATED);

/**
 * A fixture test class with requires annotation.
 *
 * This is a fixture class for
 * \Drupal\FunctionalTests\BrowserTestBaseTest::testRequiresModule().
 *
 * This test class should not be discovered by run-tests.sh, phpstan or phpunit.
 *
 * @requires module module_does_not_exist
 * @group fixture
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3418480
 */
class BrowserMissingDependentModuleTest extends BrowserTestBase {

  /**
   * Placeholder test method.
   *
   * Depending on configuration, PHPUnit might fail a test if it has no test
   * methods, so we must provide one. This method should never be executed.
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
