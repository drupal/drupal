<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal\Traits;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Drupal\migrate_drupal\MigrationState;

/**
 * Tests the migration state information in module.migrate_drupal.yml.
 *
 * This test checks that the discovered upgrade paths, which are based on the
 * source_module and destination_module definition matches the declared
 * upgrade paths in all the migrate_drupal.yml files.
 *
 * @group migrate_drupal
 */
trait ValidateMigrationStateTestTrait {

  use FileSystemModuleDiscoveryDataProviderTrait;
  use MigrationConfigurationTrait;

  /**
   * Tests the migration information in .migrate_drupal.yml.
   *
   * Checks that every discovered pair has a corresponding declaration in the
   * declared pairs. The alternate check, that each declared pair has a
   * corresponding discovered pair is not possible because declarations can be
   * made for the two cases where migrations are yet to be written and where
   * migrations are not needed.
   */
  public function testMigrationState(): void {

    // Level separator of destination and source properties.
    $separator = ',';

    $this->enableAllModules();

    $version = (string) $this->getLegacyDrupalVersion($this->sourceDatabase);

    // Build an array for each migration keyed by provider. The value is a
    // string consisting of the version number, the provider, the source_module
    // and the destination module.
    $discovered = [];
    /** @var \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.migration');
    $migrations = $plugin_manager->createInstancesByTag('Drupal ' . $version);
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    foreach ($migrations as $migration) {
      $definition = $migration->getPluginDefinition();
      if (is_array($definition['provider'])) {
        $provider = reset($definition['provider']);
      }
      else {
        $provider = $definition['provider'];
      }

      $source_module = $migration->getSourcePlugin()->getSourceModule();
      $destination_module = $migration->getDestinationPlugin()
        ->getDestinationModule();

      $discovered[] = implode($separator, [
        $provider,
        $source_module,
        $destination_module,
      ]);
    }

    // Add the field migrations.
    /** @var \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager */
    $definitions = $this->container->get('plugin.manager.migrate.field')
      ->getDefinitions();
    foreach ($definitions as $key => $definition) {
      if (isset($definition['core'][$version])) {
        $discovered[] = implode($separator, [
          $definition['provider'],
          $definition['source_module'],
          $definition['destination_module'],
        ]);
      }
    }

    // Get the declared migration state information from .migrate_drupal.yml
    // and build an array of source modules and there migration state. The
    // destination is not used yet but can be later for validating the
    // source/destination pairs with the actual source/destination pairs in the
    // migrate plugins.
    $system_info = (new YamlDiscovery('migrate_drupal', array_map(function ($value) {
      return $value . '/migrations/state/';
    }, \Drupal::moduleHandler()->getModuleDirectories())))->findAll();

    $declared = [
      MigrationState::FINISHED => [],
      MigrationState::NOT_FINISHED => [],
    ];
    foreach ($system_info as $module => $info) {
      foreach (array_keys($declared) as $state) {
        if (isset($info[$state][$version])) {
          foreach ($info[$state][$version] as $source => $destination) {
            // Do not add the source module i18nstrings or i18_string. The
            // 18n migrations can have up to three source modules but only one
            // can be handled in the migration.
            if (($source !== 'i18nstrings') && ($source !== 'i18n_string')) {
              foreach ((array) $destination as $dest) {
                $key = [$module, $source, trim($dest)];
                $declared[$state][] = implode($separator, $key);
              }
            }
          }
        }
      }
    }

    // Sort and make the array values unique.
    sort($declared[MigrationState::FINISHED]);
    sort($declared[MigrationState::NOT_FINISHED]);
    $declared_unique[MigrationState::FINISHED] = array_unique($declared[MigrationState::FINISHED]);
    $declared_unique[MigrationState::NOT_FINISHED] = array_unique($declared[MigrationState::NOT_FINISHED]);
    sort($discovered);
    $discovered_unique = array_unique($discovered);

    // Assert that each discovered migration has a corresponding declaration
    // in a migrate_drupal.yml.
    foreach ($discovered_unique as $datum) {
      $data = str_getcsv($datum);
      $in_finished = in_array($datum, $declared_unique[MigrationState::FINISHED]);
      $in_not_finished = in_array($datum, $declared_unique[MigrationState::NOT_FINISHED]);
      $found = $in_finished || $in_not_finished;
      $this->assertTrue($found, sprintf("No migration state found for version '%s' with source_module '%s' and destination_module '%s' declared in module '%s'", $version, $data[1], $data[2], $data[0]));
    }

    // Remove the declared finished from the discovered, leaving just the not
    // finished, if there are any. These should have an entry in the declared
    // not finished.
    $discovered_not_finished = array_diff($discovered_unique, $declared_unique[MigrationState::FINISHED]);
    foreach ($discovered_not_finished as $datum) {
      $data = str_getcsv($datum);
      $this->assertContains($datum, $declared_unique[MigrationState::NOT_FINISHED], sprintf("No migration found for version '%s' with source_module '%s' and destination_module '%s' declared in module '%s'", $version, $data[1], $data[2], $data[0]));
    }
  }

  /**
   * Enable all available modules.
   */
  protected function enableAllModules() {
    // Install all available modules.
    $module_handler = $this->container->get('module_handler');
    $modules = $this->coreModuleListDataProvider();
    $modules_enabled = $module_handler->getModuleList();
    $modules_to_enable = array_keys(array_diff_key($modules, $modules_enabled));
    $this->enableModules($modules_to_enable);
    return $modules;
  }

}
