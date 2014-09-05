<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\MigrateSqlIdMapTestCase.
 */

namespace Drupal\Tests\migrate\Unit;

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
  protected $migrationConfiguration = array(
    'id' => 'sql_idmap_test',
  );

  protected $sourceIds = array(
    'source_id_property' => array(),
  );

  protected $destinationIds = array(
    'destination_id_property' => array(),
  );

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Creates a test SQL ID map plugin.
   *
   * @param array $database_contents
   *   (optional) An array keyed by table names. Value are list of rows where
   *   each row is an associative array of field => value.
   * @param array $connection_options
   *   (optional) An array of database connection options.
   * @param string $prefix
   *   (optional) The database prefix.
   *
   * @return \Drupal\Tests\migrate\Unit\TestSqlIdMap
   *   A SQL ID map plugin test instance.
   */
  protected function getIdMap($database_contents = array(), $connection_options = array(), $prefix = '') {
    $migration = $this->getMigration();
    $plugin = $this->getMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $plugin->expects($this->any())
      ->method('getIds')
      ->will($this->returnValue($this->sourceIds));
    $migration->expects($this->any())
      ->method('getSourcePlugin')
      ->will($this->returnValue($plugin));
    $plugin = $this->getMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $plugin->expects($this->any())
      ->method('getIds')
      ->will($this->returnValue($this->destinationIds));
    $migration->expects($this->any())
      ->method('getDestinationPlugin')
      ->will($this->returnValue($plugin));
    $this->database = $this->getDatabase($database_contents, $connection_options, $prefix);
    $id_map = new TestSqlIdMap($this->database, array(), 'sql', array(), $migration);
    $migration->expects($this->any())
      ->method('getIdMap')
      ->will($this->returnValue($id_map));
    return $id_map;
  }

  /**
   * Sets defaults for SQL ID map plugin tests.
   */
  protected function idMapDefaults() {
    return array(
      'source_row_status' => MigrateIdMapInterface::STATUS_IMPORTED,
      'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
      'hash' => '',
    );
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
    $row = new Row($source, array('source_id_property' => array()));
    $id_map = $this->getIdMap();
    $id_map->saveIdMapping($row, array('destination_id_property' => 2));
    $expected_result = array(
      array(
        'sourceid1' => 'source_value',
        'destid1' => 2,
      ) + $this->idMapDefaults(),
    );
    $this->queryResultTest($this->database->databaseContents['migrate_map_sql_idmap_test'], $expected_result);
    $source = array(
      'source_id_property' => 'source_value_1',
    );
    $row = new Row($source, array('source_id_property' => array()));
    $id_map->saveIdMapping($row, array('destination_id_property' => 3));
    $expected_result[] = array(
      'sourceid1' => 'source_value_1',
      'destid1' => 3,
    ) + $this->idMapDefaults();
    $this->queryResultTest($this->database->databaseContents['migrate_map_sql_idmap_test'], $expected_result);
    $id_map->saveIdMapping($row, array('destination_id_property' => 4));
    $expected_result[1]['destid1'] = 4;
    $this->queryResultTest($this->database->databaseContents['migrate_map_sql_idmap_test'], $expected_result);
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
    $expected_results = array(0, 1, 2, 3);
    $id_map = $this->getIdMap();

    // Insert 4 message for later delete.
    foreach ($expected_results as $key => $expected_result) {
      $id_map->saveMessage(array($key), $message);
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
    $row_statuses = array(
      MigrateIdMapInterface::STATUS_IMPORTED,
      MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
      MigrateIdMapInterface::STATUS_IGNORED,
      MigrateIdMapInterface::STATUS_FAILED,
    );
    // Create a mapping row for each STATUS constant.
    foreach ($row_statuses as $status) {
      $source = array('source_id_property' => 'source_value_' . $status);
      $row = new Row($source, array('source_id_property' => array()));
      $destination = array('destination_id_property' => 'destination_value_' . $status);
      $id_map->saveIdMapping($row, $destination, $status);
      $expected_results[] = array(
        'sourceid1' => 'source_value_' . $status,
        'destid1' => 'destination_value_' . $status,
        'source_row_status' => $status,
        'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
        'hash' => '',
      );
      // Assert zero rows need an update.
      if ($status == MigrateIdMapInterface::STATUS_IMPORTED) {
        $rows_needing_update = $id_map->getRowsNeedingUpdate(1);
        $this->assertCount(0, $rows_needing_update);
      }
    }
    // Assert that test values exist.
    $this->queryResultTest($this->database->databaseContents['migrate_map_sql_idmap_test'], $expected_results);

    // Assert a single row needs an update.
    $row_needing_update = $id_map->getRowsNeedingUpdate(1);
    $this->assertCount(1, $row_needing_update);

    // Assert the row matches its original source.
    $source_id = $expected_results[MigrateIdMapInterface::STATUS_NEEDS_UPDATE]['sourceid1'];
    $test_row = $id_map->getRowBySource(array($source_id));
    $this->assertSame($test_row, $row_needing_update[0]);

    // Add additional row that needs an update.
    $source = array('source_id_property' => 'source_value_multiple');
    $row = new Row($source, array('source_id_property' => array()));
    $destination = array('destination_id_property' => 'destination_value_multiple');
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
    $expected_results = array(0, 1, 2, 3);
    $id_map = $this->getIdMap();

    // Test count message multiple times starting from 0.
    foreach ($expected_results as $key => $expected_result) {
      $count = $id_map->messageCount();
      $this->assertEquals($expected_result, $count);
      $id_map->saveMessage(array($key), $message);
    }
  }

  /**
   * Tests the SQL ID map save message method.
   */
  public function testMessageSave() {
    $message = 'Hello world.';
    $expected_results = array(
      1 => array('message' => $message, 'level' => MigrationInterface::MESSAGE_ERROR),
      2 => array('message' => $message, 'level' => MigrationInterface::MESSAGE_WARNING),
      3 => array('message' => $message, 'level' => MigrationInterface::MESSAGE_NOTICE),
      4 => array('message' => $message, 'level' => MigrationInterface::MESSAGE_INFORMATIONAL),
    );
    $id_map = $this->getIdMap();

    foreach ($expected_results as $key => $expected_result) {
      $id_map->saveMessage(array($key), $message, $expected_result['level']);
      $message_row = $this->database->select($id_map->messageTableName(), 'message')
                       ->fields('message')
                       ->condition('level', $expected_result['level'])
                       ->condition('message', $expected_result['message'])
                       ->execute()
                       ->fetchAssoc();
      $this->assertEquals($expected_result['message'], $message_row['message'], 'Message from database was read.');
    }

    // Insert with default level.
    $message_default = 'Hello world default.';
    $id_map->saveMessage(array(5), $message_default);
    $message_row = $this->database->select($id_map->messageTableName(), 'message')
                     ->fields('message')
                     ->condition('level', MigrationInterface::MESSAGE_ERROR)
                     ->condition('message', $message_default)
                     ->execute()
                     ->fetchAssoc();
    $this->assertEquals($message_default, $message_row['message'], 'Message from database was read.');
  }

  /**
   * Tests the getRowBySource method.
   */
  public function testGetRowBySource() {
    $row = array(
      'sourceid1' => 'source_id_value_1',
      'sourceid2' => 'source_id_value_2',
      'destid1' => 'destination_id_value_1',
    ) + $this->idMapDefaults();
    $database_contents['migrate_map_sql_idmap_test'][] = $row;
    $row = array(
      'sourceid1' => 'source_id_value_3',
      'sourceid2' => 'source_id_value_4',
      'destid1' => 'destination_id_value_2',
    ) + $this->idMapDefaults();
    $database_contents['migrate_map_sql_idmap_test'][] = $row;
    $source_id_values = array($row['sourceid1'], $row['sourceid2']);
    $id_map = $this->getIdMap($database_contents);
    $result_row = $id_map->getRowBySource($source_id_values);
    $this->assertSame($row, $result_row);
    $source_id_values = array('missing_value_1', 'missing_value_2');
    $result_row = $id_map->getRowBySource($source_id_values);
    $this->assertFalse($result_row);
  }

  /**
   * Tests the destination ID lookup method.
   *
   * Scenarios to test (for both hits and misses) are:
   * - Single-value source ID to single-value destination ID.
   * - Multi-value source ID to multi-value destination ID.
   * - Single-value source ID to multi-value destination ID.
   * - Multi-value source ID to single-value destination ID.
   */
  public function testLookupDestinationIdMapping() {
    $this->performLookupDestinationIdTest(1, 1);
    $this->performLookupDestinationIdTest(2, 2);
    $this->performLookupDestinationIdTest(1, 2);
    $this->performLookupDestinationIdTest(2, 1);
  }

  /**
   * Performs destination ID test on source and destination fields.
   *
   * @param int $num_source_fields
   *   Number of source fields to test.
   * @param int $num_destination_fields
   *   Number of destination fields to test.
   */
  protected function performLookupDestinationIdTest($num_source_fields, $num_destination_fields) {
    // Adjust the migration configuration according to the number of source and
    // destination fields.
    $this->sourceIds = array();
    $this->destinationIds = array();
    $source_id_values = array();
    $nonexistent_id_values = array();
    $row = $this->idMapDefaults();
    for ($i = 1; $i <= $num_source_fields; $i++) {
      $row["sourceid$i"] = "source_id_value_$i";
      $source_id_values[] = "source_id_value_$i";
      $nonexistent_id_values[] = "nonexistent_source_id_value_$i";
      $this->sourceIds["source_id_property_$i"] = array();
    }
    $expected_result = array();
    for ($i = 1; $i <= $num_destination_fields; $i++) {
      $row["destid$i"] = "destination_id_value_$i";
      $expected_result[] = "destination_id_value_$i";
      $this->destinationIds["destination_id_property_$i"] = array();
    }
    $database_contents['migrate_map_sql_idmap_test'][] = $row;
    $id_map = $this->getIdMap($database_contents);
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
    $row = array(
      'sourceid1' => 'source_id_value_1',
      'sourceid2' => 'source_id_value_2',
      'destid1' => 'destination_id_value_1',
    ) + $this->idMapDefaults();
    $database_contents['migrate_map_sql_idmap_test'][] = $row;
    $row = array(
      'sourceid1' => 'source_id_value_3',
      'sourceid2' => 'source_id_value_4',
      'destid1' => 'destination_id_value_2',
    ) + $this->idMapDefaults();
    $database_contents['migrate_map_sql_idmap_test'][] = $row;
    $dest_id_values = array($row['destid1']);
    $id_map = $this->getIdMap($database_contents);
    $result_row = $id_map->getRowByDestination($dest_id_values);
    $this->assertSame($row, $result_row);
    // This value does not exist.
    $dest_id_values = array('invalid_destination_id_property');
    $id_map = $this->getIdMap($database_contents);
    $result_row = $id_map->getRowByDestination($dest_id_values);
    $this->assertFalse($result_row);
  }

  /**
   * Tests the source ID lookup method.
   *
   * Scenarios to test (for both hits and misses) are:
   * - Single-value destination ID to single-value source ID.
   * - Multi-value destination ID to multi-value source ID.
   * - Single-value destination ID to multi-value source ID.
   * - Multi-value destination ID to single-value source ID.
   */
  public function testLookupSourceIDMapping() {
    $this->performLookupSourceIdTest(1, 1);
    $this->performLookupSourceIdTest(2, 2);
    $this->performLookupSourceIdTest(1, 2);
    $this->performLookupSourceIdTest(2, 1);
  }

  /**
   * Performs the source ID test on source and destination fields.
   *
   * @param int $num_source_fields
   *   Number of source fields to test.
   * @param int $num_destination_fields
   *   Number of destination fields to test.
   */
  protected function performLookupSourceIdTest($num_source_fields, $num_destination_fields) {
    // Adjust the migration configuration according to the number of source and
    // destination fields.
    $this->sourceIds = array();
    $this->destinationIds = array();
    $row = $this->idMapDefaults();
    $expected_result = array();
    for ($i = 1; $i <= $num_source_fields; $i++) {
      $row["sourceid$i"] = "source_id_value_$i";
      $expected_result[] = "source_id_value_$i";
      $this->sourceIds["source_id_property_$i"] = array();
    }
    $destination_id_values = array();
    $nonexistent_id_values = array();
    for ($i = 1; $i <= $num_destination_fields; $i++) {
      $row["destid$i"] = "destination_id_value_$i";
      $destination_id_values[] = "destination_id_value_$i";
      $nonexistent_id_values[] = "nonexistent_destination_id_value_$i";
      $this->destinationIds["destination_id_property_$i"] = array();
    }
    $database_contents['migrate_map_sql_idmap_test'][] = $row;
    $id_map = $this->getIdMap($database_contents);
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
    $source = array('source_id_property' => 'source_value_failed');
    $row = new Row($source, array('source_id_property' => array()));
    $destination = array('destination_id_property' => 'destination_value_failed');
    $id_map->saveIdMapping($row, $destination, MigrateIdMapInterface::STATUS_FAILED);
    $imported_count = $id_map->importedCount();
    $this->assertSame(0, $imported_count);

    // Add an imported row and assert single count.
    $source = array('source_id_property' => 'source_value_imported');
    $row = new Row($source, array('source_id_property' => array()));
    $destination = array('destination_id_property' => 'destination_value_imported');
    $id_map->saveIdMapping($row, $destination, MigrateIdMapInterface::STATUS_IMPORTED);
    $imported_count = $id_map->importedCount();
    $this->assertSame(1, $imported_count);

    // Add a row needing update and assert multiple imported rows.
    $source = array('source_id_property' => 'source_value_update');
    $row = new Row($source, array('source_id_property' => array()));
    $destination = array('destination_id_property' => 'destination_value_update');
    $id_map->saveIdMapping($row, $destination, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
    $imported_count = $id_map->importedCount();
    $this->assertSame(2, $imported_count);
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
    $processed_count = $id_map->processedCount();
    $this->assertSame(0, $processed_count);
    $row_statuses = array(
      MigrateIdMapInterface::STATUS_IMPORTED,
      MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
      MigrateIdMapInterface::STATUS_IGNORED,
      MigrateIdMapInterface::STATUS_FAILED,
    );
    // Create a mapping row for each STATUS constant.
    foreach ($row_statuses as $status) {
      $source = array('source_id_property' => 'source_value_' . $status);
      $row = new Row($source, array('source_id_property' => array()));
      $destination = array('destination_id_property' => 'destination_value_' . $status);
      $id_map->saveIdMapping($row, $destination, $status);
      if ($status == MigrateIdMapInterface::STATUS_IMPORTED) {
        // Assert a single row has been processed.
        $processed_count = $id_map->processedCount();
        $this->assertSame(1, $processed_count);
      }
    }
    // Assert multiple rows have been processed.
    $processed_count = $id_map->processedCount();
    $this->assertSame(count($row_statuses), $processed_count);
  }

  /**
   * Tests the update count method.
   *
   * Scenarios to test for:
   * - No updates.
   * - One update.
   * - Multiple updates.
   */
  public function testUpdateCount() {
    $this->performUpdateCountTest(0);
    $this->performUpdateCountTest(1);
    $this->performUpdateCountTest(3);
  }

  /**
   * Performs the update count test with a given number of update rows.
   *
   * @param int $num_update_rows
   *   The number of update rows to test.
   */
  protected function performUpdateCountTest($num_update_rows) {
    $database_contents['migrate_map_sql_idmap_test'] = array();
    for ($i = 0; $i < 5; $i++) {
      $row = $this->idMapDefaults();
      $row['sourceid1'] = "source_id_value_$i";
      $row['destid1'] = "destination_id_value_$i";
      $row['source_row_status'] = MigrateIdMapInterface::STATUS_IMPORTED;
      $database_contents['migrate_map_sql_idmap_test'][] = $row;
    }
    for (; $i < 5 + $num_update_rows; $i++) {
      $row = $this->idMapDefaults();
      $row['sourceid1'] = "source_id_value_$i";
      $row['destid1'] = "destination_id_value_$i";
      $row['source_row_status'] = MigrateIdMapInterface::STATUS_NEEDS_UPDATE;
      $database_contents['migrate_map_sql_idmap_test'][] = $row;
    }
    $id_map = $this->getIdMap($database_contents);
    $count = $id_map->updateCount();
    $this->assertSame($num_update_rows, $count);
  }

  /**
   * Tests the error count method.
   *
   * Scenarios to test for:
   * - No errors.
   * - One error.
   * - Multiple errors.
   */
  public function testErrorCount() {
    $this->performErrorCountTest(0);
    $this->performErrorCountTest(1);
    $this->performErrorCountTest(3);
  }

  /**
   * Performs error count test with a given number of error rows.
   *
   * @param int $num_error_rows
   *   Number of error rows to test.
   */
  protected function performErrorCountTest($num_error_rows) {
    $database_contents['migrate_map_sql_idmap_test'] = array();
    for ($i = 0; $i < 5; $i++) {
      $row = $this->idMapDefaults();
      $row['sourceid1'] = "source_id_value_$i";
      $row['destid1'] = "destination_id_value_$i";
      $row['source_row_status'] = MigrateIdMapInterface::STATUS_IMPORTED;
      $database_contents['migrate_map_sql_idmap_test'][] = $row;
    }
    for (; $i < 5 + $num_error_rows; $i++) {
      $row = $this->idMapDefaults();
      $row['sourceid1'] = "source_id_value_$i";
      $row['destid1'] = "destination_id_value_$i";
      $row['source_row_status'] = MigrateIdMapInterface::STATUS_FAILED;
      $database_contents['migrate_map_sql_idmap_test'][] = $row;
    }

    $id_map = $this->getIdMap($database_contents);
    $count = $id_map->errorCount();
    $this->assertSame($num_error_rows, $count);
  }

  /**
   * Tests setting a row source_row_status to STATUS_NEEDS_UPDATE.
   */
  public function testSetUpdate() {
    $id_map = $this->getIdMap();
    $row_statuses = array(
      MigrateIdMapInterface::STATUS_IMPORTED,
      MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
      MigrateIdMapInterface::STATUS_IGNORED,
      MigrateIdMapInterface::STATUS_FAILED,
    );
    // Create a mapping row for each STATUS constant.
    foreach ($row_statuses as $status) {
      $source = array('source_id_property' => 'source_value_' . $status);
      $row = new Row($source, array('source_id_property' => array()));
      $destination = array('destination_id_property' => 'destination_value_' . $status);
      $id_map->saveIdMapping($row, $destination, $status);
      $expected_results[] = array(
        'sourceid1' => 'source_value_' . $status,
        'destid1' => 'destination_value_' . $status,
        'source_row_status' => $status,
        'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
        'hash' => '',
      );
    }
    // Assert that test values exist.
    $this->queryResultTest($this->database->databaseContents['migrate_map_sql_idmap_test'], $expected_results);
    // Mark each row as STATUS_NEEDS_UPDATE.
    foreach ($row_statuses as $status) {
      $id_map->setUpdate(array('source_value_' . $status));
    }
    // Update expected results.
    foreach ($expected_results as $key => $value) {
      $expected_results[$key]['source_row_status'] = MigrateIdMapInterface::STATUS_NEEDS_UPDATE;
    }
    // Assert that updated expected values match.
    $this->queryResultTest($this->database->databaseContents['migrate_map_sql_idmap_test'], $expected_results);
    // Assert an exception is thrown when source identifiers are not provided.
    try {
      $id_map->setUpdate(array());
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
    $row_statuses = array(
      MigrateIdMapInterface::STATUS_IMPORTED,
      MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
      MigrateIdMapInterface::STATUS_IGNORED,
      MigrateIdMapInterface::STATUS_FAILED,
    );

    // Create a mapping row for each STATUS constant.
    foreach ($row_statuses as $status) {
      $source = array('source_id_property' => 'source_value_' . $status);
      $row = new Row($source, array('source_id_property' => array()));
      $destination = array('destination_id_property' => 'destination_value_' . $status);
      $id_map->saveIdMapping($row, $destination, $status);
      $expected_results[] = array(
        'sourceid1' => 'source_value_' . $status,
        'destid1' => 'destination_value_' . $status,
        'source_row_status' => $status,
        'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
        'hash' => '',
      );
    }

    // Assert that test values exist.
    $this->queryResultTest($this->database->databaseContents['migrate_map_sql_idmap_test'], $expected_results);

    // Mark all rows as STATUS_NEEDS_UPDATE.
    $id_map->prepareUpdate();

    // Update expected results.
    foreach ($expected_results as $key => $value) {
      $expected_results[$key]['source_row_status'] = MigrateIdMapInterface::STATUS_NEEDS_UPDATE;
    }
    // Assert that updated expected values match.
    $this->queryResultTest($this->database->databaseContents['migrate_map_sql_idmap_test'], $expected_results);
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
    // Initialize the id map.
    $id_map->getDatabase();
    $map_table_name = $id_map->mapTableName();
    $message_table_name = $id_map->messageTableName();
    $row = new Row(array('source_id_property' => 'source_value'), array('source_id_property' => array()));
    $id_map->saveIdMapping($row, array('destination_id_property' => 2));
    $id_map->saveMessage(array('source_value'), 'A message');
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
   * Tests the getQualifiedMapTable method with an unprefixed database.
   */
  public function testGetQualifiedMapTableNoPrefix() {
    $id_map = $this->getIdMap(array(), array('database' => 'source_database'));
    $qualified_map_table = $id_map->getQualifiedMapTableName();
    $this->assertEquals('source_database.migrate_map_sql_idmap_test', $qualified_map_table);
  }

  /**
   * Tests the getQualifiedMapTable method with a prefixed database.
   */
  public function testGetQualifiedMapTablePrefix() {
    $id_map = $this->getIdMap(array(), array('database' => 'source_database'), 'prefix');
    $qualified_map_table = $id_map->getQualifiedMapTableName();
    $this->assertEquals('source_database.prefixmigrate_map_sql_idmap_test', $qualified_map_table);
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
    $database_contents['migrate_map_sql_idmap_test'] = array();
    for ($i = 0; $i < 3; $i++) {
      $row = $this->idMapDefaults();
      $row['sourceid1'] = "source_id_value_$i";
      $row['destid1'] = "destination_id_value_$i";
      $row['source_row_status'] = MigrateIdMapInterface::STATUS_IMPORTED;
      $expected_results[serialize(array('sourceid1' => $row['sourceid1']))] = array('destid1' => $row['destid1']);
      $database_contents['migrate_map_sql_idmap_test'][] = $row;
    }

    $id_map = $this->getIdMap($database_contents);
    $this->assertSame(iterator_to_array($id_map), $expected_results);
  }

}
