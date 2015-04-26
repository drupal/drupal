<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\Migration.
 */


namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Calculates the value of a property based on a previous migration.
 *
 * @MigrateProcessPlugin(
 *   id = "migration"
 * )
 */
class Migration extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $processPluginManager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $migrationStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, MigratePluginManager $process_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationStorage = $storage;
    $this->migration = $migration;
    $this->processPluginManager = $process_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity.manager')->getStorage('migration'),
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $migration_ids = $this->configuration['migration'];
    if (!is_array($migration_ids)) {
      $migration_ids = array($migration_ids);
    }
    $scalar = FALSE;
    if (!is_array($value)) {
      $scalar = TRUE;
      $value = array($value);
    }
    $this->skipOnEmpty($value);
    $self = FALSE;
    /** @var \Drupal\migrate\Entity\MigrationInterface[] $migrations */
    $migrations = $this->migrationStorage->loadMultiple($migration_ids);
    $destination_ids = NULL;
    $source_id_values = array();
    foreach ($migrations as $migration_id => $migration) {
      if ($migration_id == $this->migration->id()) {
        $self = TRUE;
      }
      if (isset($this->configuration['source_ids'][$migration_id])) {
        $configuration = array('source' => $this->configuration['source_ids'][$migration_id]);
        $source_id_values[$migration_id] = $this->processPluginManager
          ->createInstance('get', $configuration, $this->migration)
          ->transform(NULL, $migrate_executable, $row, $destination_property);
      }
      else {
        $source_id_values[$migration_id] = $value;
      }
      // Break out of the loop as soon as a destination ID is found.
      if ($destination_ids = $migration->getIdMap()->lookupDestinationID($source_id_values[$migration_id])) {
        break;
      }
    }

    if (!$destination_ids && ($self || isset($this->configuration['stub_id']) || count($migrations) == 1)) {
      // If the lookup didn't succeed, figure out which migration will do the
      // stubbing.
      if ($self) {
        $migration = $this->migration;
      }
      elseif (isset($this->configuration['stub_id'])) {
        $migration = $migrations[$this->configuration['stub_id']];
      }
      else {
        $migration = reset($migrations);
      }
      $destination_plugin = $migration->getDestinationPlugin(TRUE);
      // Only keep the process necessary to produce the destination ID.
      $process = $migration->get('process');
      // We already have the source id values but need to key them for the Row
      // constructor.
      $source_ids = $migration->getSourcePlugin()->getIds();
      $values = array();
      foreach (array_keys($source_ids) as $index => $source_id) {
        $values[$source_id] = $source_id_values[$migration->id()][$index];
      }

      $stub_row = new Row($values + $migration->get('source'), $source_ids, TRUE);

      // Do a normal migration with the stub row.
      $migrate_executable->processRow($stub_row, $process);
      $destination_ids = array();
      try {
        $destination_ids = $destination_plugin->import($stub_row);
      }
      catch (MigrateException $e) {
      }
    }
    if ($destination_ids) {
      if ($scalar) {
        if (count($destination_ids) == 1) {
          return reset($destination_ids);
        }
      }
      else {
        return $destination_ids;
      }
    }
    throw new MigrateSkipRowException();
  }

  /**
   * Skip the migration process entirely if the value is FALSE.
   *
   * @param mixed $value
   *   The incoming value to transform.
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   */
  protected function skipOnEmpty($value) {
    if (!array_filter($value)) {
      throw new MigrateSkipProcessException();
    }
  }

}
