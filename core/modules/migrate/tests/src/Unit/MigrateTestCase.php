<?php

namespace Drupal\Tests\migrate\Unit;

use Drupal\Core\Database\Driver\sqlite\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Provides setup and helper methods for Migrate module tests.
 */
abstract class MigrateTestCase extends UnitTestCase {

  /**
   * An array of migration configuration values.
   *
   * @var array
   */
  protected $migrationConfiguration = [];

  /**
   * The migration ID map.
   *
   * @var \Drupal\migrate\Plugin\MigrateIdMapInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $idMap;

  /**
   * Local store for mocking setStatus()/getStatus().
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface::STATUS_*
   */
  protected $migrationStatus = MigrationInterface::STATUS_IDLE;

  /**
   * Retrieves a mocked migration.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface|\PHPUnit_Framework_MockObject_MockObject
   *   The mocked migration.
   */
  protected function getMigration() {
    $this->migrationConfiguration += ['migrationClass' => 'Drupal\migrate\Plugin\Migration'];
    $this->idMap = $this->getMock('Drupal\migrate\Plugin\MigrateIdMapInterface');

    $this->idMap
      ->method('getQualifiedMapTableName')
      ->willReturn('test_map');

    $migration = $this->getMockBuilder($this->migrationConfiguration['migrationClass'])
      ->disableOriginalConstructor()
      ->getMock();

    $migration->method('checkRequirements')
      ->willReturn(TRUE);

    $migration->method('getIdMap')
      ->willReturn($this->idMap);

    // We need the state to be toggled throughout the test so we store the value
    // on the test class and use a return callback.
    $migration->expects($this->any())
      ->method('getStatus')
      ->willReturnCallback(function() {
        return $this->migrationStatus;
      });
    $migration->expects($this->any())
      ->method('setStatus')
      ->willReturnCallback(function($status) {
        $this->migrationStatus = $status;
      });

    $migration->method('getMigrationDependencies')
      ->willReturn([
        'required' => [],
        'optional' => [],
      ]);

    $configuration = &$this->migrationConfiguration;

    $migration->method('getHighWaterProperty')
      ->willReturnCallback(function () use ($configuration) {
        return isset($configuration['high_water_property']) ? $configuration['high_water_property'] : '';
      });

    $migration->method('set')
      ->willReturnCallback(function ($argument, $value) use (&$configuration) {
        $configuration[$argument] = $value;
      });

    $migration->method('id')
      ->willReturn($configuration['id']);

    return $migration;
  }

  /**
   * Gets an SQLite database connection object for use in tests.
   *
   * @param array $database_contents
   *   The database contents faked as an array. Each key is a table name, each
   *   value is a list of table rows, an associative array of field => value.
   * @param array $connection_options
   *   (optional) Options for the database connection. Defaults to an empty
   *   array.
   *
   * @return \Drupal\Core\Database\Driver\sqlite\Connection
   *   The database connection.
   */
  protected function getDatabase(array $database_contents, $connection_options = []) {
    if (extension_loaded('pdo_sqlite')) {
      $connection_options['database'] = ':memory:';
      $pdo = Connection::open($connection_options);
      $connection = new Connection($pdo, $connection_options);
    }
    else {
      $this->markTestSkipped('The pdo_sqlite extension is not available.');
    }

    // Initialize the DIC with a fake module handler for alterable queries.
    $container = new ContainerBuilder();
    $container->set('module_handler', $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface'));
    \Drupal::setContainer($container);

    // Create the tables and load them up with data, skipping empty ones.
    foreach (array_filter($database_contents) as $table => $rows) {
      $pilot_row = reset($rows);
      $connection->schema()->createTable($table, $this->createSchemaFromRow($pilot_row));

      $insert = $connection->insert($table)->fields(array_keys($pilot_row));
      array_walk($rows, [$insert, 'values']);
      $insert->execute();
    }

    return $connection;
  }

  /**
   * Generates a table schema from a row.
   *
   * @param array $row
   *   The reference row on which to base the schema.
   *
   * @return array
   *   The Schema API-ready table schema.
   */
  protected function createSchemaFromRow(array $row) {
    // SQLite uses loose ("affinity") typing, so it is OK for every column to be
    // a text field.
    $fields = array_map(function() { return ['type' => 'text']; }, $row);
    return ['fields' => $fields];
  }

  /**
   * Tests a query.
   *
   * @param array|\Traversable $iter
   *   The countable. foreach-able actual results if a query is being run.
   * @param array $expected_results
   *   An array of expected results.
   */
  public function queryResultTest($iter, $expected_results) {
    $this->assertSame(count($expected_results), count($iter), 'Number of results match');
    $count = 0;
    foreach ($iter as $data_row) {
      $expected_row = $expected_results[$count];
      $count++;
      foreach ($expected_row as $key => $expected_value) {
        $this->retrievalAssertHelper($expected_value, $this->getValue($data_row, $key), sprintf('Value matches for key "%s"', $key));
      }
    }
    $this->assertSame(count($expected_results), $count);
  }

  /**
   * Gets the value on a row for a given key.
   *
   * @param array $row
   *   The row information.
   * @param string $key
   *   The key identifier.
   *
   * @return mixed
   *   The value on a row for a given key.
   */
  protected function getValue($row, $key) {
    return $row[$key];
  }

  /**
   * Asserts tested values during test retrieval.
   *
   * @param mixed $expected_value
   *   The incoming expected value to test.
   * @param mixed $actual_value
   *   The incoming value itself.
   * @param string $message
   *   The tested result as a formatted string.
   */
  protected function retrievalAssertHelper($expected_value, $actual_value, $message) {
    if (is_array($expected_value)) {
      // If the expected and actual values are empty, no need to array compare.
      if (empty($expected_value && $actual_value)) {
        return;
      }
      $this->assertArrayEquals($expected_value, $actual_value, $message);
    }
    else {
      $this->assertSame((string) $expected_value, (string) $actual_value, $message);
    }
  }

}
