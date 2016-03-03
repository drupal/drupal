<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\MigrationCreationTrait.
 */

namespace Drupal\migrate_drupal;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Creates the appropriate migrations for a given source Drupal database.
 */
trait MigrationCreationTrait {

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
   * @param array $database
   *   Database array representing the source Drupal database.
   *
   * @return array
   *   The system data from the system table of the source Drupal database.
   */
  protected function getSystemData(array $database) {
    $connection = $this->getConnection($database);
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
   * Sets up the relevant migrations for import from a database connection.
   *
   * @param array $database
   *   Database array representing the source Drupal database.
   * @param string $source_base_path
   *   (Optional) Address of the source Drupal site (e.g., http://example.com/).
   *
   * @return array
   *   An array of the migration templates (parsed YAML config arrays) that were
   *   tagged for the identified source Drupal version. The templates are
   *   populated with database state key and file source base path information
   *   for execution. The array is keyed by migration IDs.
   *
   * @throws \Exception
   */
  protected function getMigrationTemplates(array $database, $source_base_path = '') {
    // Set up the connection.
    $connection = $this->getConnection($database);
    if (!$drupal_version = $this->getLegacyDrupalVersion($connection)) {
      throw new \Exception('Source database does not contain a recognizable Drupal version.');
    }
    $database_state['key'] = 'upgrade';
    $database_state['database'] = $database;
    $database_state_key = 'migrate_drupal_' . $drupal_version;
    \Drupal::state()->set($database_state_key, $database_state);

    $version_tag = 'Drupal ' . $drupal_version;

    $template_storage = \Drupal::service('migrate.template_storage');
    $migration_templates = $template_storage->findTemplatesByTag($version_tag);
    foreach ($migration_templates as $id => $template) {
      $migration_templates[$id]['source']['database_state_key'] = $database_state_key;
      // Configure file migrations so they can find the files.
      if ($template['destination']['plugin'] == 'entity:file') {
        if ($source_base_path) {
          // Make sure we have a single trailing slash.
          $source_base_path = rtrim($source_base_path, '/') . '/';
          $migration_templates[$id]['destination']['source_base_path'] = $source_base_path;
        }
      }
    }
    return $migration_templates;
  }

  /**
   * Gets the migrations for import.
   *
   * Uses the migration template connection to ensure that only the relevant
   * migrations are returned.
   *
   * @param array $migration_templates
   *   Migration templates (parsed YAML config arrays), keyed by the ID.
   *
   * @return \Drupal\migrate\Entity\MigrationInterface[]
   *   The migrations for import.
   */
  protected function getMigrations(array $migration_templates) {
    // Let the builder service create our migration configuration entities from
    // the templates, expanding them to multiple entities where necessary.
    /** @var \Drupal\migrate\MigrationBuilder $builder */
    $builder = \Drupal::service('migrate.migration_builder');
    $initial_migrations = $builder->createMigrations($migration_templates);
    $migrations = [];
    foreach ($initial_migrations as $migration) {
      try {
        // Any plugin that has specific requirements to check will implement
        // RequirementsInterface.
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
      // Migrations which are not applicable given the source and destination
      // site configurations (e.g., what modules are enabled) will be silently
      // ignored.
      catch (RequirementsException $e) {
      }
      catch (PluginNotFoundException $e) {
      }
    }

    return $migrations;
  }

  /**
   * Saves the migrations for import from the provided template connection.
   *
   * @param array $migration_templates
   *   Migration template.
   *
   * @return array
   *   The migration IDs sorted in dependency order.
   */
  protected function createMigrations(array $migration_templates) {
    $migration_ids = [];
    $migrations = $this->getMigrations($migration_templates);
    foreach ($migrations as $migration) {
      // Don't try to resave migrations that already exist.
      if (!Migration::load($migration->id())) {
        $migration->save();
      }
      $migration_ids[] = $migration->id();
    }
    // loadMultiple will sort the migrations in dependency order.
    return array_keys(Migration::loadMultiple($migration_ids));
  }

  /**
   * Determines what version of Drupal the source database contains.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   *
   * @return int|FALSE
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
