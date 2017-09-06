<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Tests that modules exist for all source and destination plugins.
 *
 * @group migrate_drupal_ui
 */
class MigrationProvidersExistTest extends MigrateDrupalTestBase {

  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migration_provider_test'];

  /**
   * Tests that modules exist for all source and destination plugins.
   */
  public function testProvidersExist() {
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
    // Ensure the test module was enabled.
    $this->assertTrue(array_key_exists('migration_provider_test', $migrations));
    $this->assertTrue(array_key_exists('migration_provider_no_annotation', $migrations));
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    foreach ($migrations as $migration) {
      $source_module = $migration->getSourcePlugin()->getSourceModule();
      $destination_module = $migration->getDestinationPlugin()->getDestinationModule();
      $migration_id = $migration->getPluginId();
      if ($migration_id == 'migration_provider_test') {
        $this->assertFalse($source_module, new FormattableMarkup('Source module not found for @migration_id.', ['@migration_id' => $migration_id]));
        $this->assertFalse($destination_module, new FormattableMarkup('Destination module not found for @migration_id.', ['@migration_id' => $migration_id]));
      }
      elseif ($migration_id == 'migration_provider_no_annotation') {
        $this->assertFalse($source_module, new FormattableMarkup('Source module not found for @migration_id.', ['@migration_id' => $migration_id]));
        $this->assertTrue($destination_module, new FormattableMarkup('Destination module found for @migration_id.', ['@migration_id' => $migration_id]));
      }
      else {
        $this->assertTrue($source_module, new FormattableMarkup('Source module found for @migration_id.', ['@migration_id' => $migration_id]));
        $this->assertTrue($destination_module, new FormattableMarkup('Destination module found for @migration_id.', ['@migration_id' => $migration_id]));
      }
      // Destination module can't be migrate or migrate_drupal or migrate_drupal_ui
      $invalid_destinations = ['migrate', 'migrate_drupal', 'migrate_drupal_ui'];
      $this->assertNotContains($destination_module, $invalid_destinations, new FormattableMarkup('Invalid destination for @migration_id.', ['@migration_id' => $migration_id]));
    }
  }

}
