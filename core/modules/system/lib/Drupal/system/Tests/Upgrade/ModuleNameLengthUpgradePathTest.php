<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Upgrade\ModuleNameLengthUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

use Drupal\Core\Database\DatabaseExceptionWrapper;

/**
 * Performs upgrade path tests for module name length related changes.
 */
class ModuleNameLengthUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name'  => 'Module name length upgrade test',
      'description'  => 'Upgrade path tests for module name length related changes.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    // Path to the database dump files.
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.minimal.database.php.gz',
    );
    parent::setUp();
  }

  /**
   * Performs upgrade path tests for module name length related changes.
   */
  public function testModuleNameLengths() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Make sure that the module colums where shortened.
    try {
      db_insert('file_usage')
        ->fields(array(
          'fid' => 2,
          'module' => str_repeat('b', 51),
          'type' => $this->randomName(),
          'id' => $this->randomName(),
          'count' => 1,
        ))
        ->execute();
      $this->fail('Length of {file_usage}.module successfully updated.');
    }
    catch (DatabaseExceptionWrapper $e) {
      $this->pass('Length of {file_usage}.module successfully updated.');
    }
  }

}
