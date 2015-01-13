<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\source\SqlBase.
 */

namespace Drupal\migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Sources whose data may be fetched via DBTNG.
 *
 * By default, an existing database connection with key 'migrate' and target
 * 'default' is used. These may be overridden with explicit 'key' and/or
 * 'target' configuration keys. In addition, if the configuration key 'database'
 * is present, it is used as a database connection information array to define
 * the connection.
 */
abstract class SqlBase extends SourcePluginBase {

  /**
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $query;

  /**
   * @var \Drupal\migrate\Entity\MigrationInterface
   */
  protected $migration;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * Print the query string when the object is used a string.
   *
   * @return string
   *   The query string.
   */
  public function __toString() {
    return (string) $this->query;
  }

  /**
   * Get the database connection object.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function getDatabase() {
    if (!isset($this->database)) {
      if (isset($this->configuration['target'])) {
        $target = $this->configuration['target'];
      }
      else {
        $target = 'default';
      }
      if (isset($this->configuration['key'])) {
        $key = $this->configuration['key'];
      }
      else {
        $key = 'migrate';
      }
      if (isset($this->configuration['database'])) {
        Database::addConnectionInfo($key, $target, $this->configuration['database']);
      }
      $this->database = Database::getConnection($target, $key);
    }
    return $this->database;
  }

  /**
   * Wrapper for database select.
   */
  protected function select($table, $alias = NULL, array $options = array()) {
    $options['fetch'] = \PDO::FETCH_ASSOC;
    return $this->getDatabase()->select($table, $alias, $options);
  }

  /**
   * A helper for adding tags and metadata to the query.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The query with additional tags and metadata.
   */
  protected function prepareQuery() {
    $this->query = clone $this->query();
    $this->query->addTag('migrate');
    $this->query->addTag('migrate_' . $this->migration->id());
    $this->query->addMetaData('migration', $this->migration);

    return $this->query;
  }

  /**
   * Implementation of MigrateSource::performRewind().
   *
   * We could simply execute the query and be functionally correct, but
   * we will take advantage of the PDO-based API to optimize the query up-front.
   */
  protected function runQuery() {
    $this->prepareQuery();
    $high_water_property = $this->migration->get('highWaterProperty');

    // Get the key values, for potential use in joining to the map table, or
    // enforcing idlist.
    $keys = array();

    // The rules for determining what conditions to add to the query are as
    // follows (applying first applicable rule)
    // 1. If idlist is provided, then only process items in that list (AND key
    //    IN (idlist)). Only applicable with single-value keys.
    if ($id_list = $this->migration->get('idlist')) {
      $this->query->condition($keys[0], $id_list, 'IN');
    }
    else {
      // 2. If the map is joinable, join it. We will want to accept all rows
      //    which are either not in the map, or marked in the map as NEEDS_UPDATE.
      //    Note that if high water fields are in play, we want to accept all rows
      //    above the high water mark in addition to those selected by the map
      //    conditions, so we need to OR them together (but AND with any existing
      //    conditions in the query). So, ultimately the SQL condition will look
      //    like (original conditions) AND (map IS NULL OR map needs update
      //      OR above high water).
      $conditions = $this->query->orConditionGroup();
      $condition_added = FALSE;
      if ($this->mapJoinable()) {
        // Build the join to the map table. Because the source key could have
        // multiple fields, we need to build things up.
        $count = 1;
        $map_join = '';
        $delimiter = '';
        foreach ($this->getIds() as $field_name => $field_schema) {
          if (isset($field_schema['alias'])) {
            $field_name = $field_schema['alias'] . '.' . $field_name;
          }
          $map_join .= "$delimiter$field_name = map.sourceid" . $count++;
          $delimiter = ' AND ';
        }

        $alias = $this->query->leftJoin($this->migration->getIdMap()->getQualifiedMapTableName(), 'map', $map_join);
        $conditions->isNull($alias . '.sourceid1');
        $conditions->condition($alias . '.source_row_status', MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
        $condition_added = TRUE;

        // And as long as we have the map table, add its data to the row.
        $n = count($this->getIds());
        for ($count = 1; $count <= $n; $count++) {
          $map_key = 'sourceid' . $count;
          $this->query->addField($alias, $map_key, "migrate_map_$map_key");
        }
        if ($n = count($this->migration->get('destinationIds'))) {
          for ($count = 1; $count <= $n; $count++) {
            $map_key = 'destid' . $count++;
            $this->query->addField($alias, $map_key, "migrate_map_$map_key");
          }
        }
        $this->query->addField($alias, 'source_row_status', 'migrate_map_source_row_status');
      }
      // 3. If we are using high water marks, also include rows above the mark.
      //    But, include all rows if the high water mark is not set.
      if (isset($high_water_property['name']) && ($high_water = $this->migration->getHighWater()) !== '') {
        if (isset($high_water_property['alias'])) {
          $high_water = $high_water_property['alias'] . '.' . $high_water_property['name'];
        }
        else {
          $high_water = $high_water_property['name'];
        }
        $conditions->condition($high_water, $high_water, '>');
        $condition_added = TRUE;
      }
      if ($condition_added) {
        $this->query->condition($conditions);
      }
    }

    return new \IteratorIterator($this->query->execute());
  }

  /**
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  abstract public function query();

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->query()->countQuery()->execute()->fetchField();
  }

  /**
   * Returns the iterator that will yield the row arrays to be processed.
   *
   * @return \Iterator
   */
  public function getIterator() {
    if (!isset($this->iterator)) {
      $this->iterator = $this->runQuery();
    }
    return $this->iterator;
  }

  /**
   * Check if we can join against the map table.
   *
   * This function specifically catches issues when we're migrating with
   * unique sets of credentials for the source and destination database.
   *
   * @return bool
   *   TRUE if we can join against the map table otherwise FALSE.
   */
  protected function mapJoinable() {
    if (!$this->getIds()) {
      return FALSE;
    }
    $id_map = $this->migration->getIdMap();
    if (!$id_map instanceof Sql) {
      return FALSE;
    }
    $id_map_database_options = $id_map->getDatabase()->getConnectionOptions();
    $source_database_options = $this->getDatabase()->getConnectionOptions();
    foreach (array('username', 'password', 'host', 'port', 'namespace', 'driver') as $key) {
      if (isset($source_database_options[$key])) {
        if ($id_map_database_options[$key] != $source_database_options[$key]) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

}
