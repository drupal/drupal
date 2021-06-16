<?php

namespace Drupal\migrate_drupal;

use Drupal\Core\Database\Connection;
use Drupal\Core\Site\Settings;

/**
 * Provides a class to determine the type of migration.
 */
final class NodeMigrateType {

  use MigrationConfigurationTrait;

  /**
   * Only the complete node migration map tables are in use.
   */
  const NODE_MIGRATE_TYPE_COMPLETE = 'COMPLETE';

  /**
   * Only the classic node migration map tables are in use.
   */
  const NODE_MIGRATE_TYPE_CLASSIC = 'CLASSIC';

  /**
   * Determines the type of node migration to be used.
   *
   * The node complete migration is the default. It is not used when there
   * are existing tables for dN_node.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection to the target database.
   * @param string|false $version
   *   The Drupal version of the source database, FALSE if it cannot be
   *   determined.
   *
   * @return string
   *   The migrate type.
   *
   * @internal
   */
  public static function getNodeMigrateType(Connection $connection, $version) {
    $migrate_node_migrate_type_classic = Settings::get('migrate_node_migrate_type_classic', FALSE);
    if ($migrate_node_migrate_type_classic) {
      return static::NODE_MIGRATE_TYPE_CLASSIC;
    }

    $migrate_type = static::NODE_MIGRATE_TYPE_COMPLETE;
    if ($version) {
      // Create the variable name, 'node_has_rows' or 'node_complete_exists' and
      // set it the default value, FALSE.
      $node_has_rows = FALSE;
      $node_complete_has_rows = FALSE;

      // Find out what migrate map tables have rows for the node migrations.
      // It is either the classic, 'dN_node', or the complete,
      // 'dN_node_complete', or both. This is used to determine which migrations
      // are run and if migrations using the node migrations in a
      // migration_lookup are altered.
      $bases = ['node', 'node_complete'];
      $tables = $connection->schema()
        ->findTables('migrate_map_d' . $version . '_node%');
      foreach ($bases as $base) {
        $has_rows = $base . '_has_rows';
        $base_tables = preg_grep('/^migrate_map_d' . $version . '_' . $base . '_{2}.*$/', $tables);
        // Set the has_rows True when a map table has rows with a positive
        // count for the matched migration.
        foreach ($base_tables as $base_table) {
          if ($connection->schema()->tableExists($base_table)) {
            $count = $connection->select($base_table)->countQuery()
              ->execute()->fetchField();
            if ($count > 0) {
              $$has_rows = TRUE;
              break;
            }
          }
        }
      }

      // Set the node migration type to use.
      if ($node_has_rows && !$node_complete_has_rows) {
        $migrate_type = static::NODE_MIGRATE_TYPE_CLASSIC;
      }
    }
    return $migrate_type;
  }

}
