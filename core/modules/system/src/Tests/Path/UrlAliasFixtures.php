<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Path\UrlAliasFixtures.
 */

namespace Drupal\system\Tests\Path;

use Drupal\Core\Database\Connection;

/**
 * Utility methods to generate sample data, database configuration, etc.
 */
class UrlAliasFixtures {

  /**
   * Create the tables required for the sample data.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection to use to create the tables.
   */
  public function createTables(Connection $connection) {
    $tables = $this->tableDefinition();
    $schema = $connection->schema();

    foreach ($tables as $name => $table) {
      $schema->dropTable($name);
      $schema->createTable($name, $table);
    }
  }

  /**
   * Drop the tables used for the sample data.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection to use to drop the tables.
   */
  public function dropTables(Connection $connection) {
    $tables = $this->tableDefinition();
    $schema = $connection->schema();

    foreach ($tables as $name => $table) {
      $schema->dropTable($name);
    }
  }

  /**
   * Returns an array of URL aliases for testing.
   *
   * @return array of URL alias definitions.
   */
  public function sampleUrlAliases() {
    return array(
      array(
        'source' => '/node/1',
        'alias' => '/alias_for_node_1_en',
        'langcode' => 'en'
      ),
      array(
        'source' => '/node/2',
        'alias' => '/alias_for_node_2_en',
        'langcode' => 'en'
      ),
      array(
        'source' => '/node/1',
        'alias' => '/alias_for_node_1_fr',
        'langcode' => 'fr'
      ),
      array(
        'source' => '/node/1',
        'alias' => '/alias_for_node_1_und',
        'langcode' => 'und'
      )
    );
  }


  /**
   * Returns the table definition for the URL alias fixtures.
   *
   * @return array
   *   Table definitions.
   */
  public function tableDefinition() {
    $tables = array();

    // Prime the drupal_get_filename() cache with the location of the system
    // module as its location is known and shouldn't change.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    drupal_get_filename('module', 'system', 'core/modules/system/system.info.yml');
    module_load_install('system');
    $schema = system_schema();

    $tables['url_alias'] = $schema['url_alias'];
    $tables['key_value'] = $schema['key_value'];

    return $tables;
  }
}
