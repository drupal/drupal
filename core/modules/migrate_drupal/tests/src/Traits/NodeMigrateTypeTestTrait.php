<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal\Traits;

use Drupal\migrate_drupal\NodeMigrateType;

// cspell:ignore destid sourceid

/**
 * Helper functions to test complete and classic node migrations.
 */
trait NodeMigrateTypeTestTrait {

  /**
   * The migrate_map table name.
   *
   * @var string
   */
  public $tableName = NULL;

  /**
   * Gets the numbers of complete and classic node migrate_map tables.
   *
   * @param string $version
   *   The source database version.
   *
   * @return array
   *   An associative array with the total number of complete and classic
   *   node migrate_map tables.
   */
  protected function nodeMigrateMapTableCount($version): array {
    $results = [];
    $bases = ['node', 'node_complete'];
    $tables = \Drupal::database()->schema()
      ->findTables('migrate_map_d' . $version . '_node%');

    foreach ($bases as $base) {
      $base_tables = preg_grep('/^migrate_map_d' . $version . '_' . $base . '_{2}.*$/', $tables);
      $results[$base] = count($base_tables);
    }
    return $results;
  }

  /**
   * Remove the node migrate map table.
   *
   * @param string $type
   *   The type of node migration, 'complete' or 'classic'.
   * @param string $version
   *   The source database version.
   *
   * @throws \Exception
   */
  protected function removeNodeMigrateMapTable($type, $version) {
    $name = $this->getTableName($type, $version);
    \Drupal::database()->schema()->dropTable($name);
  }

  /**
   * Gets the migrate_map table name.
   *
   * @param string $type
   *   The type of node migration, 'complete' or 'classic'.
   * @param string $version
   *   The source database version.
   *
   * @return string
   *   The migrate_map table name.
   */
  protected function getTableName($type, $version) {
    if (!$this->tableName) {
      $content_type = $this->randomMachineName();
      $this->tableName = 'migrate_map_d' . $version . '_node_complete__' . $content_type;
      if ($type == NodeMigrateType::NODE_MIGRATE_TYPE_CLASSIC) {
        $this->tableName = 'migrate_map_d' . $version . '_node__' . $content_type;
      }
    }
    return $this->tableName;
  }

  /**
   * Create a node migrate_map table.
   *
   * @param string $type
   *   The type of node migration, 'complete' or 'classic'.
   * @param string $version
   *   The source database version.
   *
   * @throws \Exception
   */
  protected function makeNodeMigrateMapTable($type, $version) {
    $name = $this->getTableName($type, $version);
    $fields = [
      'source_ids_hash' => [
        'type' => 'varchar',
        'not null' => TRUE,
        'length' => '64',
      ],
      'sourceid1' => [
        'type' => 'int',
        'not null' => TRUE,
        'size' => 'normal',
      ],
      'sourceid2' => [
        'type' => 'int',
        'not null' => TRUE,
        'size' => 'normal',
      ],
      'sourceid3' => [
        'type' => 'varchar',
        'not null' => TRUE,
        'length' => '255',
      ],
      'destid1' => [
        'type' => 'int',
        'not null' => FALSE,
        'size' => 'normal',
        'unsigned' => TRUE,
      ],
      'destid2' => [
        'type' => 'int',
        'not null' => FALSE,
        'size' => 'normal',
        'unsigned' => TRUE,
      ],
      'destid3' => [
        'type' => 'varchar_ascii',
        'not null' => FALSE,
        'length' => '12',
      ],
      'source_row_status' => [
        'type' => 'int',
        'not null' => TRUE,
        'size' => 'tiny',
        'default' => '0',
        'unsigned' => TRUE,
      ],
      'rollback_action' => [
        'type' => 'int',
        'not null' => TRUE,
        'size' => 'tiny',
        'default' => '0',
        'unsigned' => TRUE,
      ],
      'last_imported' => [
        'type' => 'int',
        'not null' => TRUE,
        'size' => 'normal',
        'default' => '0',
        'unsigned' => TRUE,
      ],
      'hash' => [
        'type' => 'varchar',
        'not null' => FALSE,
        'length' => '64',
      ],
    ];
    $values = [
      'source_ids_hash' => '123',
      'sourceid1' => '4242',
      'sourceid2' => '4242',
      'sourceid3' => 'en',
      'destid1' => '4242',
      'destid2' => '4242',
      'destid3' => 'en',
      'source_row_status' => '1',
      'rollback_action' => '1',
      'last_imported' => time(),
      'hash' => 'abc',
    ];
    $indexes = [
      'source' => [
        'sourceid1',
        'sourceid2',
        'sourceid3',
      ],
    ];

    // Remove keys not used.
    if ($type == NodeMigrateType::NODE_MIGRATE_TYPE_CLASSIC) {
      $keys = ['sourceid2', 'sourceid3', 'destid2', 'destid3'];
      foreach ($keys as $key) {
        unset($fields[$key]);
        unset($values[$key]);
        if (str_contains($key, 'sourceid')) {
          $index_key = substr($key, -1) - 1;
          unset($indexes['source'][$index_key]);
        }
      }
    }
    $connection = \Drupal::database();
    $connection->schema()->createTable($name, [
      'fields' => $fields,
      'primary key' => [
        'source_ids_hash',
      ],
      'indexes' => $indexes,
      'mysql_character_set' => 'utf8mb4',
    ]);

    $field_names = array_keys($fields);
    $connection->insert($name)
      ->fields($field_names)
      ->values($values)
      ->execute();
  }

}
