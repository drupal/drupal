<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal\Traits;

use Drupal\field_discovery_test\FieldDiscoveryTestClass;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_drupal\FieldDiscoveryInterface;

/**
 * Helper functions to test field discovery.
 */
trait FieldDiscoveryTestTrait {

  /**
   * Asserts the field discovery returns the expected processes.
   *
   * @param \Drupal\migrate_drupal\FieldDiscoveryInterface $field_discovery
   *   The Field Discovery service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager service.
   * @param string $core
   *   The Drupal core version, either '6', or '7'.
   * @param string $field_plugin_method
   *   (optional) The field plugin method to use.
   * @param array $expected_process
   *   (optional) The expected resulting process.
   * @param string $entity_type_id
   *   (optional) The entity type id.
   * @param string $bundle
   *   (optional) The bundle.
   */
  public function assertFieldProcess(FieldDiscoveryInterface $field_discovery, MigrationPluginManagerInterface $migration_plugin_manager, $core, $field_plugin_method = NULL, array $expected_process = [], $entity_type_id = NULL, $bundle = NULL) {
    $definition = [
      'migration_tags' => ['Drupal ' . $core],
      'field_plugin_method' => $field_plugin_method,
    ];
    $migration = $migration_plugin_manager->createStubMigration($definition);
    if ($bundle) {
      $field_discovery->addBundleFieldProcesses($migration, $entity_type_id, $bundle);
    }
    elseif ($entity_type_id) {
      $field_discovery->addEntityFieldProcesses($migration, $entity_type_id);
    }
    else {
      $field_discovery->addAllFieldProcesses($migration);
    }
    $actual_process = $migration->getProcess();
    $this->assertSame($expected_process, $actual_process);
  }

  /**
   * Asserts the field discovery returns the expected processes.
   *
   * @param \Drupal\migrate_drupal\FieldDiscoveryInterface $field_discovery
   *   The Field Discovery service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager service.
   * @param string $core
   *   The Drupal core version, either '6', or '7'.
   * @param array $expected_process_keys
   *   (optional) The expected resulting process_keys.
   * @param string $entity_type_id
   *   (optional) The entity type id.
   * @param string $bundle
   *   (optional) The bundle.
   */
  public function assertFieldProcessKeys(FieldDiscoveryInterface $field_discovery, MigrationPluginManagerInterface $migration_plugin_manager, $core, array $expected_process_keys, $entity_type_id = NULL, $bundle = NULL) {
    $definition = [
      'migration_tags' => ['Drupal ' . $core],
    ];
    $migration = $migration_plugin_manager->createStubMigration($definition);
    if ($bundle) {
      $field_discovery->addBundleFieldProcesses($migration, $entity_type_id, $bundle);
    }
    elseif ($entity_type_id) {
      $field_discovery->addEntityFieldProcesses($migration, $entity_type_id);
    }
    else {
      $field_discovery->addAllFieldProcesses($migration);
    }
    $actual_process = $migration->getProcess();
    $actual = array_keys($actual_process);
    $this->assertSame(sort($expected_process_keys), sort($actual));
  }

  /**
   * Asserts a migrate source plugin.
   *
   * @param string $core
   *   The Drupal core version.
   * @param string $class
   *   The expected class of the source plugin.
   * @param array $expected_definition
   *   The expected source plugin definition.
   */
  public function assertSourcePlugin($core, $class, array $expected_definition) {
    $field_discovery = new FieldDiscoveryTestClass($this->fieldPluginManager, $this->migrationPluginManager, $this->logger);
    $source = $field_discovery->getSourcePlugin($core);
    $this->assertInstanceOf($class, $source);
    $this->assertSame($expected_definition, $source->getPluginDefinition());
  }

}
