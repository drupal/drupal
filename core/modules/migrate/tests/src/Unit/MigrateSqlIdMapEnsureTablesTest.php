<?php

namespace Drupal\Tests\migrate\Unit;

use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Tests the SQL ID map plugin ensureTables() method.
 *
 * @group migrate
 */
class MigrateSqlIdMapEnsureTablesTest extends MigrateTestCase {

  /**
   * The migration configuration, initialized to set the ID and destination IDs.
   *
   * @var array
   */
  protected $migrationConfiguration = [
    'id' => 'sql_idmap_test',
  ];

  /**
   * Tests the ensureTables method when the tables do not exist.
   */
  public function testEnsureTablesNotExist() {
    $fields['source_ids_hash'] = [
      'type' => 'varchar',
      'length' => 64,
      'not null' => 1,
      'description' => 'Hash of source ids. Used as primary key',
    ];
    $fields['sourceid1'] = [
      'type' => 'int',
      'not null' => TRUE,
    ];
    $fields['sourceid2'] = [
      'type' => 'int',
      'not null' => TRUE,
    ];
    $fields['destid1'] = [
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ];
    $fields['source_row_status'] = [
      'type' => 'int',
      'size' => 'tiny',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => MigrateIdMapInterface::STATUS_IMPORTED,
      'description' => 'Indicates current status of the source row',
    ];
    $fields['rollback_action'] = [
      'type' => 'int',
      'size' => 'tiny',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => MigrateIdMapInterface::ROLLBACK_DELETE,
      'description' => 'Flag indicating what to do for this item on rollback',
    ];
    $fields['last_imported'] = [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
      'description' => 'UNIX timestamp of the last time this row was imported',
    ];
    $fields['hash'] = [
      'type' => 'varchar',
      'length' => '64',
      'not null' => FALSE,
      'description' => 'Hash of source row data, for detecting changes',
    ];
    $map_table_schema = [
      'description' => 'Mappings from source identifier value(s) to destination identifier value(s).',
      'fields' => $fields,
      'primary key' => ['source_ids_hash'],
      'indexes' => [
        'source' => ['sourceid1', 'sourceid2'],
      ],
    ];
    $schema = $this->getMockBuilder('Drupal\Core\Database\Schema')
      ->disableOriginalConstructor()
      ->getMock();
    $schema->expects($this->at(0))
      ->method('tableExists')
      ->with('migrate_map_sql_idmap_test')
      ->will($this->returnValue(FALSE));
    $schema->expects($this->at(1))
      ->method('createTable')
      ->with('migrate_map_sql_idmap_test', $map_table_schema);
    // Now do the message table.
    $fields = [];
    $fields['msgid'] = [
      'type' => 'serial',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ];
    $fields['source_ids_hash'] = [
      'type' => 'varchar',
      'length' => 64,
      'not null' => 1,
      'description' => 'Hash of source ids. Used as primary key',
    ];
    $fields['level'] = [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 1,
    ];
    $fields['message'] = [
      'type' => 'text',
      'size' => 'medium',
      'not null' => TRUE,
    ];
    $table_schema = [
      'description' => 'Messages generated during a migration process',
      'fields' => $fields,
      'primary key' => ['msgid'],
    ];

    $schema->expects($this->at(2))
      ->method('tableExists')
      ->with('migrate_message_sql_idmap_test')
      ->will($this->returnValue(FALSE));
    $schema->expects($this->at(3))
      ->method('createTable')
      ->with('migrate_message_sql_idmap_test', $table_schema);
    $schema->expects($this->any())
      ->method($this->anything());
    $this->runEnsureTablesTest($schema);
  }

  /**
   * Tests the ensureTables method when the tables exist.
   */
  public function testEnsureTablesExist() {
    $schema = $this->getMockBuilder('Drupal\Core\Database\Schema')
      ->disableOriginalConstructor()
      ->getMock();
    $schema->expects($this->at(0))
      ->method('tableExists')
      ->with('migrate_map_sql_idmap_test')
      ->will($this->returnValue(TRUE));
    $schema->expects($this->at(1))
      ->method('fieldExists')
      ->with('migrate_map_sql_idmap_test', 'rollback_action')
      ->will($this->returnValue(FALSE));
    $field_schema = [
      'type' => 'int',
      'size' => 'tiny',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
      'description' => 'Flag indicating what to do for this item on rollback',
    ];
    $schema->expects($this->at(2))
      ->method('addField')
      ->with('migrate_map_sql_idmap_test', 'rollback_action', $field_schema);
    $schema->expects($this->at(3))
      ->method('fieldExists')
      ->with('migrate_map_sql_idmap_test', 'hash')
      ->will($this->returnValue(FALSE));
    $field_schema = [
      'type' => 'varchar',
      'length' => '64',
      'not null' => FALSE,
      'description' => 'Hash of source row data, for detecting changes',
    ];
    $schema->expects($this->at(4))
      ->method('addField')
      ->with('migrate_map_sql_idmap_test', 'hash', $field_schema);
    $schema->expects($this->at(5))
      ->method('fieldExists')
      ->with('migrate_map_sql_idmap_test', 'source_ids_hash')
      ->will($this->returnValue(FALSE));
    $field_schema = [
      'type' => 'varchar',
      'length' => '64',
      'not null' => TRUE,
      'description' => 'Hash of source ids. Used as primary key',
    ];
    $schema->expects($this->at(6))
      ->method('addField')
      ->with('migrate_map_sql_idmap_test', 'source_ids_hash', $field_schema);
    $schema->expects($this->exactly(7))
      ->method($this->anything());
    $this->runEnsureTablesTest($schema);
  }

  /**
   * Actually run the test.
   *
   * @param array $schema
   *   The mock schema object with expectations set. The Sql constructor calls
   *   ensureTables() which in turn calls this object and the expectations on
   *   it are the actual test and there are no additional asserts added.
   */
  protected function runEnsureTablesTest($schema) {
    $database = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $database->expects($this->any())
      ->method('schema')
      ->willReturn($schema);
    $migration = $this->getMigration();
    $plugin = $this->getMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $plugin->expects($this->any())
      ->method('getIds')
      ->willReturn([
      'source_id_property' => [
        'type' => 'integer',
      ],
      'source_id_property_2' => [
        'type' => 'integer',
      ],
    ]);
    $migration->expects($this->any())
      ->method('getSourcePlugin')
      ->willReturn($plugin);
    $plugin = $this->getMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $plugin->expects($this->any())
      ->method('getIds')
      ->willReturn([
      'destination_id_property' => [
        'type' => 'string',
      ],
    ]);
    $migration->expects($this->any())
      ->method('getDestinationPlugin')
      ->willReturn($plugin);
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher */
    $event_dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $map = new TestSqlIdMap($database, [], 'sql', [], $migration, $event_dispatcher);
    $map->getDatabase();
  }

}
