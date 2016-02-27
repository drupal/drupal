<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\MigrateSqlIdMapTest.
 */

namespace Drupal\Tests\migrate\Unit;

use Drupal\Core\Database\Driver\sqlite\Connection;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;

/**
 * Tests the SQL ID map plugin.
 *
 * @group migrate
 */
class MigrateSqlIdMapTest extends MigrateTestCase {

  /**
   * The migration configuration, initialized to set the ID and destination IDs.
   *
   * @var array
   */
  protected $migrationConfiguration = [
    'id' => 'sql_idmap_test',
  ];

  /**
   * The source IDs.
   *
   * @var array
   */
  protected $sourceIds = [
    'source_id_property' => [
      'type' => 'string',
    ],
  ];

  /**
   * The destination IDs.
   *
   * @var array
   */
  protected $destinationIds = [
    'destination_id_property' => [
      'type' => 'string',
    ],
  ];

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->database = $this->getDatabase([]);
  }

  /**
   * Saves a single ID mapping row in the database.
   *
   * @param array $map
   *   The row to save.
   */
  protected function saveMap(array $map) {
    $table = 'migrate_map_sql_idmap_test';

    $schema = $this->database->schema();
    // If the table already exists, add any columns which are in the map array,
    // but don't yet exist in the table. Yay, flexibility!
    if ($schema->tableExists($table)) {
      foreach (array_keys($map) as $field) {
        if (!$schema->fieldExists($table, $field)) {
          $schema->addField($table, $field, ['type' => 'text']);
        }
      }
    }
    else {
      $schema->createTable($table, $this->createSchemaFromRow($map));
    }

    $this->database->insert($table)->fields($map)->execute();
  }

  /**
   * Creates a test SQL ID map plugin.
   *
   * @return \Drupal\Tests\migrate\Unit\TestSqlIdMap
   *   A SQL ID map plugin test instance.
   */
  protected function getIdMap() {
    $migration = $this->getMigration();

    $plugin = $this->getMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $plugin
      ->method('getIds')
      ->willReturn($this->sourceIds);
    $migration
      ->method('getSourcePlugin')
      ->willReturn($plugin);

    $plugin = $this->getMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $plugin
      ->method('getIds')
      ->willReturn($this->destinationIds);
    $migration
      ->method('getDestinationPlugin')
      ->willReturn($plugin);
    $event_dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

    $id_map = new TestSqlIdMap($this->database, [], 'sql', [], $migration, $event_dispatcher);
    $migration
      ->method('getIdMap')
      ->willReturn($id_map);

    return $id_map;
  }

  /**
   * Sets defaults for SQL ID map plugin tests.
   *
   * @return array
   *   An associative array with the following keys:
   *   - source_row_status
   *   - rollback_action
   *   - hash
   */
  protected function idMapDefaults() {
    $defaults = array(
      'source_row_status' => MigrateIdMapInterface::STATUS_IMPORTED,
      'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
      'hash' => '',
    );
    // By default, the PDO SQLite driver strongly prefers to return strings
    // from SELECT queries. Even for columns that don't store strings. Even
    // if the connection's STRINGIFY_FETCHES attribute is FALSE. This can cause
    // assertSame() calls to fail, since 0 !== '0'. Casting these values to
    // strings isn't the most elegant workaround, but it allows the assertions
    // to pass properly.
    if ($this->database->driver() == 'sqlite') {
      $defaults['source_row_status'] = (string) $defaults['source_row_status'];
      $defaults['rollback_action'] = (string) $defaults['rollback_action'];
    }
    return $defaults;
  }

  /**
   * Tests the ID mapping method.
   *
   * Create two ID mappings and update the second to verify that:
   * - saving new to empty tables work.
   * - saving new to nonempty tables work.
   * - updating work.
   */
  public function testSaveIdMapping() {
    $source = array(
      'source_id_property' => 'source_value',
    );
    $row = new Row($source, ['source_id_property' => []]);
    $id_map = $this->getIdMap();
    $id_map->saveIdMapping($row, ['destination_id_property' => 2]);
    $expected_result = [
      [
        'sourceid1' => 'source_value',
        'source_ids_hash' => $this->getIdMap()->getSourceIDsHash($source),
        'destid1' => 2,
      ] + $this->idMapDefaults(),
    ];
    $this->queryResultTest($this->getIdMapContents(), $expected_result);
    $source = [
      'source_id_property' => 'source_value_1',
    ];
    $row = new Row($source, ['source_id_property' => []]);
    $id_map->saveIdMapping($row, ['destination_id_property' => 3]);
    $expected_result[] = [
      'sourceid1' => 'source_value_1',
      'source_ids_hash' => $this->getIdMap()->getSourceIDsHash($source),
      'destid1' => 3,
    ] + $this->idMapDefaults();
    $this->queryResultTest($this->getIdMapContents(), $expected_result);
    $id_map->saveIdMapping($row, ['destination_id_property' => 4]);
    $expected_result[1]['destid1'] = 4;
    $this->queryResultTest($this->getIdMapContents(), $expected_result);
  }

  /**
   * Tests the SQL ID map set message method.
   */
  public function testSetMessage() {
    $message = $this->getMock('Drupal\migrate\MigrateMessageInterface');
    $id_map = $this->getIdMap();
    $id_map->setMessage($message);
    $this->assertAttributeEquals($message, 'message', $id_map);
  }

  /**
   * Tests the clear messages method.
   */
  public function testClearMessages() {
    $message = 'Hello world.';
    $expected_results = [0, 1, 2, 3];
    $id_map = $this->getIdMap();

    // Insert 4 message for later delete.
    foreach ($expected_results as $key => $expected_result) {
      $id_map->saveMessage(['source_id_property' => $key], $message);
    }

    // Truncate and check that 4 messages were deleted.
    $this->assertEquals($id_map->messageCount(), 4);
    $id_map->clearMessages();
    $count = $id_map->messageCount();
    $this->assertEquals($count, 0);
  }

  /**
   * Tests the getRowsNeedingUpdate method for rows that need an update.
   */
  public function testGetRowsNeedingUpdate() {
    $id_map = $this->getIdMap();
    $row_statuses = [
      MigrateIdMapInterface::STATUS_IMPORTED,
      MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
      MigrateIdMapInterface::STATUS_IGNORED,
      MigrateIdMapInterface::STATUS_FAILED,
    ];
    // Create a mapping row for each STATUS constant.
    foreach ($row_statuses as $status) {
      $source = ['source_id_property' => 'source_value_' . $status];
      $row = new Row($source, ['source_id_property' => []]);
      $destination = ['destination_id_property' => 'destination_value_' . $status];
      $id_map->saveIdMapping($row, $destination, $status);
      $expected_results[] = [
        'sourceid1' => 'source_value_' . $status,
        'source_ids_hash' => $this->getIdMap()->getSourceIDsHash($source),
        'destid1' => 'destination_value_' . $status,
        'source_row_status' => $status,
        'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
        'hash' => '',
      ];
      // Assert zero rows need an update.
      if ($status == MigrateIdMapInterface::STATUS_IMPORTED) {
        $rows_needing_update = $id_map->getRowsNeedingUpdate(1);
        $this->assertCount(0, $rows_needing_update);
      }
    }
    // Assert that test values exist.
    $this->queryResultTest($this->getIdMapContents(), $expected_results);

    // Assert a single row needs an update.
    $row_needing_update = $id_map->getRowsNeedingUpdate(1);
    $this->assertCount(1, $row_needing_update);

    // Assert the row matches its original source.
    $source_id = $expected_results[MigrateIdMapInterface::STATUS_NEEDS_UPDATE]['sourceid1'];
    $test_row = $id_map->getRowBySource(['source_id_property' => $source_id]);
    // $row_needing_update is an array of objects returned from the database,
    // but $test_row is an array, so the cast is necessary.
    $this->assertSame($test_row, (array) $row_needing_update[0]);

    // Add additional row that needs an update.
    $source = ['source_id_property' => 'source_value_multiple'];
    $row = new Row($source, ['source_id_property' => []]);
    $destination = ['destination_id_property' => 'destination_value_multiple'];
    $id_map->saveIdMapping($row, $destination, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);

    // Assert multiple rows need an update.
    $rows_needing_update = $id_map->getRowsNeedingUpdate(2);
    $this->assertCount(2, $rows_needing_update);
  }

  /**
   * Tests the SQL ID map message count method by counting and saving messages.
   */
  public function testMessageCount() {
    $message = 'Hello world.';
    $expected_results = [0, 1, 2, 3];
    $id_map = $this->getIdMap();

    // Test count message multiple times starting from 0.
    foreach ($expected_results as $key => $expected_result) {
      $count = $id_map->messageCount();
      $this->assertEquals($expected_result, $count);
      $id_map->saveMessage(['source_id_property' => $key], $message);
    }
  }

  /**
   * Tests the SQL ID map save message method.
   */
  public function testMessageSave() {
    $message = 'Hello world.';
    $original_values = [
      1 => ['message' => $message, 'level' => MigrationInterface::MESSAGE_ERROR],
      2 => ['message' => $message, 'level' => MigrationInterface::MESSAGE_WARNING],
      3 => ['message' => $message, 'level' => MigrationInterface::MESSAGE_NOTICE],
      4 => ['message' => $message, 'level' => MigrationInterface::MESSAGE_INFORMATIONAL],
    ];
    $expected_results = [
      '7ad742edb7e866caa78ced1e4455d2e9cbd8adb2074e7c323d21b4e67732e755' => ['message' => $message, 'level' => MigrationInterface::MESSAGE_ERROR],
      '2d3ec2b0c547e819346e6ae03f881fd9f5c978ff3cbe29dfb807d40735e53703' => ['message' => $message, 'level' => MigrationInterface::MESSAGE_WARNING],
      '12a042f72cad9a2a8c7715df0c7695d762975f0687d87f5d480725dae1432a6f' => ['message' => $message, 'level' => MigrationInterface::MESSAGE_NOTICE],
      'd9d1fd27a2447ace48f47a2e9ff649673f67b446d9381a7963c949fc083f8791' => ['message' => $message, 'level' => MigrationInterface::MESSAGE_INFORMATIONAL],
    ];
    $id_map = $this->getIdMap();

    foreach ($original_values as $key => $original_value) {
      $id_map->saveMessage(['source_id_property' => $key], $message, $original_value['level']);
    }

    foreach ($id_map->getMessageIterator() as $message_row) {
      $key = $message_row->source_ids_hash;
      $this->assertEquals($expected_results[$key]['message'], $message_row->message);
      $this->assertEquals($expected_results[$key]['level'], $message_row->level);
    }

    // Insert with default level.
    $message_default = 'Hello world default.';
    $id_map->saveMessage(['source_id_property' => 5], $message_default);
    $messages = $id_map->getMessageIterator(['source_id_property' => 5]);
    $count = 0;
    foreach ($messages as $key => $message_row) {
      $count = 1;
      $this->assertEquals($message_default, $message_row->message);
      $this->assertEquals(MigrationInterface::MESSAGE_ERROR, $message_row->level);
    }
    $this->assertEquals($count, 1);

    // Retrieve messages with a specific level.
    $messages = $id_map->getMessageIterator([], MigrationInterface::MESSAGE_WARNING);
    $count = 0;
    foreach ($messages as $key => $message_row) {
      $count = 1;
      $this->assertEquals(MigrationInterface::MESSAGE_WARNING, $message_row->level);
    }
    $this->assertEquals($count, 1);
  }

  /**
   * Tests the getRowBySource method.
   */
  public function testGetRowBySource() {
    $this->getDatabase([]);
    $row = [
      'sourceid1' => 'source_id_value_1',
      'sourceid2' => 'source_id_value_2',
      'source_ids_hash' => $this->getIdMap()->getSourceIDsHash(['source_id_property' => 'source_id_value_1']),
      'destid1' => 'destination_id_value_1',
    ] + $this->idMapDefaults();
    $this->saveMap($row);
    $row = [
      'sourceid1' => 'source_id_value_3',
      'sourceid2' => 'source_id_value_4',
      'source_ids_hash' => $this->getIdMap()->getSourceIDsHash(['source_id_property' => 'source_id_value_3', 'sourceid2' => 'source_id_value_4']),
      'destid1' => 'destination_id_value_2',
    ] + $this->idMapDefaults();
    $this->saveMap($row);
    $source_id_values = ['source_id_property' => $row['sourceid1'], 'sourceid2' => $row['sourceid2']];
    $id_map = $this->getIdMap();
    $result_row = $id_map->getRowBySource($source_id_values);
    $this->assertSame($row, $result_row);
    $source_id_values = ['source_id_property' => 'missing_value_1', 'sourceid2' => 'missing_value_2'];
    $result_row = $id_map->getRowBySource($source_id_values);
    $this->assertFalse($result_row);
  }

  /**
   * Data provider for testLookupDestinationIdMapping().
   *
   * Scenarios to test (for both hits and misses) are:
   * - Single-value source ID to single-value destination ID.
   * - Multi-value source ID to multi-value destination ID.
   * - Single-value source ID to multi-value destination ID.
   * - Multi-value source ID to single-value destination ID.
   *
   * @return array
   *   An array of data values.
   */
  public function lookupDestinationIdMappingDataProvider() {
    return [
      [1, 1],
      [2, 2],
      [1, 2],
      [2, 1],
    ];
  }

  /**
   * Performs destination ID test on source and destination fields.
   *
   * @param int $num_source_fields
   *   Number of source fields to test.
   * @param int $num_destination_fields
   *   Number of destination fields to test.
   *
   * @dataProvider lookupDestinationIdMappingDataProvider
   */
  public function testLookupDestinationIdMapping($num_source_fields, $num_destination_fields) {
    // Adjust the migration configuration according to the number of source and
    // destination fields.
    $this->sourceIds = [];
    $this->destinationIds = [];
    $source_id_values = [];
    $nonexistent_id_values = [];
    $row = $this->idMapDefaults();
    for ($i = 1; $i <= $num_source_fields; $i++) {
      $row["sourceid$i"] = "source_id_value_$i";
      $source_id_values[] = "source_id_value_$i";
      $nonexistent_id_values[] = "nonexistent_source_id_value_$i";
      $this->sourceIds["source_id_property_$i"] = [];
    }
    $expected_result = [];
    for ($i = 1; $i <= $num_destination_fields; $i++) {
      $row["destid$i"] = "destination_id_value_$i";
      $expected_result[] = "destination_id_value_$i";
      $this->destinationIds["destination_id_property_$i"] = [];
    }
    $row['source_ids_hash'] = $this->getIdMap()->getSourceIDsHash($source_id_values);
    $this->saveMap($row);
    $id_map = $this->getIdMap();
    // Test for a valid hit.
    $destination_id = $id_map->lookupDestinationId($source_id_values);
    $this->assertSame($expected_result, $destination_id);
    // Test for a miss.
    $destination_id = $id_map->lookupDestinationId($nonexistent_id_values);
    $this->assertSame(0, count($destination_id));
  }

  /**
   * Tests the getRowByDestination method.
   */
  public function testGetRowByDestination() {
    $row = [
      'sourceid1' => 'source_id_value_1',
      'sourceid2' => 'source_id_value_2',
      'source_ids_hash' => $this->getIdMap()->getSourceIDsHash(['source_id_property' => 'source_id_value_1']),
      'destid1' => 'destination_id_value_1',
    ] + $this->idMapDefaults();
    $this->saveMap($row);
    $row = [
      'sourceid1' => 'source_id_value_3',
      'sourceid2' => 'source_id_value_4',
      'source_ids_hash' => $this->getIdMap()->getSourceIDsHash(['source_id_property' => 'source_id_value_3']),
      'destid1' => 'destination_id_value_2',
    ] + $this->idMapDefaults();
    $this->saveMap($row);
    $dest_id_values = ['destination_id_property' => $row['destid1']];
    $id_map = $this->getIdMap();
    $result_row = $id_map->getRowByDestination($dest_id_values);
    $this->assertSame($row, $result_row);
    // This value does not exist.
    $dest_id_values = ['destination_id_property' => 'invalid_destination_id_property'];
    $id_map = $this->getIdMap();
    $result_row = $id_map->getRowByDestination($dest_id_values);
    $this->assertFalse($result_row);
  }

  /**
   * Data provider for testLookupSourceIDMapping().
   *
   * Scenarios to test (for both hits and misses) are:
   * - Single-value destination ID to single-value source ID.
   * - Multi-value destination ID to multi-value source ID.
   * - Single-value destination ID to multi-value source ID.
   * - Multi-value destination ID to single-value source ID.
   *
   * @return array
   *   An array of data values.
   */
  public function lookupSourceIDMappingDataProvider() {
    return [
      [1, 1],
      [2, 2],
      [1, 2],
      [2, 1],
    ];
  }

  /**
   * Performs the source ID test on source and destination fields.
   *
   * @param int $num_source_fields
   *   Number of source fields to test.
   * @param int $num_destination_fields
   *   Number of destination fields to test.
   *
   * @dataProvider lookupSourceIDMappingDataProvider
   */
  public function testLookupSourceIDMapping($num_source_fields, $num_destination_fields) {
    // Adjust the migration configuration according to the number of source and
    // destination fields.
    $this->sourceIds = [];
    $this->destinationIds = [];
    $row = $this->idMapDefaults();
    $source_ids_values = [];
    $expected_result = [];
    for ($i = 1; $i <= $num_source_fields; $i++) {
      $row["sourceid$i"] = "source_id_value_$i";
      $source_ids_values = [$row["sourceid$i"]];
      $expected_result["source_id_property_$i"] = "source_id_value_$i";
      $this->sourceIds["source_id_property_$i"] = [];
    }
    $destination_id_values = [];
    $nonexistent_id_values = [];
    for ($i = 1; $i <= $num_destination_fields; $i++) {
      $row["destid$i"] = "destination_id_value_$i";
      $destination_id_values["destination_id_property_$i"] = "destination_id_value_$i";
      $nonexistent_id_values["destination_id_property_$i"] = "nonexistent_destination_id_value_$i";
      $this->destinationIds["destination_id_property_$i"] = [];
    }
    $row['source_ids_hash'] = $this->getIdMap()->getSourceIDsHash($source_ids_values);
    $this->saveMap($row);
    $id_map = $this->getIdMap();
    // Test for a valid hit.
    $source_id = $id_map->lookupSourceID($destination_id_values);
    $this->assertSame($expected_result, $source_id);
    // Test for a miss.
    $source_id = $id_map->lookupSourceID($nonexistent_id_values);
    $this->assertSame(0, count($source_id));
  }

  /**
   * Tests the imported count method.
   *
   * Scenarios to test for:
   * - No imports.
   * - One import.
   * - Multiple imports.
   */
  public function testImportedCount() {
    $id_map = $this->getIdMap();
    // Add a single failed row and assert zero imported rows.
    $source = ['source_id_property' => 'source_value_failed'];
    $row = new Row($source, ['source_id_property' => []]);
    $destination = ['destination_id_property' => 'destination_value_failed'];
    $id_map->saveIdMapping($row, $destination, MigrateIdMapInterface::STATUS_FAILED);
    $this->assertSame(0, (int) $id_map->importedCount());

    // Add an imported row and assert single count.
    $source = ['source_id_property' => 'source_value_imported'];
    $row = new Row($source, ['source_id_property' => []]);
    $destination = ['destination_id_property' => 'destination_value_imported'];
    $id_map->saveIdMapping($row, $destination, MigrateIdMapInterface::STATUS_IMPORTED);
    $this->assertSame(1, (int) $id_map->importedCount());

    // Add a row needing update and assert multiple imported rows.
    $source = ['source_id_property' => 'source_value_update'];
    $row = new Row($source, ['source_id_property' => []]);
    $destination = ['destination_id_property' => 'destination_value_update'];
    $id_map->saveIdMapping($row, $destination, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
    $this->assertSame(2, (int) $id_map->importedCount());
  }

  /**
   * Tests the number of processed source rows.
   *
   * Scenarios to test for:
   * - No processed rows.
   * - One processed row.
   * - Multiple processed rows.
   */
  public function testProcessedCount() {
    $id_map = $this->getIdMap();
    // Assert zero rows have been processed before adding rows.
    $this->assertSame(0, (int) $id_map->processedCount());
    $row_statuses = [
      MigrateIdMapInterface::STATUS_IMPORTED,
      MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
      MigrateIdMapInterface::STATUS_IGNORED,
      MigrateIdMapInterface::STATUS_FAILED,
    ];
    // Create a mapping row for each STATUS constant.
    foreach ($row_statuses as $status) {
      $source = ['source_id_property' => 'source_value_' . $status];
      $row = new Row($source, ['source_id_property' => []]);
      $destination = ['destination_id_property' => 'destination_value_' . $status];
      $id_map->saveIdMapping($row, $destination, $status);
      if ($status == MigrateIdMapInterface::STATUS_IMPORTED) {
        // Assert a single row has been processed.
        $this->assertSame(1, (int) $id_map->processedCount());
      }
    }
    // Assert multiple rows have been processed.
    $this->assertSame(count($row_statuses), (int) $id_map->processedCount());
  }

  /**
   * Data provider for testUpdateCount().
   *
   * Scenarios to test for:
   * - No updates.
   * - One update.
   * - Multiple updates.
   *
   * @return array
   *   An array of data values.
   */
  public function updateCountDataProvider() {
    return [
      [0],
      [1],
      [3],
    ];
  }

  /**
   * Performs the update count test with a given number of update rows.
   *
   * @param int $num_update_rows
   *   The number of update rows to test.
   *
   * @dataProvider updateCountDataProvider
   */
  public function testUpdateCount($num_update_rows) {
    for ($i = 0; $i < 5; $i++) {
      $row = $this->idMapDefaults();
      $row['sourceid1'] = "source_id_value_$i";
      $row['source_ids_hash'] = $this->getIdMap()->getSourceIDsHash(['source_id_property' => $row['sourceid1']]);
      $row['destid1'] = "destination_id_value_$i";
      $row['source_row_status'] = MigrateIdMapInterface::STATUS_IMPORTED;
      $this->saveMap($row);
    }
    for (; $i < 5 + $num_update_rows; $i++) {
      $row = $this->idMapDefaults();
      $row['sourceid1'] = "source_id_value_$i";
      $row['source_ids_hash'] = $this->getIdMap()->getSourceIDsHash(['source_id_property' => $row['sourceid1']]);
      $row['destid1'] = "destination_id_value_$i";
      $row['source_row_status'] = MigrateIdMapInterface::STATUS_NEEDS_UPDATE;
      $this->saveMap($row);
    }
    $id_map = $this->getIdMap();
    $this->assertSame($num_update_rows, (int) $id_map->updateCount());
  }

  /**
   * Data provider for testErrorCount().
   *
   * Scenarios to test for:
   * - No errors.
   * - One error.
   * - Multiple errors.
   *
   * @return array
   *   An array of data values.
   */
  public function errorCountDataProvider() {
    return [
      [0],
      [1],
      [3],
    ];
  }

  /**
   * Performs error count test with a given number of error rows.
   *
   * @param int $num_error_rows
   *   Number of error rows to test.
   *
   * @dataProvider errorCountDataProvider
   */
  public function testErrorCount($num_error_rows) {
    for ($i = 0; $i < 5; $i++) {
      $row = $this->idMapDefaults();
      $row['sourceid1'] = "source_id_value_$i";
      $row['source_ids_hash'] = $this->getIdMap()->getSourceIDsHash(['source_id_property' => $row['sourceid1']]);
      $row['destid1'] = "destination_id_value_$i";
      $row['source_row_status'] = MigrateIdMapInterface::STATUS_IMPORTED;
      $this->saveMap($row);
    }
    for (; $i < 5 + $num_error_rows; $i++) {
      $row = $this->idMapDefaults();
      $row['sourceid1'] = "source_id_value_$i";
      $row['source_ids_hash'] = $this->getIdMap()->getSourceIDsHash(['source_id_property' => $row['sourceid1']]);
      $row['destid1'] = "destination_id_value_$i";
      $row['source_row_status'] = MigrateIdMapInterface::STATUS_FAILED;
      $this->saveMap($row);
    }

    $this->assertSame($num_error_rows, (int) $this->getIdMap()->errorCount());
  }

  /**
   * Tests setting a row source_row_status to STATUS_NEEDS_UPDATE.
   */
  public function testSetUpdate() {
    $id_map = $this->getIdMap();
    $row_statuses = [
      MigrateIdMapInterface::STATUS_IMPORTED,
      MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
      MigrateIdMapInterface::STATUS_IGNORED,
      MigrateIdMapInterface::STATUS_FAILED,
    ];
    // Create a mapping row for each STATUS constant.
    foreach ($row_statuses as $status) {
      $source = ['source_id_property' => 'source_value_' . $status];
      $row = new Row($source, ['source_id_property' => []]);
      $destination = ['destination_id_property' => 'destination_value_' . $status];
      $id_map->saveIdMapping($row, $destination, $status);
      $expected_results[] = [
        'sourceid1' => 'source_value_' . $status,
        'source_ids_hash' => $this->getIdMap()->getSourceIDsHash($source),
        'destid1' => 'destination_value_' . $status,
        'source_row_status' => $status,
        'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
        'hash' => '',
      ];
    }
    // Assert that test values exist.
    $this->queryResultTest($this->getIdMapContents(), $expected_results);
    // Mark each row as STATUS_NEEDS_UPDATE.
    foreach ($row_statuses as $status) {
      $id_map->setUpdate(['source_id_property' => 'source_value_' . $status]);
    }
    // Update expected results.
    foreach ($expected_results as $key => $value) {
      $expected_results[$key]['source_row_status'] = MigrateIdMapInterface::STATUS_NEEDS_UPDATE;
    }
    // Assert that updated expected values match.
    $this->queryResultTest($this->getIdMapContents(), $expected_results);
    // Assert an exception is thrown when source identifiers are not provided.
    try {
      $id_map->setUpdate([]);
      $this->assertFalse(FALSE, 'MigrateException not thrown, when source identifiers were provided to update.');
    }
    catch (MigrateException $e) {
      $this->assertTrue(TRUE, "MigrateException thrown, when source identifiers were not provided to update.");
    }
  }

  /**
   * Tests prepareUpdate().
   */
  public function testPrepareUpdate() {
    $id_map = $this->getIdMap();
    $row_statuses = [
      MigrateIdMapInterface::STATUS_IMPORTED,
      MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
      MigrateIdMapInterface::STATUS_IGNORED,
      MigrateIdMapInterface::STATUS_FAILED,
    ];

    // Create a mapping row for each STATUS constant.
    foreach ($row_statuses as $status) {
      $source = ['source_id_property' => 'source_value_' . $status];
      $row = new Row($source, ['source_id_property' => []]);
      $destination = ['destination_id_property' => 'destination_value_' . $status];
      $id_map->saveIdMapping($row, $destination, $status);
      $expected_results[] = [
        'sourceid1' => 'source_value_' . $status,
        'destid1' => 'destination_value_' . $status,
        'source_row_status' => $status,
        'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
        'hash' => '',
      ];
    }

    // Assert that test values exist.
    $this->queryResultTest($this->getIdMapContents(), $expected_results);

    // Mark all rows as STATUS_NEEDS_UPDATE.
    $id_map->prepareUpdate();

    // Update expected results.
    foreach ($expected_results as $key => $value) {
      $expected_results[$key]['source_row_status'] = MigrateIdMapInterface::STATUS_NEEDS_UPDATE;
    }
    // Assert that updated expected values match.
    $this->queryResultTest($this->getIdMapContents(), $expected_results);
  }

  /**
   * Tests the destroy method.
   *
   * Scenarios to test for:
   * - No errors.
   * - One error.
   * - Multiple errors.
   */
  public function testDestroy() {
    $id_map = $this->getIdMap();
    // Initialize the ID map.
    $id_map->getDatabase();
    $map_table_name = $id_map->mapTableName();
    $message_table_name = $id_map->messageTableName();
    $row = new Row(['source_id_property' => 'source_value'], ['source_id_property' => []]);
    $id_map->saveIdMapping($row, ['destination_id_property' => 2]);
    $id_map->saveMessage(['source_id_property' => 'source_value'], 'A message');
    $this->assertTrue($this->database->schema()->tableExists($map_table_name),
                      "$map_table_name exists");
    $this->assertTrue($this->database->schema()->tableExists($message_table_name),
                      "$message_table_name exists");
    $id_map->destroy();
    $this->assertFalse($this->database->schema()->tableExists($map_table_name),
                       "$map_table_name does not exist");
    $this->assertFalse($this->database->schema()->tableExists($message_table_name),
                       "$message_table_name does not exist");
  }

  /**
   * Tests the getQualifiedMapTable method with a prefixed database.
   */
  public function testGetQualifiedMapTablePrefix() {
    $connection_options = [
      'database' => ':memory:',
      'prefix' => 'prefix',
    ];
    $pdo = Connection::open($connection_options);
    $this->database = new Connection($pdo, $connection_options);
    $qualified_map_table = $this->getIdMap()->getQualifiedMapTableName();
    // The SQLite driver is a special flower. It will prefix tables with
    // PREFIX.TABLE, instead of the standard PREFIXTABLE.
    // @see \Drupal\Core\Database\Driver\sqlite\Connection::__construct()
    $this->assertEquals('prefix.migrate_map_sql_idmap_test', $qualified_map_table);
  }

  /**
   * Tests all the iterator methods in one swing.
   *
   * The iterator methods are:
   * - Sql::rewind()
   * - Sql::next()
   * - Sql::valid()
   * - Sql::key()
   * - Sql::current()
   */
  public function testIterators() {
    for ($i = 0; $i < 3; $i++) {
      $row = $this->idMapDefaults();
      $row['sourceid1'] = "source_id_value_$i";
      $row['source_ids_hash'] = $this->getIdMap()->getSourceIDsHash(['source_id_property' => $row['sourceid1']]);
      $row['destid1'] = "destination_id_value_$i";
      $row['source_row_status'] = MigrateIdMapInterface::STATUS_IMPORTED;
      $expected_results[serialize(['sourceid1' => $row['sourceid1']])] = ['destid1' => $row['destid1']];
      $this->saveMap($row);
    }

    $this->assertSame(iterator_to_array($this->getIdMap()), $expected_results);
  }

  /**
   * Retrieves the contents of an ID map.
   *
   * @return array
   *   The contents of an ID map.
   */
  private function getIdMapContents() {
    $result = $this->database
      ->select('migrate_map_sql_idmap_test', 't')
      ->fields('t')
      ->execute();

    // The return value needs to be countable, or it will fail certain
    // assertions. iterator_to_array() will not suffice because it won't
    // respect the PDO fetch mode, if specified.
    $contents = [];
    foreach ($result as $row) {
      $contents[] = (array) $row;
    }
    return $contents;
  }

}
