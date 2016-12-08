<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Core\Database\Driver\sqlite\Connection;

/**
 * Tests query batching.
 *
 * @covers \Drupal\migrate_query_batch_test\Plugin\migrate\source\QueryBatchTest
 * @group migrate
 */
class QueryBatchTest extends KernelTestBase {

  /**
   * The mocked migration.
   *
   * @var MigrationInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'migrate',
    'migrate_query_batch_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a mock migration. This will be injected into the source plugin
    // under test.
    $this->migration = $this->prophesize(MigrationInterface::class);

    $this->migration->id()->willReturn(
      $this->randomMachineName(16)
    );
    // Prophesize a useless ID map plugin and an empty set of destination IDs.
    // Calling code can override these prophecies later and set up different
    // behaviors.
    $this->migration->getIdMap()->willReturn(
      $this->prophesize(MigrateIdMapInterface::class)->reveal()
    );
    $this->migration->getDestinationIds()->willReturn([]);
  }

  /**
   * Tests a negative batch size throws an exception.
   */
  public function testBatchSizeNegative() {
    $this->setExpectedException(MigrateException::class, 'batch_size must be greater than or equal to zero');
    $plugin = $this->getPlugin(['batch_size' => -1]);
    $plugin->next();
  }

  /**
   * Tests a non integer batch size throws an exception.
   */
  public function testBatchSizeNonInteger() {
    $this->setExpectedException(MigrateException::class, 'batch_size must be greater than or equal to zero');
    $plugin = $this->getPlugin(['batch_size' => '1']);
    $plugin->next();
  }

  /**
   * {@inheritdoc}
   */
  public function queryDataProvider() {
    // Define the parameters for building the data array. The first element is
    // the number of source data rows, the second is the batch size to set on
    // the plugin configuration.
    $test_parameters = [
      // Test when batch size is 0.
      [200, 0],
      // Test when rows mod batch size is 0.
      [200, 20],
      // Test when rows mod batch size is > 0.
      [200, 30],
      // Test when batch size = row count.
      [200, 200],
      // Test when batch size > row count.
      [200, 300],
    ];

    // Build the data provider array. The provider array consists of the source
    // data rows, the expected result data, the expected count, the plugin
    // configuration, the expected batch size and the expected batch count.
    $table = 'query_batch_test';
    $tests = [];
    $data_set = 0;
    foreach ($test_parameters as $data) {
      list($num_rows, $batch_size) = $data;
      for ($i = 0; $i < $num_rows; $i++) {
        $tests[$data_set]['source_data'][$table][] = [
          'id' => $i,
          'data' => $this->randomString(),
        ];
      }
      $tests[$data_set]['expected_data'] = $tests[$data_set]['source_data'][$table];
      $tests[$data_set][2] = $num_rows;
      // Plugin configuration array.
      $tests[$data_set][3] = ['batch_size' => $batch_size];
      // Expected batch size.
      $tests[$data_set][4] = $batch_size;
      // Expected batch count is 0 unless a batch size is set.
      $expected_batch_count = 0;
      if ($batch_size > 0) {
        $expected_batch_count = (int) ($num_rows / $batch_size);
        if ($num_rows % $batch_size) {
          // If there is a remainder an extra batch is needed to get the
          // remaining rows.
          $expected_batch_count++;
        }
      }
      $tests[$data_set][5] = $expected_batch_count;
      $data_set++;
    }
    return $tests;
  }

  /**
   * Tests query batch size.
   *
   * @param array $source_data
   *   The source data, keyed by table name. Each table is an array containing
   *   the rows in that table.
   * @param array $expected_data
   *   The result rows the plugin is expected to return.
   * @param int $num_rows
   *   How many rows the source plugin is expected to return.
   * @param array $configuration
   *   Configuration for the source plugin specifying the batch size.
   * @param int $expected_batch_size
   *   The expected batch size, will be set to zero for invalid batch sizes.
   * @param int $expected_batch_count
   *   The total number of batches.
   *
   * @dataProvider queryDataProvider
   */
  public function testQueryBatch($source_data, $expected_data, $num_rows, $configuration, $expected_batch_size, $expected_batch_count) {
    $plugin = $this->getPlugin($configuration);

    // Since we don't yet inject the database connection, we need to use a
    // reflection hack to set it in the plugin instance.
    $reflector = new \ReflectionObject($plugin);
    $property = $reflector->getProperty('database');
    $property->setAccessible(TRUE);

    $connection = $this->getDatabase($source_data);
    $property->setValue($plugin, $connection);

    // Test the results.
    $i = 0;
    /** @var \Drupal\migrate\Row $row */
    foreach ($plugin as $row) {

      $expected = $expected_data[$i++];
      $actual = $row->getSource();

      foreach ($expected as $key => $value) {
        $this->assertArrayHasKey($key, $actual);
        $this->assertSame((string) $value, (string) $actual[$key]);
      }
    }

    // Test that all rows were retrieved.
    self::assertSame($num_rows, $i);

    // Test the batch size.
    if (is_null($expected_batch_size)) {
      $expected_batch_size = $configuration['batch_size'];
    }
    $property = $reflector->getProperty('batchSize');
    $property->setAccessible(TRUE);
    self::assertSame($expected_batch_size, $property->getValue($plugin));

    // Test the batch count.
    if (is_null($expected_batch_count)) {
      $expected_batch_count = intdiv($num_rows, $expected_batch_size);
      if ($num_rows % $configuration['batch_size']) {
        $expected_batch_count++;
      }
    }
    $property = $reflector->getProperty('batch');
    $property->setAccessible(TRUE);
    self::assertSame($expected_batch_count, $property->getValue($plugin));
  }

  /**
   * Instantiates the source plugin under test.
   *
   * @param array $configuration
   *   The source plugin configuration.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface|object
   *   The fully configured source plugin.
   */
  protected function getPlugin($configuration) {
    /** @var \Drupal\migrate\Plugin\MigratePluginManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.migrate.source');
    $plugin = $plugin_manager->createInstance('query_batch_test', $configuration, $this->migration->reveal());

    $this->migration
      ->getSourcePlugin()
      ->willReturn($plugin);
    return $plugin;
  }

  /**
   * Builds an in-memory SQLite database from a set of source data.
   *
   * @param array $source_data
   *   The source data, keyed by table name. Each table is an array containing
   *   the rows in that table.
   *
   * @return \Drupal\Core\Database\Driver\sqlite\Connection
   *   The SQLite database connection.
   */
  protected function getDatabase(array $source_data) {
    // Create an in-memory SQLite database. Plugins can interact with it like
    // any other database, and it will cease to exist when the connection is
    // closed.
    $connection_options = ['database' => ':memory:'];
    $pdo = Connection::open($connection_options);
    $connection = new Connection($pdo, $connection_options);

    // Create the tables and fill them with data.
    foreach ($source_data as $table => $rows) {
      // Use the biggest row to build the table schema.
      $counts = array_map('count', $rows);
      asort($counts);
      end($counts);
      $pilot = $rows[key($counts)];

      $connection->schema()
        ->createTable($table, [
          // SQLite uses loose affinity typing, so it's OK for every field to
          // be a text field.
          'fields' => array_map(function () {
            return ['type' => 'text'];
          }, $pilot),
        ]);

      $fields = array_keys($pilot);
      $insert = $connection->insert($table)->fields($fields);
      array_walk($rows, [$insert, 'values']);
      $insert->execute();
    }
    return $connection;
  }

}
