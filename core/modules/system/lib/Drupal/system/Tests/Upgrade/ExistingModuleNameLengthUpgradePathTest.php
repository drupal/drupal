<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Upgrade\ExistingModuleNameLengthUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

use Drupal\Core\Database\DatabaseExceptionWrapper;

/**
 * Performs upgrade path tests for module name length related changes.
 */
class ExistingModuleNameLengthUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name'  => 'Module name length upgrade test (existing module)',
      'description'  => 'Upgrade path test when there is an installed module with a too long name.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    // Path to the database dump files.
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.minimal.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.module_name_length.database.php',
    );
    parent::setUp();
  }

  /**
   * Checks that upgrading is not possible when there is a too long module name.
   */
  public function testUpgradeAborts() {
    // Load the first update screen.
    $this->getUpdatePhp();
    if (!$this->assertResponse(200)) {
      throw new \Exception('Initial GET to update.php did not return HTTP 200 status.');
    }

    $this->assertText('Module name too long');
    $this->assertNoFieldByXPath('//input[@type="submit"]', 'Not allowed to continue with the update process.');
  }

}
