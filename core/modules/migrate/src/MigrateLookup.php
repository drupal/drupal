<?php

namespace Drupal\migrate;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

/**
 * Provides a migration lookup service.
 */
class MigrateLookup implements MigrateLookupInterface {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs a MigrateLookup object.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   */
  public function __construct(MigrationPluginManagerInterface $migration_plugin_manager) {
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function lookup($migration_id, array $source_id_values) {
    $results = [];
    $migrations = $this->migrationPluginManager->createInstances($migration_id);
    if (!$migrations) {
      throw new PluginNotFoundException($migration_id);
    }
    foreach ($migrations as $migration) {
      if ($result = $this->doLookup($migration, $source_id_values)) {
        $results = array_merge($results, $result);
      }
    }
    return $results;
  }

  /**
   * Performs a lookup.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration upon which to perform the lookup.
   * @param array $source_id_values
   *   The source ID values to look up.
   *
   * @return array
   *   An array of arrays of destination identifier values.
   *
   * @throws \Drupal\migrate\MigrateException
   *   Thrown when $source_id_values contains unknown keys, or the wrong number
   *   of keys.
   */
  protected function doLookup(MigrationInterface $migration, array $source_id_values) {
    $destination_keys = array_keys($migration->getDestinationPlugin()->getIds());
    $indexed_ids = $migration->getIdMap()
      ->lookupDestinationIds($source_id_values);
    $keyed_ids = [];
    foreach ($indexed_ids as $id) {
      $keyed_ids[] = array_combine($destination_keys, $id);
    }
    return $keyed_ids;
  }

}
