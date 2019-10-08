<?php

namespace Drupal\migrate;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

/**
 * Provides the migrate stubbing service.
 */
class MigrateStub implements MigrateStubInterface {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs a MigrationStub object.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   */
  public function __construct(MigrationPluginManagerInterface $migration_plugin_manager) {
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * Creates a stub.
   *
   * @param string $migration_id
   *   The migration to stub.
   * @param array $source_ids
   *   An array of source ids.
   * @param array $default_values
   *   (optional) An array of default values to add to the stub.
   * @param bool $key_by_destination_ids
   *   (optional) NULL or TRUE to force indexing of the return array by
   *   destination id keys (default), or FALSE to return the raw return value of
   *   the destination plugin's ::import() method. The return value from
   *   MigrateDestinationInterface::import() is very poorly defined as "The
   *   entity ID or an indication of success". In practice, the mapping systems
   *   expect and all destination plugins return an array of destination
   *   identifiers. Unfortunately these arrays are inconsistently keyed. The
   *   core destination plugins return a numerically indexed array of
   *   destination identifiers, but several contrib destinations return an array
   *   of identifiers indexed by the destination keys. This method will
   *   generally index all return arrays for consistency and to provide as much
   *   information as possible, but this parameter is added for backwards
   *   compatibility to allow accessing the original array.
   *
   * @return array|false
   *   An array of destination ids for the new stub, keyed by destination id
   *   key, or false if the stub failed.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\migrate\MigrateException
   */
  public function createStub($migration_id, array $source_ids, array $default_values = [], $key_by_destination_ids = NULL) {
    $migrations = $this->migrationPluginManager->createInstances([$migration_id]);
    if (!$migrations) {
      throw new PluginNotFoundException($migration_id);
    }
    if (count($migrations) !== 1) {
      throw new \LogicException(sprintf('Cannot stub derivable migration "%s".  You must specify the id of a specific derivative to stub.', $migration_id));
    }
    $migration = reset($migrations);
    $source_id_keys = array_keys($migration->getSourcePlugin()->getIds());
    if (count($source_id_keys) !== count($source_ids)) {
      throw new \InvalidArgumentException('Expected and provided source id counts do not match.');
    }
    if (array_keys($source_ids) === range(0, count($source_ids) - 1)) {
      $source_ids = array_combine($source_id_keys, $source_ids);
    }
    $stub = $this->doCreateStub($migration, $source_ids, $default_values);

    // If the return from ::import is numerically indexed, and we aren't
    // requesting the raw return value, index it associatively using the
    // destination id keys.
    if (($key_by_destination_ids !== FALSE) && array_keys($stub) === range(0, count($stub) - 1)) {
      $stub = array_combine(array_keys($migration->getDestinationPlugin()->getIds()), $stub);
    }
    return $stub;
  }

  /**
   * Creates a stub.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to use to create the stub.
   * @param array $source_ids
   *   The source ids to map to the stub.
   * @param array $default_values
   *   (optional) An array of values to include in the stub.
   *
   * @return array|bool
   *   An array of destination ids for the stub.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function doCreateStub(MigrationInterface $migration, array $source_ids, array $default_values = []) {
    $destination = $migration->getDestinationPlugin(TRUE);
    $process = $migration->getProcess();
    $id_map = $migration->getIdMap();
    $migrate_executable = new MigrateExecutable($migration);
    $row = new Row($source_ids + $migration->getSourceConfiguration(), $migration->getSourcePlugin()->getIds(), TRUE);
    $migrate_executable->processRow($row, $process);
    foreach ($default_values as $key => $value) {
      $row->setDestinationProperty($key, $value);
    }
    $destination_ids = [];
    try {
      $destination_ids = $destination->import($row);
    }
    catch (\Exception $e) {
      $id_map->saveMessage($row->getSourceIdValues(), $e->getMessage());
    }
    if ($destination_ids) {
      $id_map->saveIdMapping($row, $destination_ids, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
      return $destination_ids;
    }
    return FALSE;
  }

}
