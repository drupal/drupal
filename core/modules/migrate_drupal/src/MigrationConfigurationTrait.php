<?php

namespace Drupal\migrate_drupal;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\RequirementsInterface;

/**
 * Configures the appropriate migrations for a given source Drupal database.
 */
trait MigrationConfigurationTrait {

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
    catch (\Exception $e) {
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
    \Drupal::state()->set($database_state_key, $database_state);
    \Drupal::state()->set('migrate.fallback_state_key', $database_state_key);
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
    $plugin_manager = \Drupal::service('plugin.manager.migration');
    /** @var \Drupal\migrate\Plugin\Migration[] $all_migrations */
    $all_migrations = $plugin_manager->createInstancesByTag($version_tag);
    $migrations = [];
    foreach ($all_migrations as $migration) {
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
   * Determines what version of Drupal the source database contains.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   *
   * @return int|false
   *   An integer representing the major branch of Drupal core (e.g. '6' for
   *   Drupal 6.x), or FALSE if no valid version is matched.
   */
  protected function getLegacyDrupalVersion(Connection $connection) {
    // Don't assume because a table of that name exists, that it has the columns
    // we're querying. Catch exceptions and report that the source database is
    // not Drupal.
    // Drupal 5/6/7 can be detected by the schema_version in the system table.
    if ($connection->schema()->tableExists('system')) {
      try {
        $version_string = $connection
          ->query('SELECT schema_version FROM {system} WHERE name = :module', [':module' => 'system'])
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
      $result = $connection
        ->query("SELECT value FROM {key_value} WHERE collection = :system_schema  and name = :module", [':system_schema' => 'system.schema', ':module' => 'system'])
        ->fetchField();
      $version_string = unserialize($result);
    }
    else {
      $version_string = FALSE;
    }

    return $version_string ? substr($version_string, 0, 1) : FALSE;
  }

}
