<?php

namespace Drupal\migrate\Plugin\migrate\source;

use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sources whose data may be fetched via a database connection.
 *
 * Available configuration keys:
 * - database_state_key: (optional) Name of the state key which contains an
 *   array with database connection information.
 * - key: (optional) The database key name. Defaults to 'migrate'.
 * - target: (optional) The database target name. Defaults to 'default'.
 * - batch_size: (optional) Number of records to fetch from the database during
 *   each batch. If omitted, all records are fetched in a single query.
 * - ignore_map: (optional) Source data is joined to the map table by default to
 *   improve migration performance. If set to TRUE, the map table will not be
 *   joined. Using expressions in the query may result in column aliases in the
 *   JOIN clause which would be invalid SQL. If you run into this, set
 *   ignore_map to TRUE.
 *
 * For other optional configuration keys inherited from the parent class, refer
 * to \Drupal\migrate\Plugin\migrate\source\SourcePluginBase.
 *
 * About the source database determination:
 * - If the source plugin configuration contains 'database_state_key', its value
 *   is taken as the name of a state key which contains an array with the
 *   database configuration.
 * - Otherwise, if the source plugin configuration contains 'key', the database
 *   configuration with that name is used.
 * - If both 'database_state_key' and 'key' are omitted in the source plugin
 *   configuration, the database connection named 'migrate' is used by default.
 * - If all of the above steps fail, RequirementsException is thrown.
 *
 * Drupal Database API supports multiple database connections. The connection
 * parameters are defined in $databases array in settings.php or
 * settings.local.php. It is also possible to modify the $databases array in
 * runtime. For example, Migrate Drupal, which provides the migrations from
 * Drupal 6 / 7, asks for the source database connection parameters in the UI
 * and then adds the $databases['migrate'] connection in runtime before the
 * migrations are executed.
 *
 * As described above, the default source database is $databases['migrate']. If
 * the source plugin needs another source connection, the database connection
 * parameters should be added to the $databases array as, for instance,
 * $databases['foo']. The source plugin can then use this connection by setting
 * 'key' to 'foo' in its configuration.
 *
 * For a complete example on migrating data from an SQL source, refer to
 * https://www.drupal.org/docs/8/api/migrate-api/migrating-data-from-sql-source
 *
 * @see https://www.drupal.org/docs/8/api/database-api
 * @see \Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase
 */
abstract class SqlBase extends SourcePluginBase implements ContainerFactoryPluginInterface, RequirementsInterface {

  /**
   * The query string.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $query;

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * State service for retrieving database info.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The count of the number of batches run.
   *
   * @var int
   */
  protected $batch = 0;

  /**
   * Number of records to fetch from the database during each batch.
   *
   * A value of zero indicates no batching is to be done.
   *
   * @var int
   */
  protected $batchSize = 0;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->state = $state;
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
      $container->get('state')
    );
  }

  /**
   * Prints the query string when the object is used as a string.
   *
   * @return string
   *   The query string.
   */
  public function __toString() {
    return (string) $this->query();
  }

  /**
   * Gets the database connection object.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function getDatabase() {
    if (!isset($this->database)) {
      // Look first for an explicit state key containing the configuration.
      if (isset($this->configuration['database_state_key'])) {
        $this->database = $this->setUpDatabase($this->state->get($this->configuration['database_state_key']));
      }
      // Next, use explicit configuration in the source plugin.
      elseif (isset($this->configuration['key'])) {
        $this->database = $this->setUpDatabase($this->configuration);
      }
      // Next, try falling back to the global state key.
      elseif (($fallback_state_key = $this->state->get('migrate.fallback_state_key'))) {
        $this->database = $this->setUpDatabase($this->state->get($fallback_state_key));
      }
      // If all else fails, let setUpDatabase() fallback to the 'migrate' key.
      else {
        $this->database = $this->setUpDatabase([]);
      }
    }
    return $this->database;
  }

  /**
   * Gets a connection to the referenced database.
   *
   * This method will add the database connection if necessary.
   *
   * @param array $database_info
   *   Configuration for the source database connection. The keys are:
   *    'key' - The database connection key.
   *    'target' - The database connection target.
   *    'database' - Database configuration array as accepted by
   *      Database::addConnectionInfo.
   *
   * @return \Drupal\Core\Database\Connection
   *   The connection to use for this plugin's queries.
   *
   * @throws \Drupal\migrate\Exception\RequirementsException
   *   Thrown if no source database connection is configured.
   */
  protected function setUpDatabase(array $database_info) {
    if (isset($database_info['key'])) {
      $key = $database_info['key'];
    }
    else {
      // If there is no explicit database configuration at all, fall back to a
      // connection named 'migrate'.
      $key = 'migrate';
    }
    if (isset($database_info['target'])) {
      $target = $database_info['target'];
    }
    else {
      $target = 'default';
    }
    if (isset($database_info['database'])) {
      Database::addConnectionInfo($key, $target, $database_info['database']);
    }
    try {
      $connection = Database::getConnection($target, $key);
    }
    catch (ConnectionNotDefinedException $e) {
      // If we fell back to the magic 'migrate' connection and it doesn't exist,
      // treat the lack of the connection as a RequirementsException.
      if ($key == 'migrate') {
        throw new RequirementsException("No database connection configured for source plugin " . $this->pluginId, [], 0, $e);
      }
      else {
        throw $e;
      }
    }
    return $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    if ($this->pluginDefinition['requirements_met'] === TRUE) {
      $this->getDatabase();
    }
  }

  /**
   * Wrapper for database select.
   */
  protected function select($table, $alias = NULL, array $options = []) {
    $options['fetch'] = \PDO::FETCH_ASSOC;
    return $this->getDatabase()->select($table, $alias, $options);
  }

  /**
   * Adds tags and metadata to the query.
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
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    // Initialize the batch size.
    if ($this->batchSize == 0 && isset($this->configuration['batch_size'])) {
      // Valid batch sizes are integers >= 0.
      if (is_int($this->configuration['batch_size']) && ($this->configuration['batch_size']) >= 0) {
        $this->batchSize = $this->configuration['batch_size'];
      }
      else {
        throw new MigrateException("batch_size must be greater than or equal to zero");
      }
    }

    // If a batch has run the query is already setup.
    if ($this->batch == 0) {
      $this->prepareQuery();

      // Get the key values, for potential use in joining to the map table.
      $keys = [];

      // The rules for determining what conditions to add to the query are as
      // follows (applying first applicable rule):
      // 1. If the map is joinable, join it. We will want to accept all rows
      //    which are either not in the map, or marked in the map as NEEDS_UPDATE.
      //    Note that if high water fields are in play, we want to accept all rows
      //    above the high water mark in addition to those selected by the map
      //    conditions, so we need to OR them together (but AND with any existing
      //    conditions in the query). So, ultimately the SQL condition will look
      //    like (original conditions) AND (map IS NULL OR map needs update
      //      OR above high water).
      $conditions = $this->query->orConditionGroup();
      $condition_added = FALSE;
      $added_fields = [];
      if ($this->mapJoinable()) {
        // Build the join to the map table. Because the source key could have
        // multiple fields, we need to build things up.
        $count = 1;
        $map_join = '';
        $delimiter = '';
        foreach ($this->getIds() as $field_name => $field_schema) {
          if (isset($field_schema['alias'])) {
            $field_name = $field_schema['alias'] . '.' . $this->query->escapeField($field_name);
          }
          $map_join .= "$delimiter$field_name = map.sourceid" . $count++;
          $delimiter = ' AND ';
        }

        $alias = $this->query->leftJoin($this->migration->getIdMap()
          ->getQualifiedMapTableName(), 'map', $map_join);
        $conditions->isNull($alias . '.sourceid1');
        $conditions->condition($alias . '.source_row_status', MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
        $condition_added = TRUE;

        // And as long as we have the map table, add its data to the row.
        $n = count($this->getIds());
        for ($count = 1; $count <= $n; $count++) {
          $map_key = 'sourceid' . $count;
          $this->query->addField($alias, $map_key, "migrate_map_$map_key");
          $added_fields[] = "$alias.$map_key";
        }
        if ($n = count($this->migration->getDestinationIds())) {
          for ($count = 1; $count <= $n; $count++) {
            $map_key = 'destid' . $count++;
            $this->query->addField($alias, $map_key, "migrate_map_$map_key");
            $added_fields[] = "$alias.$map_key";
          }
        }
        $this->query->addField($alias, 'source_row_status', 'migrate_map_source_row_status');
        $added_fields[] = "$alias.source_row_status";
      }
      // 2. If we are using high water marks, also include rows above the mark.
      //    But, include all rows if the high water mark is not set.
      if ($this->getHighWaterProperty()) {
        $high_water_field = $this->getHighWaterField();
        $high_water = $this->getHighWater();
        // We check against NULL because 0 is an acceptable value for the high
        // water mark.
        if ($high_water !== NULL) {
          $conditions->condition($high_water_field, $high_water, '>');
          $condition_added = TRUE;
        }
        // Always sort by the high water field, to ensure that the first run
        // (before we have a high water value) also has the results in a
        // consistent order.
        $this->query->orderBy($high_water_field);
      }
      if ($condition_added) {
        $this->query->condition($conditions);
      }
      // If the query has a group by, our added fields need it too, to keep the
      // query valid.
      // @see https://dev.mysql.com/doc/refman/5.7/en/group-by-handling.html
      $group_by = $this->query->getGroupBy();
      if ($group_by && $added_fields) {
        foreach ($added_fields as $added_field) {
          $this->query->groupBy($added_field);
        }
      }
    }

    // Download data in batches for performance.
    if (($this->batchSize > 0)) {
      $this->query->range($this->batch * $this->batchSize, $this->batchSize);
    }
    $statement = $this->query->execute();
    $statement->setFetchMode(\PDO::FETCH_ASSOC);
    return new \IteratorIterator($statement);
  }

  /**
   * Position the iterator to the following row.
   */
  protected function fetchNextRow() {
    $this->getIterator()->next();
    // We might be out of data entirely, or just out of data in the current
    // batch. Attempt to fetch the next batch and see.
    if ($this->batchSize > 0 && !$this->getIterator()->valid()) {
      $this->fetchNextBatch();
    }
  }

  /**
   * Prepares query for the next set of data from the source database.
   */
  protected function fetchNextBatch() {
    $this->batch++;
    unset($this->iterator);
    $this->getIterator()->rewind();
  }

  /**
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  abstract public function query();

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    return (int) $this->query()->countQuery()->execute()->fetchField();
  }

  /**
   * Checks if we can join against the map table.
   *
   * This function specifically catches issues when we're migrating with
   * unique sets of credentials for the source and destination database.
   *
   * @return bool
   *   TRUE if we can join against the map table otherwise FALSE.
   */
  protected function mapJoinable() {

    // Do not join map if explicitly configured not to.
    if (isset($this->configuration['ignore_map'])  && $this->configuration['ignore_map']) {
      return FALSE;
    }

    // If we are using high water, but haven't yet set a high water mark, do not
    // join the map table, as we want to get all available records.
    if ($this->getHighWaterProperty() && $this->getHighWater() === NULL) {
      return FALSE;
    }

    // If we are tracking changes, we also need to retrieve all rows to compare
    // hashes
    if ($this->trackChanges) {
      return FALSE;
    }

    if (!$this->getIds()) {
      return FALSE;
    }
    // With batching, we want a later batch to return the same rows that would
    // have been returned at the same point within a monolithic query. If we
    // join to the map table, the first batch is writing to the map table and
    // thus affecting the results of subsequent batches. To be safe, we avoid
    // joining to the map table when batching.
    if ($this->batchSize > 0) {
      return FALSE;
    }
    $id_map = $this->migration->getIdMap();
    if (!$id_map instanceof Sql) {
      return FALSE;
    }
    $id_map_database_options = $id_map->getDatabase()->getConnectionOptions();
    $source_database_options = $this->getDatabase()->getConnectionOptions();

    // Special handling for sqlite which deals with files.
    if ($id_map_database_options['driver'] === 'sqlite' &&
      $source_database_options['driver'] === 'sqlite' &&
      $id_map_database_options['database'] != $source_database_options['database']
    ) {
      return FALSE;
    }

    // FALSE if driver is PostgreSQL and database doesn't match.
    if ($id_map_database_options['driver'] === 'pgsql' &&
      $source_database_options['driver'] === 'pgsql' &&
      $id_map_database_options['database'] != $source_database_options['database']
      ) {
      return FALSE;
    }

    foreach (['username', 'password', 'host', 'port', 'namespace', 'driver'] as $key) {
      if (isset($source_database_options[$key])) {
        if ($id_map_database_options[$key] != $source_database_options[$key]) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

}
