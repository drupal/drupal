<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\ModulesDisabledUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Tests upgrading with all non-required modules installed but disabled.
 *
 * Loads a filled installation of Drupal 7 with disabled modules and runs the
 * upgrade process on it.
 */
class ModulesDisabledUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name'  => 'Modules disabled upgrade test',
      'description'  => 'Upgrade path test for disabled modules.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.filled.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.all-disabled.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests an upgrade with all non-required modules installed but disabled.
   */
  public function testDisabledUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Get enabled modules.
    $enabled = \Drupal::moduleHandler()->getModuleList();
    // Get all available modules.
    $available = system_rebuild_module_data();
    // Filter out hidden test modules.
    foreach ($available as $module => $data) {
      if (!empty($data->info['hidden'])) {
        unset($available[$module]);
      }
    }
    $to_enable = array_diff_key($available, $enabled);
    module_enable(array_keys($to_enable));
    // Check for updates.
    require_once DRUPAL_ROOT . '/core/includes/update.inc';
    require_once DRUPAL_ROOT . '/core/includes/install.inc';
    $updates = update_get_update_list();
    $this->assertEqual($updates, array(), 'No pending updates after enabling all modules.');
    $this->assertTrue(\Drupal::state()->get('update_test_1_update_dependencies_run'), 'Module update dependencies resolved for disabled modules');
  }
}
