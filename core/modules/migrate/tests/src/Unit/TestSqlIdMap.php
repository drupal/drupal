<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit;

use Drupal\Core\Database\Connection;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a SQL ID map for use in tests.
 */
class TestSqlIdMap extends Sql implements \Iterator {

  /**
   * Constructs a TestSqlIdMap object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID for the migration process to do.
   * @param mixed $plugin_definition
   *   The configuration for the plugin.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to do.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_manager
   *   The migration manager.
   */
  public function __construct(Connection $database, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EventDispatcherInterface $event_dispatcher, MigrationPluginManagerInterface $migration_manager) {
    $this->database = $database;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $event_dispatcher, $migration_manager);
  }

  /**
   * {@inheritdoc}
   */
  public $message;

  /**
   * {@inheritdoc}
   */
  public function getDatabase() {
    return parent::getDatabase();
  }

  /**
   * Gets the field schema.
   *
   * @param array $id_definition
   *   An array defining the field, with a key 'type'.
   *
   * @return array
   *   A field schema depending on value of key 'type'.  An empty array is
   *   returned if 'type' is not defined.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function getFieldSchema(array $id_definition) {
    if (!isset($id_definition['type'])) {
      return [];
    }
    switch ($id_definition['type']) {
      case 'integer':
        return [
          'type' => 'int',
          'not null' => TRUE,
        ];

      case 'string':
        return [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ];

      default:
        throw new MigrateException($id_definition['type'] . ' not supported');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function ensureTables() {
    parent::ensureTables();
  }

}
