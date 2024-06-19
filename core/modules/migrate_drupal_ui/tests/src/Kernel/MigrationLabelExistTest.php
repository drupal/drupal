<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal_ui\Kernel;

use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Tests that labels exist for all migrations.
 *
 * @group migrate_drupal_ui
 */
class MigrationLabelExistTest extends MigrateDrupalTestBase {

  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * Tests that labels exist for all migrations.
   */
  public function testLabelExist(): void {
    // Install all available modules.
    $module_handler = $this->container->get('module_handler');
    $modules = $this->coreModuleListDataProvider();
    $modules_enabled = $module_handler->getModuleList();
    $modules_to_enable = array_keys(array_diff_key($modules, $modules_enabled));
    $this->enableModules($modules_to_enable);

    /** @var \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.migration');
    // Get all the migrations
    $migrations = $plugin_manager->createInstances(array_keys($plugin_manager->getDefinitions()));
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    foreach ($migrations as $migration) {
      $migration_id = $migration->getPluginId();
      $this->assertNotEmpty($migration->label(), "Label found for $migration_id.");
    }
  }

}
