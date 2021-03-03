<?php

namespace Drupal\migrate_drupal;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\RequirementsInterface;

/**
 * Configures the appropriate migrations for a given source Drupal database.
 */
trait MigrationConfigurationTrait {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The follow-up migration tags.
   *
   * @var string[]
   */
  protected $followUpMigrationTags;

  /**
   * Gets the database connection for the source Drupal database.
   *
   * @param array $database
   *   Database array representing the source Drupal database.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection for the source Drupal database.
   */
  protected function getConnection(array $database) {
    // Set up the connection.
    Database::addConnectionInfo('upgrade', 'default', $database);
    $connection = Database::getConnection('default', 'upgrade');
    return $connection;
  }

  /**
   * Gets the system data from the system table of the source Drupal database.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection to the source Drupal database.
   *
   * @return array
   *   The system data from the system table of the source Drupal database.
   */
  protected function getSystemData(Connection $connection) {
    $system_data = [];
    try {
      $results = $connection->select('system', 's', [
        'fetch' => \PDO::FETCH_ASSOC,
      ])
        ->fields('s')
        ->execute();
      foreach ($results as $result) {
        $system_data[$result['type']][$result['name']] = $result;
      }
    }
    catch (DatabaseExceptionWrapper $e) {
      // The table might not exist for example in tests.
    }
    return $system_data;
  }

  /**
   * Creates the necessary state entries for SqlBase::getDatabase() to work.
   *
   * The state entities created here have to exist before migration plugin
   * instances are created so that derivers such as
   * \Drupal\taxonomy\Plugin\migrate\D6TermNodeDeriver can access the source
   * database.
   *
   * @param array $database
   *   The source database settings.
   * @param string $drupal_version
   *   The Drupal version.
   *
   * @see \Drupal\migrate\Plugin\migrate\source\SqlBase::getDatabase()
   */
  protected function createDatabaseStateSettings(array $database, $drupal_version) {
    $database_state['key'] = 'upgrade';
    $database_state['database'] = $database;
    $database_state_key = 'migrate_drupal_' . $drupal_version;
    $state = $this->getState();
    $state->set($database_state_key, $database_state);
    $state->set('migrate.fallback_state_key', $database_state_key);
  }

  /**
   * Gets the migrations for import.
   *
   * @param string $database_state_key
   *   The state key.
   * @param int $drupal_version
   *   The version of Drupal we're getting the migrations for.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface[]
   *   The migrations for import.
   */
  protected function getMigrations($database_state_key, $drupal_version) {
    $version_tag = 'Drupal ' . $drupal_version;
    /** @var \Drupal\migrate\Plugin\MigrationInterface[] $all_migrations */
    $all_migrations = $this->getMigrationPluginManager()->createInstancesByTag($version_tag);

    // Unset the node migrations that should not run based on the type of node
    // migration. That is, if this is a complete node migration then unset the
    // classic node migrations and if this is a classic node migration then
    // unset the complete node migrations.
    $type = NodeMigrateType::getNodeMigrateType(\Drupal::database(), $drupal_version);
    switch ($type) {
      case NodeMigrateType::NODE_MIGRATE_TYPE_COMPLETE:
        $patterns = '/(d' . $drupal_version . '_node:)|(d' . $drupal_version . '_node_translation:)|(d' . $drupal_version . '_node_revision:)|(d7_node_entity_translation:)/';
        break;

      case NodeMigrateType::NODE_MIGRATE_TYPE_CLASSIC:
        $patterns = '/(d' . $drupal_version . '_node_complete:)/';
        break;
    }
    foreach ($all_migrations as $key => $migrations) {
      if (preg_match($patterns, $key)) {
        unset($all_migrations[$key]);
      }
    }

    $migrations = [];
    foreach ($all_migrations as $migration) {
      // Skip migrations tagged with any of the follow-up migration tags. They
      // will be derived and executed after the migrations on which they depend
      // have been successfully executed.
      // @see Drupal\migrate_drupal\Plugin\MigrationWithFollowUpInterface
      if (!empty(array_intersect($migration->getMigrationTags(), $this->getFollowUpMigrationTags()))) {
        continue;
      }

      try {
        // @todo https://drupal.org/node/2681867 We should be able to validate
        //   the entire migration at this point.
        $source_plugin = $migration->getSourcePlugin();
        if ($source_plugin instanceof RequirementsInterface) {
          $source_plugin->checkRequirements();
        }
        $destination_plugin = $migration->getDestinationPlugin();
        if ($destination_plugin instanceof RequirementsInterface) {
          $destination_plugin->checkRequirements();
        }
        $migrations[] = $migration;
      }
      catch (RequirementsException $e) {
        // Migrations which are not applicable given the source and destination
        // site configurations (e.g., what modules are enabled) will be silently
        // ignored.
      }
    }

    return $migrations;
  }

  /**
   * Returns the follow-up migration tags.
   *
   * @return string[]
   */
  protected function getFollowUpMigrationTags() {
    if ($this->followUpMigrationTags === NULL) {
      $this->followUpMigrationTags = $this->getConfigFactory()
        ->get('migrate_drupal.settings')
        ->get('follow_up_migration_tags') ?: [];
    }
    return $this->followUpMigrationTags;
  }

  /**
   * Determines what version of Drupal the source database contains.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   *
   * @return string|false
   *   A string representing the major branch of Drupal core (e.g. '6' for
   *   Drupal 6.x), or FALSE if no valid version is matched.
   */
  public static function getLegacyDrupalVersion(Connection $connection) {
    // Don't assume because a table of that name exists, that it has the columns
    // we're querying. Catch exceptions and report that the source database is
    // not Drupal.
    // Drupal 5/6/7 can be detected by the schema_version in the system table.
    if ($connection->schema()->tableExists('system')) {
      try {
        $version_string = $connection
          ->query('SELECT [schema_version] FROM {system} WHERE [name] = :module', [':module' => 'system'])
          ->fetchField();
        if ($version_string && $version_string[0] == '1') {
          if ((int) $version_string >= 1000) {
            $version_string = '5';
          }
          else {
            $version_string = FALSE;
          }
        }
      }
      catch (\PDOException $e) {
        $version_string = FALSE;
      }
    }
    // For Drupal 8 (and we're predicting beyond) the schema version is in the
    // key_value store.
    elseif ($connection->schema()->tableExists('key_value')) {
      try {
        $result = $connection
          ->query("SELECT [value] FROM {key_value} WHERE [collection] = :system_schema AND [name] = :module", [
            ':system_schema' => 'system.schema',
            ':module' => 'system',
          ])
          ->fetchField();
        $version_string = unserialize($result);
      }
      catch (DatabaseExceptionWrapper $e) {
        $version_string = FALSE;
      }
    }
    else {
      $version_string = FALSE;
    }

    return $version_string ? substr($version_string, 0, 1) : FALSE;
  }

  /**
   * Gets the config factory service.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory service.
   */
  protected function getConfigFactory() {
    if (!$this->configFactory) {
      $this->configFactory = \Drupal::service('config.factory');
    }

    return $this->configFactory;
  }

  /**
   * Gets the migration plugin manager service.
   *
   * @return \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   *   The migration plugin manager service.
   */
  protected function getMigrationPluginManager() {
    if (!$this->migrationPluginManager) {
      $this->migrationPluginManager = \Drupal::service('plugin.manager.migration');
    }

    return $this->migrationPluginManager;
  }

  /**
   * Gets the state service.
   *
   * @return \Drupal\Core\State\StateInterface
   *   The state service.
   */
  protected function getState() {
    if (!$this->state) {
      $this->state = \Drupal::service('state');
    }

    return $this->state;
  }

}
